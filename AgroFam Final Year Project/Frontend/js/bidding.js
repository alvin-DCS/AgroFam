const express = require('express');
const app = express();
const bodyParser = require('body-parser');
const mysql = require('mysql'); 

app.use(bodyParser.json());


const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'registration',
});

db.connect((err) => {
    if (err) throw err;
    console.log('Database connected!');
});

const bcrypt = require('bcrypt');


app.post('/login', (req, res) => {
    const { username, password, role } = req.body;

    if (role === 'farmer') {
        
        const query = 'SELECT * FROM farmers WHERE username = ?';
        db.query(query, [username], async (err, results) => {
            if (err) {
                console.error(err);
                return res.status(500).json({ success: false, message: 'Database error' });
            }

            if (results.length > 0) {
                const farmer = results[0];

                
                const match = await bcrypt.compare(password, farmer.password);
                if (match) {
                    return res.json({ 
                        success: true, 
                        redirectUrl: '/farmer-dashboard', 
                        farmer: farmer 
                    });
                } else {
                    return res.json({ success: false, message: 'Invalid username or password' });
                }
            } else {
                return res.json({ success: false, message: 'Invalid username or password' });
            }
        });
    } else if (role === 'bidder') {
        const query = 'SELECT * FROM bidders WHERE username = ?';
        db.query(query, [username], async (err, results) => {
            if (err) {
                console.error(err);
                return res.status(500).json({ success: false, message: 'Database error' });
            }

            if (results.length > 0) {
                const bidder = results[0];

                const match = await bcrypt.compare(password, bidder.password);
                if (match) {
                    return res.json({ 
                        success: true, 
                        redirectUrl: '/bidder-dashboard', 
                        bidder: bidder 
                    });
                } else {
                    return res.json({ success: false, message: 'Invalid username or password' });
                }
            } else {
                return res.json({ success: false, message: 'Invalid username or password' });
            }
        });
    } else {
        return res.status(400).json({ success: false, message: 'Invalid role' });
    }
});
