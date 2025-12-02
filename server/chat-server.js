const fs = require('fs');
const ini = require('ini');
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');

// Read DB config from project's database.ini
const iniPath = __dirname + '/../includes/database.ini';
if (!fs.existsSync(iniPath)) {
    console.error('database.ini not found at', iniPath);
    process.exit(1);
}
let cfg = ini.parse(fs.readFileSync(iniPath, 'utf-8'));
// database.ini may contain a [database] section
if (cfg.database) cfg = cfg.database;

// helper to read and strip quotes
function getCfg(key, fallback) {
    let v = cfg[key] !== undefined ? cfg[key] : (cfg[key.toLowerCase()] !== undefined ? cfg[key.toLowerCase()] : fallback);
    if (typeof v === 'string') {
        v = v.trim();
        // strip surrounding quotes if present
        if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) {
            v = v.substring(1, v.length - 1);
        }
    }
    return v;
}

const dbHost = getCfg('host', 'localhost');
const dbPort = parseInt(getCfg('port', '3306')) || 3306;
const dbName = getCfg('dbname', getCfg('database', 'SchoolConnectDB'));
const dbUser = getCfg('username', getCfg('user', 'root'));
const dbPass = getCfg('password', '');
const dbCharset = getCfg('charset', 'utf8mb4');

const pool = mysql.createPool({
    host: dbHost,
    port: dbPort,
    user: dbUser,
    password: dbPass,
    database: dbName,
    waitForConnections: true,
    connectionLimit: 10,
    charset: dbCharset
});

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });
const net = require('net');

// in-memory map: userId -> set of socket ids
const userSockets = new Map();

io.on('connection', (socket) => {
    const userId = socket.handshake.query.userId;
    if (!userId) {
        console.warn('socket connection without userId');
        return;
    }
    // track socket
    const set = userSockets.get(userId) || new Set();
    set.add(socket.id);
    userSockets.set(userId, set);
    console.log('user connected', userId, socket.id);

    socket.on('private_message', async (payload) => {
        // payload: { to, message }
        const to = payload && (payload.to || payload.recipient || payload.recipient_id);
        const message = payload && (payload.message || payload.message_text || payload.text);
        console.log('private_message recv from', userId, 'payload=', payload);
        if (!to || !message) return;
        try {
            const conn = await pool.getConnection();
            try {
                const [result] = await conn.execute(
                    'INSERT INTO DirectMessages (sender_id, recipient_id, message_text) VALUES (?, ?, ?)',
                    [userId, to, message]
                );
                const insertId = result.insertId;
                // fetch saved row
                const [rows] = await conn.execute('SELECT * FROM DirectMessages WHERE message_id = ?', [insertId]);
                const saved = rows[0];
                console.log('saved message id', insertId, 'saved=', saved);

                // emit to recipient if online
                const recipientSockets = userSockets.get(String(to));
                if (recipientSockets) {
                    recipientSockets.forEach(sid => {
                        io.to(sid).emit('private_message', saved);
                    });
                }

                // emit back to sender to confirm
                const senderSockets = userSockets.get(String(userId));
                if (senderSockets) {
                    senderSockets.forEach(sid => io.to(sid).emit('private_message', saved));
                }
            } finally {
                conn.release();
            }
        } catch (err) {
            console.error('DB error saving message', err);
        }
    });

    socket.on('disconnect', () => {
        const set = userSockets.get(userId);
        if (set) {
            set.delete(socket.id);
            if (set.size === 0) userSockets.delete(userId);
        }
        console.log('disconnect', userId, socket.id);
    });
});

// Find an available port in a safe high range so we avoid conflicts
async function findFreePort(start, end) {
    for (let p = start; p <= end; p++) {
        try {
            await new Promise((resolve, reject) => {
                const tester = net.createServer()
                    .once('error', err => {
                        tester.close();
                        reject(err);
                    })
                    .once('listening', () => {
                        tester.close();
                        resolve();
                    })
                    .listen(p);
            });
            return p;
        } catch (e) {
            // try next port
        }
    }
    return null;
}

(async () => {
    const start = parseInt(process.env.PORT || '45000', 10) || 45000;
    const end = start + 10;
    const port = await findFreePort(start, end);
    if (!port) {
        console.error('No free port found in range', start, '-', end);
        process.exit(1);
    }
    server.listen(port, () => console.log('Chat server listening on', port));
})();
