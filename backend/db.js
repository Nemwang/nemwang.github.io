const path = require('path');

const schema = `
  CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT,
    subject TEXT,
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
  )
`;

let db;

// With DATABASE_URL set (Render) we talk to real Postgres. Without it (your
// computer) we use PGlite, which is Postgres running inside Node and saving
// to backend/data/, so the same SQL works in both places.
async function connect() {
  if (process.env.DATABASE_URL) {
    const { Pool } = require('pg');
    db = new Pool({ connectionString: process.env.DATABASE_URL });
  } else {
    const { PGlite } = await import('@electric-sql/pglite');
    db = new PGlite(path.join(__dirname, 'data'));
  }
  await db.query(schema);
}

function query(sql, params) {
  return db.query(sql, params);
}

module.exports = { connect, query };
