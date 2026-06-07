const express = require('express');
const crypto  = require('crypto');
const bcrypt  = require('bcryptjs');
const rateLimit = require('express-rate-limit');
const { pool } = require('../db');
const config  = require('../config');
const { sendMail } = require('../mailer');

const router = express.Router();

const forgotLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: { message: 'Terlalu banyak permintaan reset. Coba lagi nanti.' } },
});

const otpLimiter = rateLimit({
  windowMs: 5 * 60 * 1000,
  max: 3,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: { message: 'Terlalu banyak permintaan OTP. Coba lagi nanti.' } },
});

function emailShell(bodyContent) {
  return `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#060a1e;font-family:'Manrope',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:linear-gradient(160deg,#07093d 0%,#060a28 45%,#040612 100%);min-height:100vh;">
  <tr><td align="center" style="padding:40px 16px;">
    <table width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;">
      <!-- HEADER -->
      <tr><td style="padding-bottom:28px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td>
              <span style="font-family:'Manrope',Arial,sans-serif;font-size:13px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,191,116,.85);">GravPort</span>
              <span style="font-family:'Manrope',Arial,sans-serif;font-size:13px;font-weight:400;color:rgba(255,255,255,.3);margin-left:8px;">Geoportal Gravitasi</span>
            </td>
          </tr>
        </table>
      </td></tr>
      <!-- CARD -->
      <tr><td style="background:linear-gradient(180deg,rgba(255,255,255,.07) 0%,rgba(255,255,255,.03) 100%);border:1px solid rgba(255,255,255,.1);border-radius:24px;overflow:hidden;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <!-- TOP ACCENT -->
          <tr><td height="3" style="background:linear-gradient(90deg,#a76025,#ffbf74 50%,#61d4ff);border-radius:24px 24px 0 0;font-size:0;line-height:0;">&nbsp;</td></tr>
          <!-- BODY -->
          <tr><td style="padding:36px 40px 40px;">
            ${bodyContent}
          </td></tr>
          <!-- FOOTER -->
          <tr><td style="padding:20px 40px 28px;border-top:1px solid rgba(255,255,255,.07);">
            <p style="margin:0;font-size:11px;color:rgba(255,255,255,.25);line-height:1.8;">
              Email ini dikirim otomatis oleh sistem GravPort. Jangan balas email ini.<br>
              &copy; ${new Date().getFullYear()} GravPort - Geoportal Data Gravitasi Jawa-Bali
            </p>
          </td></tr>
        </table>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>`;
}

// POST /v1/auth/forgot-password
router.post('/forgot-password', forgotLimiter, async (req, res, next) => {
  const email = String(req.body?.email || '').toLowerCase().trim();
  if (!email) {
    return res.status(422).json({ error: { message: 'Email wajib diisi.' } });
  }

  const client = await pool.connect();
  try {
    const result = await client.query(
      `SELECT acc_id AS id, acc_email AS email, acc_name AS full_name FROM ${config.database.schema}.accounts WHERE acc_email = $1 AND is_active = TRUE LIMIT 1`,
      [email]
    );
    const user = result.rows[0];

    if (!user) {
      return res.status(200).json({ data: { sent: true } });
    }

    await client.query(
      `UPDATE ${config.database.schema}.password_resets SET used = TRUE WHERE acc_id = $1 AND used = FALSE`,
      [user.id]
    );

    const token = crypto.randomBytes(48).toString('hex');
    const expiresAt = new Date(Date.now() + config.auth.resetTokenExpireMinutes * 60 * 1000);

    await client.query(
      `INSERT INTO ${config.database.schema}.password_resets (acc_id, token, expires_at) VALUES ($1, $2, $3)`,
      [user.id, token, expiresAt]
    );

    const resetUrl = `${config.app.baseUrl}/reset-password?token=${token}`;
    const name = user.full_name || user.email;

    const body = `
      <p style="margin:0 0 6px;font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,191,116,.7);">Reset Password</p>
      <h1 style="margin:0 0 24px;font-size:32px;font-weight:800;color:#fff;line-height:1.1;">Atur ulang<br>kata sandi Anda.</h1>
      <p style="margin:0 0 8px;font-size:15px;color:rgba(219,226,242,.85);">Halo <strong style="color:#fff;">${name}</strong>,</p>
      <p style="margin:0 0 28px;font-size:15px;color:rgba(219,226,242,.72);line-height:1.7;">
        Kami menerima permintaan reset password untuk akun Anda. Klik tombol di bawah untuk membuat password baru.
      </p>
      <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
        <tr><td style="border-radius:999px;background:linear-gradient(135deg,#fff4e7,#ffbf74);">
          <a href="${resetUrl}" style="display:inline-block;padding:14px 32px;font-family:'Manrope',Arial,sans-serif;font-size:15px;font-weight:800;color:#08111f;text-decoration:none;border-radius:999px;">
            Reset Password
          </a>
        </td></tr>
      </table>
      <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px 18px;">
        <p style="margin:0;font-size:12px;color:rgba(255,255,255,.4);line-height:1.7;">
          Link berlaku <strong style="color:rgba(255,255,255,.6);">${config.auth.resetTokenExpireMinutes} menit</strong>.
          Jika Anda tidak meminta ini, abaikan email ini. Password Anda tidak akan berubah.
        </p>
      </div>`;

    const mailTimeout = new Promise(resolve => setTimeout(() => resolve(false), 6000));
    const mailSent = await Promise.race([
      sendMail({ to: user.email, subject: 'GravPort - Reset Password Anda', html: emailShell(body) }),
      mailTimeout,
    ]);

    if (!mailSent) {
      console.error('[forgot] Reset email failed or timed out for user_id', user.id);
    }

    return res.status(200).json({ data: { sent: true } });
  } catch (err) {
    return next(err);
  } finally {
    client.release();
  }
});

// POST /v1/auth/reset-password
router.post('/reset-password', async (req, res, next) => {
  const token    = String(req.body?.token || '').trim();
  const password = String(req.body?.password || '');

  if (!token || !password) {
    return res.status(422).json({ error: { message: 'Token dan password wajib diisi.' } });
  }

  if (!config.auth.passwordRegex.test(password)) {
    return res.status(422).json({ error: { message: 'Password tidak memenuhi persyaratan keamanan.' } });
  }

  const client = await pool.connect();
  try {
    const result = await client.query(
      `SELECT id, acc_id, expires_at, used
       FROM ${config.database.schema}.password_resets
       WHERE token = $1 LIMIT 1`,
      [token]
    );
    const row = result.rows[0];

    if (!row || row.used || new Date(row.expires_at) < new Date()) {
      return res.status(400).json({ error: { message: 'Token tidak valid atau sudah kedaluwarsa.' } });
    }

    const passwordHash = await bcrypt.hash(password, config.auth.bcryptRounds);

    await client.query('BEGIN');
    await client.query(
      `UPDATE ${config.database.schema}.accounts SET acc_password = $1 WHERE acc_id = $2`,
      [passwordHash, row.acc_id]
    );
    await client.query(
      `UPDATE ${config.database.schema}.password_resets SET used = TRUE WHERE id = $1`,
      [row.id]
    );
    await client.query('COMMIT');

    return res.status(200).json({ data: { reset: true } });
  } catch (err) {
    await client.query('ROLLBACK').catch(() => {});
    return next(err);
  } finally {
    client.release();
  }
});

// POST /v1/auth/request-otp
router.post('/request-otp', otpLimiter, async (req, res, next) => {
  const userId = parseInt(req.body?.user_id, 10);
  if (!userId || isNaN(userId)) {
    return res.status(422).json({ error: { message: 'user_id wajib diisi.' } });
  }

  const client = await pool.connect();
  try {
    const userResult = await client.query(
      `SELECT acc_id AS id, acc_email AS email, acc_name AS full_name FROM ${config.database.schema}.accounts WHERE acc_id = $1 AND is_active = TRUE LIMIT 1`,
      [userId]
    );
    const user = userResult.rows[0];
    if (!user) {
      return res.status(404).json({ error: { message: 'User tidak ditemukan.' } });
    }

    await client.query(
      `UPDATE ${config.database.schema}.login_otps SET used = TRUE WHERE acc_id = $1 AND used = FALSE`,
      [userId]
    );

    const otpCode   = Math.floor(100000 + Math.random() * 900000).toString();
    const expiresAt = new Date(Date.now() + config.auth.otpExpireMinutes * 60 * 1000);

    await client.query(
      `INSERT INTO ${config.database.schema}.login_otps (acc_id, otp_code, expires_at) VALUES ($1, $2, $3)`,
      [userId, otpCode, expiresAt]
    );

    const name = user.full_name || user.email;
    const digits = otpCode.split('');

    const body = `
      <p style="margin:0 0 6px;font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:rgba(97,212,255,.7);">Verifikasi Login</p>
      <h1 style="margin:0 0 24px;font-size:32px;font-weight:800;color:#fff;line-height:1.1;">Kode masuk<br>Anda.</h1>
      <p style="margin:0 0 24px;font-size:15px;color:rgba(219,226,242,.72);">
        Halo <strong style="color:#fff;">${name}</strong>, gunakan kode berikut untuk masuk ke GravPort:
      </p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 28px;">
        <tr><td align="center">
          <div style="display:inline-block;background:rgba(97,212,255,.08);border:1px solid rgba(97,212,255,.2);border-radius:16px;padding:20px 32px;">
            <span style="font-family:'Manrope',Arial,sans-serif;font-size:44px;font-weight:800;letter-spacing:16px;color:#61d4ff;">${otpCode}</span>
          </div>
        </td></tr>
      </table>
      <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px 18px;">
        <p style="margin:0;font-size:12px;color:rgba(255,255,255,.4);line-height:1.7;">
          Kode berlaku <strong style="color:rgba(255,255,255,.6);">${config.auth.otpExpireMinutes} menit</strong>.
          Jangan bagikan kode ini ke siapapun. GravPort tidak akan pernah meminta kode ini.
        </p>
      </div>`;

    const mailTimeout = new Promise(resolve => setTimeout(() => resolve(false), 6000));
    const otpSent = await Promise.race([
      sendMail({
        to: user.email,
        subject: 'GravPort - Kode Verifikasi Login',
        html: emailShell(body),
      }),
      mailTimeout,
    ]);

    if (!otpSent) {
      console.error('[otp] OTP email failed or timed out for user_id', userId);
    }

    return res.status(200).json({ data: { sent: otpSent } });
  } catch (err) {
    return next(err);
  } finally {
    client.release();
  }
});

// POST /v1/auth/verify-otp
router.post('/verify-otp', async (req, res, next) => {
  const userId  = parseInt(req.body?.user_id, 10);
  const otpCode = String(req.body?.otp_code || '').trim();

  if (!userId || !otpCode) {
    return res.status(422).json({ error: { message: 'user_id dan otp_code wajib diisi.' } });
  }

  const client = await pool.connect();
  try {
    const result = await client.query(
      `SELECT id, expires_at, used
       FROM ${config.database.schema}.login_otps
       WHERE acc_id = $1 AND otp_code = $2 AND used = FALSE
       ORDER BY created_at DESC LIMIT 1`,
      [userId, otpCode]
    );
    const row = result.rows[0];

    if (!row || new Date(row.expires_at) < new Date()) {
      return res.status(200).json({ data: { verified: false } });
    }

    await client.query(
      `UPDATE ${config.database.schema}.login_otps SET used = TRUE WHERE id = $1`,
      [row.id]
    );

    return res.status(200).json({ data: { verified: true } });
  } catch (err) {
    return next(err);
  } finally {
    client.release();
  }
});

module.exports = router;
