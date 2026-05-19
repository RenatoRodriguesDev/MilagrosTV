import express from 'express';
import cors    from 'cors';
import WebTorrent from 'webtorrent';
import { extname } from 'path';

// Catch anything that escapes normal try/catch
process.on('uncaughtException',      err => console.error('uncaughtException:', err));
process.on('unhandledRejection',     err => console.error('unhandledRejection:', err));

const app    = express();
const client = new WebTorrent();

// Prevent client-level errors from crashing the process
client.on('error', err => console.error('WebTorrent client error:', err.message));

app.use(cors());

const MIME = {
    '.mp4':  'video/mp4',
    '.mkv':  'video/x-matroska',
    '.webm': 'video/webm',
    '.avi':  'video/x-msvideo',
    '.mov':  'video/quicktime',
};

const VIDEO_EXT = Object.keys(MIME);

// Cache .torrent URL → infoHash so we can find it on re-use
const urlToHash = new Map();

async function getOrAdd(magnetOrUrl) {
    // ── .torrent file URL (not a magnet URI) ─────────────────────────────────
    if (!magnetOrUrl.startsWith('magnet:')) {
        // Check cache first
        if (urlToHash.has(magnetOrUrl)) {
            const hash     = urlToHash.get(magnetOrUrl);
            const existing = client.torrents.find(t => t.infoHash === hash);
            if (existing) {
                if (existing.ready) return existing;
                return new Promise((resolve, reject) => {
                    const t = setTimeout(() => reject(new Error('Timeout')), 30000);
                    existing.once('ready', () => { clearTimeout(t); resolve(existing); });
                    existing.once('error', err => { clearTimeout(t); reject(err); });
                });
            }
        }

        console.log('Fetching .torrent file:', magnetOrUrl);
        const resp = await fetch(magnetOrUrl);
        if (!resp.ok) throw new Error(`Failed to fetch .torrent: ${resp.status}`);
        const buffer = Buffer.from(await resp.arrayBuffer());

        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('Timeout: não foi possível conectar a peers')), 30000);
            let torrent;
            try {
                torrent = client.add(buffer, { path: '/tmp/torrents', destroyStoreOnDestroy: false });
            } catch(e) { clearTimeout(timeout); return reject(e); }

            console.log('Adding .torrent buffer, fetching metadata...');
            torrent.once('metadata', () => console.log('Metadata received:', torrent.name));
            torrent.once('ready', () => {
                console.log('Torrent ready:', torrent.name);
                urlToHash.set(magnetOrUrl, torrent.infoHash);
                clearTimeout(timeout);
                resolve(torrent);
            });
            torrent.once('error', err => { console.error('Torrent error:', err.message); clearTimeout(timeout); reject(err); });
        });
    }

    // ── Magnet URI ────────────────────────────────────────────────────────────
    // infoHash is always lowercase in WebTorrent; magnet URIs from indexers may be uppercase
    const magnetLower = magnetOrUrl.toLowerCase();
    const existing = client.torrents.find(t => t.infoHash && magnetLower.includes(t.infoHash));
    if (existing) {
        if (existing.ready) return existing;
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('Timeout aguardando torrent existente')), 30000);
            existing.once('ready', () => { clearTimeout(timeout); resolve(existing); });
            existing.once('error', err => { clearTimeout(timeout); reject(err); });
        });
    }

    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('Timeout: não foi possível conectar a peers')), 30000);

        let torrent;
        try {
            torrent = client.add(magnetOrUrl, { path: '/tmp/torrents', destroyStoreOnDestroy: false });
        } catch(e) { clearTimeout(timeout); return reject(e); }

        console.log('Adding torrent:', torrent.infoHash || 'fetching metadata...');
        torrent.once('metadata', () => console.log('Metadata received:', torrent.name));
        torrent.once('ready', () => {
            console.log('Torrent ready:', torrent.name);
            clearTimeout(timeout);
            resolve(torrent);
        });
        torrent.once('error', err => { console.error('Torrent error:', err.message); clearTimeout(timeout); reject(err); });
    });
}

app.get('/stream', async (req, res) => {
    const { magnet } = req.query;
    if (!magnet) return res.status(400).json({ error: 'magnet required' });

    let disconnected = false;
    req.on('close', () => { disconnected = true; });

    try {
        const torrent = await getOrAdd(decodeURIComponent(magnet));
        if (disconnected) return;

        const file = torrent.files.find(f => VIDEO_EXT.includes(extname(f.name).toLowerCase()));
        if (!file) return res.status(404).json({ error: 'No video file found in torrent' });

        const total = file.length;
        const ext   = extname(file.name).toLowerCase();
        const mime  = MIME[ext] || 'application/octet-stream';
        const range = req.headers.range;

        if (range) {
            const [rawStart, rawEnd] = range.replace(/bytes=/, '').split('-');
            const start  = parseInt(rawStart, 10) || 0;
            const end    = rawEnd ? parseInt(rawEnd, 10) : Math.min(start + 1024 * 1024 * 4, total - 1);
            const length = end - start + 1;

            res.writeHead(206, {
                'Content-Range':  `bytes ${start}-${end}/${total}`,
                'Accept-Ranges':  'bytes',
                'Content-Length': length,
                'Content-Type':   mime,
            });

            const stream = file.createReadStream({ start, end });
            stream.on('error', err => { console.error('Read stream error:', err.message); res.end(); });
            stream.pipe(res);
        } else {
            res.writeHead(200, {
                'Content-Length': total,
                'Content-Type':   mime,
                'Accept-Ranges':  'bytes',
            });

            const stream = file.createReadStream();
            stream.on('error', err => { console.error('Read stream error:', err.message); res.end(); });
            stream.pipe(res);
        }
    } catch (err) {
        console.error('Stream error:', err.message);
        if (!res.headersSent) res.status(503).json({ error: err.message });
    }
});

app.get('/info', async (req, res) => {
    const { magnet } = req.query;
    if (!magnet) return res.status(400).json({ error: 'magnet required' });

    try {
        const torrent = await getOrAdd(decodeURIComponent(magnet));
        res.json({
            name:     torrent.name,
            files:    torrent.files.map((f, i) => ({ index: i, name: f.name, size: f.length })),
            peers:    torrent.numPeers,
            progress: torrent.progress,
        });
    } catch (err) {
        res.status(503).json({ error: err.message });
    }
});

app.get('/preload', async (req, res) => {
    const { magnet } = req.query;
    if (!magnet) return res.status(400).json({ error: 'magnet required' });

    try {
        const torrent = await getOrAdd(decodeURIComponent(magnet));
        const file = torrent.files.find(f => VIDEO_EXT.includes(extname(f.name).toLowerCase()));
        res.json({
            ok:    true,
            name:  file?.name || torrent.name,
            size:  file?.length || 0,
            peers: torrent.numPeers,
        });
    } catch (err) {
        res.status(503).json({ error: err.message });
    }
});

// List subtitle files in a torrent
app.get('/subtitles', async (req, res) => {
    const { magnet } = req.query;
    if (!magnet) return res.status(400).json({ error: 'magnet required' });

    try {
        const torrent = await getOrAdd(decodeURIComponent(magnet));
        const SUBTITLE_EXT = ['.srt', '.ass', '.vtt', '.sub'];
        const subs = torrent.files
            .filter(f => SUBTITLE_EXT.includes(extname(f.name).toLowerCase()))
            .map(f => ({ name: f.name, size: f.length }));
        res.json(subs);
    } catch (err) {
        res.status(503).json({ error: err.message });
    }
});

// Serve subtitle file, converting .srt/.ass to WebVTT on the fly
app.get('/subtitle', async (req, res) => {
    const { magnet, name } = req.query;
    if (!magnet || !name) return res.status(400).json({ error: 'magnet and name required' });

    try {
        const torrent = await getOrAdd(decodeURIComponent(magnet));
        const file = torrent.files.find(f => f.name === name);
        if (!file) return res.status(404).json({ error: 'Subtitle not found' });

        const ext = extname(file.name).toLowerCase();
        res.setHeader('Content-Type', 'text/vtt; charset=utf-8');
        res.setHeader('Access-Control-Allow-Origin', '*');

        if (ext === '.vtt') {
            const stream = file.createReadStream();
            stream.on('error', () => res.end());
            stream.pipe(res);
            return;
        }

        // Read full file then convert to VTT
        const chunks = [];
        const stream = file.createReadStream();
        stream.on('data', chunk => chunks.push(chunk));
        stream.on('error', () => res.end());
        stream.on('end', () => {
            const text = Buffer.concat(chunks).toString('utf-8');
            let vtt;
            if (ext === '.srt' || ext === '.sub') {
                // SRT → VTT: replace comma in timestamps, add WEBVTT header
                vtt = 'WEBVTT\n\n' + text.replace(/(\d{2}:\d{2}:\d{2}),(\d{3})/g, '$1.$2');
            } else if (ext === '.ass') {
                // Basic ASS → VTT conversion (strips formatting)
                const lines = text.split('\n');
                let vttLines = ['WEBVTT', ''];
                let idx = 1;
                for (const line of lines) {
                    const m = line.match(/^Dialogue:.*?,(\d+:\d{2}:\d{2}\.\d{2}),(\d+:\d{2}:\d{2}\.\d{2}),.*?,,\d+,\d+,\d+,,(.*)$/);
                    if (m) {
                        const toVttTime = t => t.replace(/(\d+):(\d{2}):(\d{2})\.(\d{2})/, (_, h, m, s, cs) =>
                            `${h.padStart(2,'0')}:${m}:${s}.${cs}0`);
                        const txt = m[3].replace(/\{[^}]*\}/g, '').replace(/\\N/g, '\n');
                        vttLines.push(String(idx++), `${toVttTime(m[1])} --> ${toVttTime(m[2])}`, txt, '');
                    }
                }
                vtt = vttLines.join('\n');
            } else {
                vtt = 'WEBVTT\n\n' + text;
            }
            res.end(vtt);
        });
    } catch (err) {
        res.status(503).json({ error: err.message });
    }
});

app.get('/health', (_, res) => res.json({ ok: true, torrents: client.torrents.length }));

app.listen(9090, () => console.log('Stream server on :9090'));
