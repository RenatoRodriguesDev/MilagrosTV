import express from 'express';
import cors    from 'cors';
import WebTorrent from 'webtorrent';
import { extname, join } from 'path';
import { spawn, execFile } from 'child_process';
import { existsSync }      from 'fs';
import { Transform }       from 'stream';

const TRANSCODE_EXT = new Set(['.mkv', '.avi', '.mov', '.ts']);

// Get video duration via ffprobe (fast — reads container header only)
function getFileDuration(filePath) {
    return new Promise(resolve => {
        execFile('ffprobe', [
            '-v', 'quiet', '-show_entries', 'format=duration',
            '-of', 'csv=p=0', filePath
        ], { timeout: 4000 }, (err, stdout) => resolve(parseFloat(stdout?.trim()) || 0));
    });
}

// Patch duration fields (mvhd + all tkhd boxes) in fragmented MP4 stream
function patchMoovDuration(durationSecs) {
    if (!durationSecs || durationSecs <= 0) return null;
    let buf = Buffer.alloc(0);
    let done = false;

    function patchBox(name, buf, timescaleOff, durationOff, durationOff64) {
        let pos = 0;
        let count = 0;
        while (true) {
            const idx = buf.indexOf(name, pos);
            if (idx < 0) break;
            const ver = buf[idx + 4];
            if (ver === 0) {
                const ts = buf.readUInt32BE(idx + timescaleOff);
                if (ts > 0) {
                    const d = Math.min(Math.round(durationSecs * ts), 0xFFFFFFFF);
                    buf.writeUInt32BE(d, idx + durationOff);
                    count++;
                }
            } else if (ver === 1 && durationOff64 !== null) {
                const ts = buf.readUInt32BE(idx + timescaleOff + 8); // timescale shifts by 8 in v1
                if (ts > 0) {
                    const d = BigInt(Math.round(durationSecs * ts));
                    buf.writeBigUInt64BE(d, idx + durationOff64);
                    count++;
                }
            }
            pos = idx + 4;
        }
        return count;
    }

    return new Transform({
        transform(chunk, _enc, cb) {
            if (done) { cb(null, chunk); return; }
            buf = Buffer.concat([buf, chunk]);

            // Wait until we have the full moov (signalled by first 'moof' appearing after 'moov')
            const moofIdx = buf.indexOf('moof');
            if (moofIdx > 0) {
                // Patch mvhd: timescale@+16, duration@+20 (v0); timescale@+24, duration@+28 (v1)
                const mvhd = patchBox('mvhd', buf, 16, 20, 28);
                // Patch tkhd: no timescale field — uses mvhd timescale; duration@+24 (v0)
                // tkhd v0: version(1)+flags(3)+ctime(4)+mtime(4)+trackid(4)+reserved(4)+duration(4)
                let tkhdPos = 0;
                while (true) {
                    const i = buf.indexOf('tkhd', tkhdPos);
                    if (i < 0) break;
                    if (buf[i + 4] === 0) {
                        const mvhdI = buf.indexOf('mvhd');
                        if (mvhdI >= 0) {
                            const ts = buf.readUInt32BE(mvhdI + 16);
                            if (ts > 0) buf.writeUInt32BE(Math.min(Math.round(durationSecs * ts), 0xFFFFFFFF), i + 24);
                        }
                    }
                    tkhdPos = i + 4;
                }
                console.log(`Moov patched: mvhd=${mvhd > 0}, dur=${durationSecs.toFixed(1)}s`);
                done = true;
                cb(null, buf); buf = Buffer.alloc(0);
            } else if (buf.length > 256 * 1024) {
                console.log('Moov patch: gave up (no moof found in 256KB)');
                done = true; cb(null, buf); buf = Buffer.alloc(0);
            } else {
                cb();
            }
        },
        flush(cb) { if (buf.length) cb(null, buf); else cb(); }
    });
}

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

const urlToHash     = new Map(); // .torrent URL → infoHash
const inFlight      = new Map(); // magnetOrUrl  → Promise<torrent>
const lastAccessed  = new Map(); // infoHash     → Date.now()
const durationCache = new Map(); // infoHash     → duration (seconds)

const MAX_IDLE_MS  = 2 * 60 * 60 * 1000; // 2 horas sem acesso → apagar
const MAX_TORRENTS = 3;                   // máximo de torrents em simultâneo

function evictOldTorrents() {
    const now = Date.now();
    for (const torrent of [...client.torrents]) {
        const idle = now - (lastAccessed.get(torrent.infoHash) ?? 0);
        if (idle > MAX_IDLE_MS) {
            console.log(`Evicting idle torrent: ${torrent.name}`);
            const h = torrent.infoHash;
            torrent.destroy({ destroyStore: true });
            lastAccessed.delete(h);
            durationCache.delete(h);
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
            const fullTranscode = req.query.transcode === '1';
            const seekSec       = parseFloat(req.query.ss) || 0;
            const videoCodec    = fullTranscode
                ? ['-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '28']
                : ['-c:v', 'copy'];

            const duration = durationCache.get(torrent.infoHash) || 0;
            console.log(`${fullTranscode ? 'Transcoding' : 'Remuxing'}: ${file.name} ss=${seekSec}s dur=${duration.toFixed(1)}s`);

            // Try disk path for seeking support; fall back to stdin pipe
            const diskCandidates = [join('/tmp/torrents', file.path), join('/tmp/torrents', file.name)];
            const diskPath = diskCandidates.find(p => existsSync(p));

            res.writeHead(200, { 'Content-Type': 'video/mp4' });

            let ffArgs, ffStdio, src;
            if (diskPath) {
                // Disk path: fast startup, seeking support, no stdin overhead
                const seekArgs = seekSec > 0 ? ['-ss', String(seekSec)] : [];
                ffArgs  = ['-fflags', 'nobuffer', ...seekArgs, '-i', diskPath, ...videoCodec];
                ffStdio = ['ignore', 'pipe', 'pipe'];
            } else {
                // Stdin pipe fallback: slower startup, no seeking
                ffArgs  = ['-fflags', 'nobuffer', '-i', 'pipe:0', ...videoCodec];
                ffStdio = ['pipe', 'pipe', 'pipe'];
            }

            const ff = spawn('ffmpeg', [...ffArgs,
                '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
                '-f', 'mp4', '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
                '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
                '-f', 'mp4', '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
                'pipe:1'
            ], { stdio: ffStdio });

            if (ffStdio[0] === 'pipe') {
                src = file.createReadStream();
                src.pipe(ff.stdin);
            }

            // Patch moov with cached duration (adjusting for seek offset)
            const patchDur = duration > 0 ? duration - seekSec : 0;
            const patcher  = patchMoovDuration(patchDur);
            if (patcher) { ff.stdout.pipe(patcher).pipe(res); }
            else          { ff.stdout.pipe(res); }

            ff.stderr.on('data', () => {});
            req.on('close', () => { ff.kill('SIGKILL'); if (src) src.destroy(); });
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

        if (file) {
            // Pre-download first 2MB so ffprobe can read headers and video starts instantly
            await new Promise(resolve => {
                const head = file.createReadStream({ start: 0, end: Math.min(2 * 1024 * 1024, file.length - 1) });
                head.on('data', () => {}); head.on('end', resolve); head.on('error', resolve);
                setTimeout(resolve, 10000);
            });

            // For MP4/WebM: also pre-fetch tail for moov atom
            const ext = extname(file.name).toLowerCase();
            if (ext === '.mp4' || ext === '.webm') {
                const tailStart = Math.max(0, file.length - 2 * 1024 * 1024);
                await new Promise(resolve => {
                    const tail = file.createReadStream({ start: tailStart, end: file.length - 1 });
                    tail.on('data', () => {}); tail.on('end', resolve); tail.on('error', resolve);
                    setTimeout(resolve, 8000);
                });
            }
        }

        // Get duration and cache it (file now has enough data for ffprobe to succeed)
        let duration = durationCache.get(torrent.infoHash) || 0;
        if (!duration && file) {
            const candidates = [join('/tmp/torrents', file.path), join('/tmp/torrents', file.name)];
            const diskPath   = candidates.find(p => existsSync(p));
            if (diskPath) {
                duration = await getFileDuration(diskPath);
                if (duration > 0) {
                    durationCache.set(torrent.infoHash, duration);
                    console.log(`Duration cached: ${duration.toFixed(1)}s for ${torrent.infoHash}`);
                }
            }
        }

        const videoFiles = torrent.files
            .map((f, i) => ({ index: i, name: f.name, size: f.length }))
            .filter(f => VIDEO_EXT.includes(extname(f.name).toLowerCase()));

        const canSeek = (() => {
            const candidates = file ? [join('/tmp/torrents', file.path), join('/tmp/torrents', file.name)] : [];
            return candidates.some(p => existsSync(p));
        })();

        res.json({
            ok:         true,
            name:       torrent.name,
            file:       file?.name || torrent.name,
            size:       file?.length || 0,
            peers:      torrent.numPeers,
            needsRemux: file ? needsTranscode(file.name) : false,
            duration,
            videoFiles,
            canSeek,
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
        const h = torrent.infoHash;
        torrent.destroy({ destroyStore: true });
        lastAccessed.delete(h);
        durationCache.delete(h);
    }
    res.json({ ok: true });
});

app.get('/health', (_, res) => res.json({ ok: true, torrents: client.torrents.length }));

app.listen(9090, () => console.log('Stream server on :9090'));
