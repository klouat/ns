const express = require('express');
const http = require('http');


const app = express();
const server = http.createServer(app);

// Initialize Socket.IO with CORS enabled
// Note: Some older ActionScript FlashSocket libraries might require Socket.io v2.x 
const io = require('socket.io')(server, {
    cors: { origin: '*' },
    transports: ['websocket']
});

// Memory stores for our test server
const messageHistory = [];
const activeUsers = new Map();

const mysql = require('mysql2/promise');

const pool = mysql.createPool({
    host: '127.0.0.1',
    user: 'root',
    database: 'ninjasage',
    password: '',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

const getRankIconId = (rankString) => {
    const rankMap = {
        'Chunin': 2,
        'Tensai Chunin': 3,
        'Jounin': 4,
        'Tensai Jounin': 5,
        'Special Jounin': 6,
        'Tensai Special Jounin': 7,
        'Ninja Tutor': 8,
        'Senior Ninja Tutor': 9,
        'Sage': 10
    };
    return rankMap[rankString] || 1; // Default to 1 (Genin)
};

// --- GLOBAL CHAT NAMESPACE ---
const globalChat = io.of('/global-chat');

globalChat.on('connection', (socket) => {
    console.log('[Global] A user connected:', socket.id);

    // Temporary player profile for the connected socket
    let playerProfile = {
        id: 0,
        name: "Unknown User",
        level: 1,
        rank: 1,
        premium: 0
    };

    // 1. Client authenticates
    socket.on('auth', async (data) => {
        console.log('[Global] Client authenticated:', data);
        playerProfile.id = data.character_id || 0;

        try {
            if (playerProfile.id > 0) {
                // Fetch real character details from DB and join users to check emblem status
                const query = `
                    SELECT c.name, c.level, c.\`rank\`, u.account_type
                    FROM characters c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ? LIMIT 1
                `;
                const [rows] = await pool.query(query, [playerProfile.id]);

                if (rows.length > 0) {
                    playerProfile.name = rows[0].name;
                    playerProfile.level = rows[0].level;
                    playerProfile.rank = getRankIconId(rows[0].rank);
                    playerProfile.premium = rows[0].account_type == 1 ? 1 : 0;
                } else {
                    playerProfile.name = "Player_" + playerProfile.id;
                }
            }
        } catch (error) {
            console.error('[Global] Database error on auth:', error);
            playerProfile.name = "Player_" + playerProfile.id;
        }

        activeUsers.set(socket.id, {
            id: playerProfile.id,
            name: playerProfile.name
        });

        // Broadcast total online change
        globalChat.emit('online-users', {
            total: activeUsers.size,
            users: Array.from(activeUsers.values())
        });
    });

    // 2. Client requests chat history and config
    socket.on('open-chatbox', () => {
        // Example custom nickname colors
        socket.emit('nickname-colors', { "999": "#ff0000" });

        // Send history
        socket.emit('history', messageHistory);
    });

    // 3. Client sends a message
    socket.on('sendMessage', (messageText) => {
        if (!messageText || messageText.trim() === "") return;

        console.log(`[Global] Message from ${playerProfile.name}: ${messageText}`);

        // The packet structure the AS3 client expects
        const messagePacket = {
            character: playerProfile,
            message: messageText
        };

        // Cache for history limit 200 strings
        messageHistory.push(messagePacket);
        if (messageHistory.length > 200) {
            messageHistory.shift();
        }

        // Broadcast to everyone in the channel
        globalChat.emit('message', messagePacket);
    });

    socket.on('disconnect', () => {
        console.log('[Global] User disconnected:', socket.id);
        if (activeUsers.has(socket.id)) {
            activeUsers.delete(socket.id);
            // Broadcast new online count
            globalChat.emit('online-users', {
                total: activeUsers.size,
                users: Array.from(activeUsers.values())
            });
        }
    });

    socket.on('error', (err) => {
        console.error('[Global] Socket error:', err);
    });
});

// --- CLAN CHAT NAMESPACE --- 
const clanChat = io.of('/clan-chat');
clanChat.on('connection', (socket) => {
    console.log('[Clan] A user connected:', socket.id);
    // Add clan-specific auth checks and emits here
});


const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Realtime Chat Server running on ws://127.0.0.1:${PORT}`);
});