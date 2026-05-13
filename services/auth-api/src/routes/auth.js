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
  message: {
    error: {
      message: 'Terlalu banyak percobaan login. Coba lagi nanti.',
    },
  },
});

const signupLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: {
    error: {
      message: 'Terlalu banyak percobaan pendaftaran. Coba lagi nanti.',
    },
  },
});

if (config.server.sharedKey !== '') {
  router.use((req, res, next) => {
    const incomingKey = String(req.get('x-auth-client-key') || '');

    if (incomingKey !== config.server.sharedKey) {
      return res.status(401).json({
        error: {
          message: 'Akses Auth API ditolak.',
        },
      });
    }

    return next();
  });
}

router.post('/login', loginLimiter, async (req, res, next) => {
  const validation = validateLoginPayload(req.body || {});
  if (!validation.ok) {
    return res.status(422).json({
      error: {
        message: validation.message,
      },
    });
  }

  const { email, password } = validation.data;
  const client = await pool.connect();

  try {
    const userResult = await client.query(
      `SELECT id, email, full_name, password_hash, is_active
       FROM ${config.database.schema}.users
       WHERE email = $1
       LIMIT 1`,
      [email]
    );

    const user = userResult.rows[0];
    if (!user || !user.is_active) {
      return res.status(401).json({
        error: {
          message: 'Invalid credentials.',
        },
      });
    }

    const passwordMatches = await bcrypt.compare(password, user.password_hash);
    if (!passwordMatches) {
      return res.status(401).json({
        error: {
          message: 'Invalid credentials.',
        },
      });
    }

    const roleName = await resolvePrimaryRole(client, Number(user.id));

    return res.status(200).json({
      data: {
        id: Number(user.id),
        email: String(user.email),
        full_name: String(user.full_name || ''),
        role: roleName,
      },
    });
  } catch (error) {
    return next(error);
  } finally {
    client.release();
  }
});

router.post('/signup', signupLimiter, async (req, res, next) => {
  const validation = validateSignupPayload(req.body || {});
  if (!validation.ok) {
    return res.status(422).json({
      error: {
        message: validation.message,
      },
    });
  }

  const { fullName, email, password } = validation.data;
  const client = await pool.connect();

  try {
    await client.query('BEGIN');

    const roleId = await ensureRoleId(client, config.auth.defaultRole);
    const passwordHash = await bcrypt.hash(password, config.auth.bcryptRounds);

    const insertResult = await client.query(
      `INSERT INTO ${config.database.schema}.users
        (email, password_hash, full_name, is_active, created_at, updated_at)
       VALUES ($1, $2, $3, TRUE, NOW(), NOW())
       RETURNING id, email, full_name`,
      [email, passwordHash, fullName]
    );

    const user = insertResult.rows[0];

    await client.query(
      `INSERT INTO ${config.database.schema}.user_roles (user_id, role_id)
       VALUES ($1, $2)
       ON CONFLICT DO NOTHING`,
      [Number(user.id), roleId]
    );

    await client.query('COMMIT');

    return res.status(201).json({
      data: {
        id: Number(user.id),
        email: String(user.email),
        full_name: String(user.full_name || ''),
        role: config.auth.defaultRole,
      },
    });
  } catch (error) {
    await client.query('ROLLBACK');

    if (error && error.code === '23505') {
      return res.status(409).json({
        error: {
          message: 'Email sudah terdaftar.',
        },
      });
    }

    return next(error);
  } finally {
    client.release();
  }
});

async function ensureRoleId(client, roleName) {
  const existing = await client.query(
    `SELECT id
     FROM ${config.database.schema}.roles
     WHERE name = $1
     LIMIT 1`,
    [roleName]
  );

  if (existing.rows[0]) {
    return Number(existing.rows[0].id);
  }

  const inserted = await client.query(
    `INSERT INTO ${config.database.schema}.roles (name)
     VALUES ($1)
     RETURNING id`,
    [roleName]
  );

  return Number(inserted.rows[0].id);
}

async function resolvePrimaryRole(client, userId) {
  const roleResult = await client.query(
    `SELECT r.name
     FROM ${config.database.schema}.user_roles ur
     JOIN ${config.database.schema}.roles r ON r.id = ur.role_id
     WHERE ur.user_id = $1
     ORDER BY r.name ASC
     LIMIT 1`,
    [userId]
  );

  if (roleResult.rows[0] && roleResult.rows[0].name) {
    return String(roleResult.rows[0].name);
  }

  const roleId = await ensureRoleId(client, config.auth.defaultRole);

  await client.query(
    `INSERT INTO ${config.database.schema}.user_roles (user_id, role_id)
     VALUES ($1, $2)
     ON CONFLICT DO NOTHING`,
    [userId, roleId]
  );

  return config.auth.defaultRole;
}

module.exports = router;
