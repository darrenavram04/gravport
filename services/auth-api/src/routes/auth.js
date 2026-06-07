const express = require('express');
const bcrypt = require('bcryptjs');
const rateLimit = require('express-rate-limit');
const { pool } = require('../db');
const config = require('../config');
const { validateLoginPayload, validateSignupPayload } = require('../validators');

const router = express.Router();

const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 8,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: { message: 'Terlalu banyak percobaan login. Coba lagi nanti.' } },
});

const signupLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: { message: 'Terlalu banyak percobaan pendaftaran. Coba lagi nanti.' } },
});

if (config.server.sharedKey !== '') {
  router.use((req, res, next) => {
    const incomingKey = String(req.get('x-auth-client-key') || '');
    if (incomingKey !== config.server.sharedKey) {
      return res.status(401).json({ error: { message: 'Akses Auth API ditolak.' } });
    }
    return next();
  });
}

// ── Helper: resolve role from accounts.role column ────────────────────────────
async function resolvePrimaryRole(client, accId) {
  const result = await client.query(
    `SELECT role FROM ${config.database.schema}.accounts WHERE acc_id = $1 LIMIT 1`,
    [accId]
  );
  return result.rows[0]?.role || config.auth.defaultRole;
}

// ── Helper: ensure account exists or create it ────────────────────────────────
async function ensureAccount(client, email, fullName, passwordHash) {
  const existing = await client.query(
    `SELECT acc_id FROM ${config.database.schema}.accounts WHERE acc_email = $1 LIMIT 1`,
    [email]
  );
  if (existing.rows[0]) return existing.rows[0].acc_id;

  const inserted = await client.query(
    `INSERT INTO ${config.database.schema}.accounts
       (acc_name, acc_email, acc_password, is_active, role, created_at)
     VALUES ($1, $2, $3, TRUE, $4, NOW())
     RETURNING acc_id`,
    [String(fullName || email).substring(0, 20), email, passwordHash, config.auth.defaultRole]
  );
  return inserted.rows[0].acc_id;
}

// POST /v1/auth/login
router.post('/login', loginLimiter, async (req, res, next) => {
  const validation = validateLoginPayload(req.body || {});
  if (!validation.ok) {
    return res.status(422).json({ error: { message: validation.message } });
  }

  const { email, password } = validation.data;
  const client = await pool.connect();

  try {
    const userResult = await client.query(
      `SELECT acc_id AS id, acc_email AS email, acc_name AS full_name,
              acc_password AS password_hash, is_active
       FROM ${config.database.schema}.accounts
       WHERE acc_email = $1
       LIMIT 1`,
      [email]
    );

    const user = userResult.rows[0];
    if (!user || !user.is_active) {
      return res.status(401).json({ error: { message: 'Invalid credentials.' } });
    }

    const passwordMatches = await bcrypt.compare(password, user.password_hash);
    if (!passwordMatches) {
      return res.status(401).json({ error: { message: 'Invalid credentials.' } });
    }

    const roleName = await resolvePrimaryRole(client, Number(user.id));

    await client.query(
      `UPDATE ${config.database.schema}.accounts SET is_logged_in = true WHERE acc_id = $1`,
      [Number(user.id)]
    );

    return res.status(200).json({
      data: {
        id:        Number(user.id),
        email:     String(user.email),
        full_name: String(user.full_name || ''),
        role:      roleName,
      },
    });
  } catch (error) {
    return next(error);
  } finally {
    client.release();
  }
});

// POST /v1/auth/logout
router.post('/logout', async (req, res, next) => {
  const userId = parseInt(req.body?.user_id, 10);
  if (!userId || userId <= 0) {
    return res.status(422).json({ error: { message: 'user_id wajib diisi.' } });
  }

  const client = await pool.connect();
  try {
    await client.query(
      `UPDATE ${config.database.schema}.accounts SET is_logged_in = false WHERE acc_id = $1`,
      [userId]
    );
    return res.status(200).json({ data: { ok: true } });
  } catch (error) {
    return next(error);
  } finally {
    client.release();
  }
});

// POST /v1/auth/signup
router.post('/signup', signupLimiter, async (req, res, next) => {
  const validation = validateSignupPayload(req.body || {});
  if (!validation.ok) {
    return res.status(422).json({ error: { message: validation.message } });
  }

  const { fullName, email, password } = validation.data;
  const requestedRole = ['user', 'admin'].includes(String(req.body?.requested_role || ''))
    ? String(req.body.requested_role)
    : 'user';

  const client = await pool.connect();

  try {
    await client.query('BEGIN');

    const passwordHash = await bcrypt.hash(password, config.auth.bcryptRounds);
    const accName      = String(fullName || email).substring(0, 20);

    const insertResult = await client.query(
      `INSERT INTO ${config.database.schema}.accounts
         (acc_name, acc_email, acc_password, is_active, role, created_at)
       VALUES ($1, $2, $3, TRUE, $4, NOW())
       RETURNING acc_id AS id, acc_email AS email, acc_name AS full_name`,
      [accName, email, passwordHash, config.auth.defaultRole]
    );

    const user = insertResult.rows[0];

    await client.query('COMMIT');

    sendWelcomeEmail(user, requestedRole).catch(err =>
      console.warn('[mailer] welcome email failed:', err.message)
    );

    return res.status(201).json({
      data: {
        id:             Number(user.id),
        email:          String(user.email),
        full_name:      String(user.full_name || ''),
        role:           config.auth.defaultRole,
        requested_role: requestedRole,
      },
    });
  } catch (error) {
    await client.query('ROLLBACK');
    if (error && error.code === '23505') {
      return res.status(409).json({ error: { message: 'Email sudah terdaftar.' } });
    }
    return next(error);
  } finally {
    client.release();
  }
});

// POST /v1/auth/admin/create-user
router.post('/admin/create-user', async (req, res, next) => {
  const { email, full_name, password_hash } = req.body || {};

  if (!email || !full_name || !password_hash) {
    return res.status(422).json({
      error: { message: 'email, full_name, dan password_hash wajib diisi.' },
    });
  }

  const client = await pool.connect();
  try {
    await client.query('BEGIN');

    const insertResult = await client.query(
      `INSERT INTO ${config.database.schema}.accounts
         (acc_name, acc_email, acc_password, is_active, role, created_at)
       VALUES ($1, $2, $3, TRUE, $4, NOW())
       RETURNING acc_id AS id, acc_email AS email, acc_name AS full_name`,
      [
        String(full_name).substring(0, 20).trim(),
        String(email).toLowerCase().trim(),
        String(password_hash),
        'user',
      ]
    );

    const user = insertResult.rows[0];
    await client.query('COMMIT');

    return res.status(201).json({
      data: {
        id:        Number(user.id),
        email:     String(user.email),
        full_name: String(user.full_name),
      },
    });
  } catch (error) {
    await client.query('ROLLBACK');
    if (error && error.code === '23505') {
      return res.status(409).json({ error: { message: 'Email sudah terdaftar.' } });
    }
    return next(error);
  } finally {
    client.release();
  }
});

// ── Email helpers ─────────────────────────────────────────────────────────────
async function sendWelcomeEmail(user, requestedRole) {
  const name = user.full_name || user.email;
  const { sendMail } = require('../mailer');

  const html = requestedRole === 'admin'
    ? `<p>Halo <strong>${name}</strong>, akun GravPort Anda sudah aktif. Permintaan akses Admin sedang ditinjau.</p>`
    : `<p>Halo <strong>${name}</strong>, selamat datang di GravPort! Akun Anda sudah aktif.</p>`;

  await sendMail({
    to: user.email,
    subject: 'GravPort — Akun Anda Sudah Aktif',
    html,
  });
}

module.exports = router;
