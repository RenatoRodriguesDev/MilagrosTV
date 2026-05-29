import express from 'express';
import cors    from 'cors';
import WebTorrent from 'webtorrent';
import { extname, join } from 'path';
import { spawn }         from 'child_process';

const TRANSCODE_EXT = new Set(['.mkv', '.avi', '.mov', '.ts']);

function needsTranscode(filename) {
    const ext = extname(filename).toLowerCase();
    if (TRANSCODE_EXT.has(ext)) return true;
    const name = filename.toUpperCase();
    return /\b(HEVC|X265|H\.?265|HEVC10)\b/.test(name);
}

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

const urlToHash    = new Map(); // .torrent URL → infoHash
const inFlight     = new Map(); // magnetOrUrl  → Promise<torrent>
const lastAccessed = new Map(); // infoHash     → Date.now()

const MAX_IDLE_MS  = 2 * 60 * 60 * 1000; // 2 horas sem acesso → apagar
const MAX_TORRENTS = 3;                   // máximo de torrents em simultâneo

function evictOldTorrents() {
    const now = Date.now();
    for (const torrent of [...client.torrents]) {
        const idle = now - (lastAccessed.get(torrent.infoHash) ?? 0);
        if (idle > MAX_IDLE_MS) {
            console.log(`Evicting idle torrent: ${torrent.name}`);
            torrent.destroy({ destroyStore: true });
            lastAccessed.delete(torrent.infoHash);
        }
    }
    // Se ainda acima do limite, apagar o mais antigo
    if (client.torrents.length > MAX_TORRENTS) {
        const oldest = [...client.torrents].sort(
            (a, b) => (lastAccessed.get(a.infoHash) ?? 0) - (lastAccessed.get(b.infoHash) ?? 0)
        )[0];
        if (oldest) {
            console.log(`Evicting oldest torrent (limit): ${oldest.name}`);
            oldest.destroy({ destroyStore: true });
            lastAccessed.delete(oldest.infoHash);
        }
    }
}

setInterval(evictOldTorrents, 15 * 60 * 1000); // verificar a cada 15 minutos

function findExisting(magnetOrUrl) {
    if (magnetOrUrl.startsWith('magnet:')) {
        const m = magnetOrUrl.match(/xt=urn:btih:([a-fA-F0-9]{40})/i);
        if (m) {
            const hash = m[1].toLowerCase();
            return client.torrents.find(t => t.infoHash === hash) ?? null;
        }
    } else if (urlToHash.has(magnetOrUrl)) {
        const hash = urlToHash.get(magnetOrUrl);
        return client.torrents.find(t => t.infoHash === hash) ?? null;
    }
    return null;
}

function waitForReady(torrent) {
    return new Promise((resolve, reject) => {
        if (torrent.ready) return resolve(torrent);
        const timeout = setTimeout(() => reject(new Error('Timeout aguardando torrent')), 30000);
        torrent.once('ready', () => { clearTimeout(timeout); resolve(torrent); });
        torrent.once('error', err => { clearTimeout(timeout); reject(err); });
    });
}

async function addNew(magnetOrUrl) {
    let torrentId = magnetOrUrl;

    if (!magnetOrUrl.startsWith('magnet:')) {
        console.log('Fetching .torrent file:', magnetOrUrl);
        const resp = await fetch(magnetOrUrl);
        if (!resp.ok) throw new Error(`Failed to fetch .torrent: ${resp.status}`);
        torrentId = Buffer.from(await resp.arrayBuffer());
    }

    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('Timeout: não foi possível conectar a peers')), 30000);

        let torrent;
        try {
            torrent = client.add(torrentId, { path: '/tmp/torrents', destroyStoreOnDestroy: true });
        } catch(e) {
            clearTimeout(timeout);
            // Race condition: added by another concurrent request
            if (e.message?.includes('duplicate')) {
                const found = findExisting(magnetOrUrl);
                if (found) return waitForReady(found).then(resolve, reject);
            }
            return reject(e);
        }

        console.log('Adding torrent:', torrent.infoHash || 'fetching metadata...');
        torrent.once('metadata', () => {
            console.log('Metadata received:', torrent.name);
            if (!magnetOrUrl.startsWith('magnet:')) urlToHash.set(magnetOrUrl, torrent.infoHash);
        });
        torrent.once('ready', () => {
            console.log('Torrent ready:', torrent.name);
            clearTimeout(timeout);
            resolve(torrent);
        });
        torrent.once('error', err => {
            console.error('Torrent error:', err.message);
            clearTimeout(timeout);
            reject(err);
        });
    });
}

async function getOrAdd(magnetOrUrl) {
    // Re-use in-flight promise — prevents duplicate add when preload + stream race
    if (inFlight.has(magnetOrUrl)) return inFlight.get(magnetOrUrl);

    // Already in client and ready
    const existing = findExisting(magnetOrUrl);
    if (existing) {
        lastAccessed.set(existing.infoHash, Date.now());
        return waitForReady(existing);
    }

    // Start and cache the promise
    const promise = addNew(magnetOrUrl);
    inFlight.set(magnetOrUrl, promise);
    promise
        .then(t => lastAccessed.set(t.infoHash, Date.now()))
        .finally(() => inFlight.delete(magnetOrUrl));
    return promise;
}

app.get('/stream', async (req, res) => {
    const { magnet } = req.query;
    if (!magnet) return res.status(400).json({ error: 'magnet required' });

    let disconnected = false;
    req.on('close', () => { disconnected = true; });

    try {
        const torrent = await getOrAdd(decodeURIComponent(magnet));
        if (disconnected) return;
        lastAccessed.set(torrent.infoHash, Date.now());

        // Allow selecting a specific file by index
        const fileIndex = req.query.fileIndex !== undefined ? parseInt(req.query.fileIndex) : -1;
        let file;
        if (fileIndex >= 0 && torrent.files[fileIndex]) {
            file = torrent.files[fileIndex];
        } else {
            file = torrent.files.find(f => VIDEO_EXT.includes(extname(f.name).toLowerCase()));
        }
        if (!file) return res.status(404).json({ error: 'No video file found in torrent' });

        const total = file.length;
        const ext   = extname(file.name).toLowerCase();
        const mime  = MIME[ext] || 'application/octet-stream';

        if (needsTranscode(file.name)) {
            // MKV/AVI/HEVC: read from disk path so FFmpeg can seek and read duration from header
            const fullTranscode = req.query.transcode === '1';
            const videoCodec = fullTranscode
                ? ['-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '28']
                : ['-c:v', 'copy'];

            // Build full file path — WebTorrent stores files at /tmp/torrents/<file.path>
            const filePath = join('/tmp/torrents', file.path);
            console.log(`${fullTranscode ? 'Transcoding' : 'Remuxing'} from disk: ${filePath}`);
            res.writeHead(200, { 'Content-Type': 'video/mp4' });

            const ff = spawn('ffmpeg', [
                '-fflags',         'nobuffer',
                '-analyzeduration','500000',   // 0.5s analysis (faster start)
                '-probesize',      '500000',   // 500KB probe
                '-i', filePath,               // read from disk, not stdin
                ...videoCodec,
                '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
                '-f', 'mp4', '-movflags', 'frag_keyframe+default_base_moof',
                // No empty_moov: FFmpeg reads duration from MKV header → correct timeline
                'pipe:1'
            ], { stdio: ['ignore', 'pipe', 'pipe'] }); // stdin is ignore (not needed)

            ff.stdout.pipe(res);
            ff.stderr.on('data', () => {});

            req.on('close', () => ff.kill('SIGKILL'));
            ff.on('error', err => { console.error('FFmpeg error:', err.message); res.end(); });
        } else {
            // MP4/WebM: serve directly with range requests
            // The moov atom may be at the end; browser fetches it via Range and updates duration
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
                res.writeHead(200, { 'Content-Length': total, 'Content-Type': mime, 'Accept-Ranges': 'bytes' });
                const stream = file.createReadStream();
                stream.on('error', err => { console.error('Read stream error:', err.message); res.end(); });
                stream.pipe(res);
            }
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
        const videoFiles = torrent.files
            .map((f, i) => ({ index: i, name: f.name, size: f.length }))
            .filter(f => VIDEO_EXT.includes(extname(f.name).toLowerCase()));
        res.json({
            name:          torrent.name,
            files:         torrent.files.map((f, i) => ({ index: i, name: f.name, size: f.length })),
            videoFiles,
            peers:         torrent.numPeers,
            progress:      torrent.progress,
            downloadSpeed: torrent.downloadSpeed,
            uploadSpeed:   torrent.uploadSpeed,
            downloaded:    torrent.downloaded,
            timeRemaining: torrent.timeRemaining,
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

        const ext = file ? extname(file.name).toLowerCase() : '';
        if (file && (ext === '.mp4' || ext === '.webm')) {
            // Download first 4MB (video start) and last 2MB (moov atom) in parallel
            const headEnd  = Math.min(4 * 1024 * 1024, file.length - 1);
            const tailStart = Math.max(0, file.length - 2 * 1024 * 1024);

            await Promise.all([
                new Promise(resolve => {
                    const head = file.createReadStream({ start: 0, end: headEnd });
                    head.on('data', () => {}); head.on('end', resolve); head.on('error', resolve);
                    setTimeout(resolve, 8000);
                }),
                new Promise(resolve => {
                    const tail = file.createReadStream({ start: tailStart, end: file.length - 1 });
                    tail.on('data', () => {}); tail.on('end', resolve); tail.on('error', resolve);
                    setTimeout(resolve, 10000);
                }),
            ]);
        }

        res.json({
            ok:           true,
            name:         torrent.name,
            file:         file?.name || torrent.name,
            size:         file?.length || 0,
            peers:        torrent.numPeers,
            needsRemux:   file ? needsTranscode(file.name) : false,
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

app.get('/stop', (req, res) => {
    const { magnet } = req.query;
    if (!magnet) return res.status(400).json({ error: 'magnet required' });
    const torrent = findExisting(decodeURIComponent(magnet));
    if (torrent) {
        console.log(`Stopping and removing: ${torrent.name}`);
        torrent.destroy({ destroyStore: true });
        lastAccessed.delete(torrent.infoHash);
    }
    res.json({ ok: true });
});

app.get('/health', (_, res) => res.json({ ok: true, torrents: client.torrents.length }));

app.listen(9090, () => console.log('Stream server on :9090'));
