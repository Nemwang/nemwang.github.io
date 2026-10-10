require('dotenv').config();

const express = require('express');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const db = require('./db');

const app = express();
const PORT = process.env.PORT || 3000;
const isProduction = process.env.NODE_ENV === 'production';

const allowedOrigins = (process.env.ALLOWED_ORIGINS || 'https://nemwang.github.io')
  .split(',')
  .map((origin) => origin.trim());
const localOrigin = /^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/;

// Render sits behind a proxy; this makes req.ip the visitor's real address.
app.set('trust proxy', 1);

app.use(cors({
  origin(origin, callback) {
    // No origin = not a browser page (curl, health checks). "null" = a page
    // opened straight from disk, which we only accept while developing.
    const allowed = !origin
      || allowedOrigins.includes(origin)
      || localOrigin.test(origin)
      || (origin === 'null' && !isProduction);
    callback(null, allowed);
  },
}));
app.use(express.json({ limit: '20kb' }));
app.use(express.urlencoded({ extended: false, limit: '20kb' }));

app.get('/', (req, res) => {
  res.json({ status: 'ok' });
});

const contactLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 5,
  message: { error: 'Too many messages, please try again later.' },
});

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

app.post('/api/contact', contactLimiter, async (req, res) => {
  const body = req.body || {};
  const name = String(body.name || '').trim();
  const email = String(body.email || '').trim();
  const subject = String(body.subject || '').trim();
  const message = String(body.message || '').trim();

  if (!name || name.length > 100) {
    return res.status(400).json({ error: 'Name is required (max 100 characters).' });
  }
  if (email && (email.length > 200 || !emailPattern.test(email))) {
    return res.status(400).json({ error: 'Email address is not valid.' });
  }
  if (subject.length > 200) {
    return res.status(400).json({ error: 'Subject is too long (max 200 characters).' });
  }
  if (!message || message.length > 5000) {
    return res.status(400).json({ error: 'Message is required (max 5000 characters).' });
  }

  try {
    await db.query(
      'INSERT INTO messages (name, email, subject, message) VALUES ($1, $2, $3, $4)',
      [name, email || null, subject || null, message]
    );
    res.status(201).json({ ok: true });
  } catch (err) {
    console.error('Could not save message:', err);
    res.status(500).json({ error: 'Could not save your message.' });
  }
});

db.connect()
  .then(() => {
    app.listen(PORT, () => {
      console.log(`API listening on http://localhost:${PORT}`);
    });
  })
  .catch((err) => {
    console.error('Could not connect to the database:', err);
    process.exit(1);
  });
