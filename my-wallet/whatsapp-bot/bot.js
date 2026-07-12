const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcodeTerminal = require('qrcode-terminal');
const QRCode = require('qrcode');
const axios = require('axios');
const path = require('path');
const http = require('http');
const fs = require('fs');
require('dotenv').config();

const API_URL = process.env.API_URL || 'http://127.0.0.1:8000';

console.log(`[Bot] Initializing WhatsApp bot with API URL: ${API_URL}`);

// Initialize client with local authentication to save session
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: path.join(__dirname, '.wwebjs_auth')
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ]
    }
});

// Event: QR Code
client.on('qr', async (qr) => {
    console.log('[Bot] QR Code received. Scan it to log in:');
    qrcodeTerminal.generate(qr, { small: true });

    try {
        // Generate base64 data URI of QR code image
        const qrImageBase64 = await QRCode.toDataURL(qr);

        // Send to Laravel API
        await axios.post(`${API_URL}/api/whatsapp/qr`, {
            qr: qrImageBase64
        });
        console.log('[Bot] Sent QR Code image to Laravel API.');
    } catch (err) {
        console.error('[Bot] Failed to send QR Code to Laravel:', err.message);
    }
});

// Event: Ready
client.on('ready', async () => {
    console.log('[Bot] Client is ready!');
    
    const info = client.info;
    const userNumber = info.wid.user;
    const userName = info.pushname || 'Owner';

    try {
        await axios.post(`${API_URL}/api/whatsapp/status`, {
            status: 'connected',
            user: `${userName} (${userNumber})`
        });
        console.log('[Bot] Connection status updated to Laravel.');
    } catch (err) {
        console.error('[Bot] Failed to update status to Laravel:', err.message);
    }
});

// Event: Disconnected
client.on('disconnected', async (reason) => {
    console.log('[Bot] Client was logged out:', reason);
    try {
        await axios.post(`${API_URL}/api/whatsapp/status`, {
            status: 'disconnected'
        });
        console.log('[Bot] Disconnect status sent to Laravel.');
    } catch (err) {
        console.error('[Bot] Failed to send disconnect status to Laravel:', err.message);
    }
});

// Event: Authenticated (not fully ready, but logged in)
client.on('authenticated', () => {
    console.log('[Bot] Authenticated successfully.');
});

client.on('auth_failure', (msg) => {
    console.error('[Bot] Authentication failure:', msg);
});

// Event: Message Created (runs for both incoming and self-sent messages)
client.on('message_create', async (msg) => {
    const text = msg.body;
    
    // Only process command messages starting with !
    if (text && text.startsWith('!')) {
        console.log(`[Bot] Received command: "${text}" from ${msg.from}`);
        
        try {
            // Forward command to Laravel API
            const response = await axios.post(`${API_URL}/api/whatsapp/message`, {
                message: text,
                sender: msg.from
            });
            
            if (response.data && response.data.reply) {
                await msg.reply(response.data.reply);
                console.log(`[Bot] Replied: "${response.data.reply.split('\n')[0]}..."`);
            }
        } catch (err) {
            console.error('[Bot] Error sending message to Laravel API:', err.message);
            await msg.reply('❌ Gagal memproses perintah. Terjadi masalah koneksi ke server.');
        }
    }
});

// Start Client
client.initialize();

// Start Control Server to handle disconnect command from Laravel
const server = http.createServer(async (req, res) => {
    if (req.url === '/disconnect' && req.method === 'POST') {
        console.log('[Bot] Disconnect request received from Laravel.');
        try {
            // Attempt clean logout
            await client.logout();
            console.log('[Bot] Logout successful.');
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: true, message: 'Logged out successfully' }));
        } catch (err) {
            console.error('[Bot] Logout failed or client was not logged in. Destroying client state:', err.message);
            try {
                await client.destroy();
            } catch (destroyErr) {
                console.error('[Bot] Error destroying client:', destroyErr.message);
            }
            
            // Clean up auth data path if exists
            const authPath = path.join(__dirname, '.wwebjs_auth');
            if (fs.existsSync(authPath)) {
                try {
                    fs.rmSync(authPath, { recursive: true, force: true });
                    console.log('[Bot] Manually cleared auth folder.');
                } catch (fsErr) {
                    console.error('[Bot] Failed to delete auth folder:', fsErr.message);
                }
            }
            
            // Re-initialize client
            console.log('[Bot] Re-initializing WhatsApp client...');
            client.initialize();
            
            // Update status to Laravel
            try {
                await axios.post(`${API_URL}/api/whatsapp/status`, {
                    status: 'disconnected'
                });
            } catch (apiErr) {
                console.error('[Bot] Failed to send status to Laravel:', apiErr.message);
            }

            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: true, message: 'Client reset and logged out manually' }));
        }
    } else {
        res.writeHead(404, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Not Found' }));
    }
});

const BOT_PORT = process.env.BOT_PORT || 8001;
server.listen(BOT_PORT, () => {
    console.log(`[Bot] Control server listening on port ${BOT_PORT}`);
});
