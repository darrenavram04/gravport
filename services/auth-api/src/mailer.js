const nodemailer = require('nodemailer');
const config = require('./config');

let _transporter = null;

// ── Brevo HTTP API (port 443, tidak diblokir firewall) ──────────────
async function sendViaBrevo({ to, subject, html }) {
  const res = await fetch('https://api.brevo.com/v3/smtp/email', {
    method: 'POST',
    headers: {
      'api-key': config.brevo.apiKey,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      sender: { name: config.brevo.fromName, email: config.brevo.fromEmail },
      to: [{ email: to }],
      subject,
      htmlContent: html,
    }),
  });
  if (!res.ok) {
    const body = await res.text();
    throw new Error(`Brevo ${res.status}: ${body}`);
  }
}

// ── Resend HTTP API (fallback jika Brevo tidak dikonfigurasi) ────────
async function sendViaResend({ to, subject, html }) {
  const { Resend } = require('resend');
  const client = new Resend(config.resend.apiKey);
  const { error } = await client.emails.send({
    from: config.resend.from,
    to: [to],
    subject,
    html,
  });
  if (error) throw new Error(error.message);
}

// ── SMTP nodemailer (lokal/development saja) ─────────────────────────
function getSmtpTransporter() {
  if (!_transporter) {
    _transporter = nodemailer.createTransport({
      host: config.smtp.host,
      port: config.smtp.port,
      secure: config.smtp.port === 465,
      auth: { user: config.smtp.user, pass: config.smtp.pass },
      connectionTimeout: 10000,
      greetingTimeout: 10000,
      socketTimeout: 15000,
    });
  }
  return _transporter;
}

async function sendMail({ to, subject, html }) {
  // 1. Brevo — prioritas utama di production (HTTPS, bisa ke email manapun)
  if (config.brevo.apiKey && config.brevo.fromEmail) {
    try {
      await sendViaBrevo({ to, subject, html });
      console.log('[mailer] Email sent via Brevo to', to);
      return true;
    } catch (err) {
      console.error('[mailer] Brevo failed:', err.message);
      return false;
    }
  }

  // 2. Resend — fallback (test mode: hanya ke email terdaftar)
  if (config.resend.apiKey) {
    try {
      await sendViaResend({ to, subject, html });
      console.log('[mailer] Email sent via Resend to', to);
      return true;
    } catch (err) {
      console.error('[mailer] Resend failed:', err.message);
      return false;
    }
  }

  // 3. SMTP — lokal/development
  if (!config.smtp.user || !config.smtp.pass) {
    console.warn('[mailer] No email provider configured — skipping', to);
    return false;
  }
  try {
    const t = getSmtpTransporter();
    await t.sendMail({ from: config.smtp.from, to, subject, html });
    console.log('[mailer] Email sent via SMTP to', to);
    return true;
  } catch (err) {
    _transporter = null;
    console.error('[mailer] SMTP failed:', err.message);
    return false;
  }
}

module.exports = { sendMail };
