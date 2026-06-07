const nodemailer = require('nodemailer');
const config = require('./config');

let _transporter = null;
let _resend = null;

function getResendClient() {
  if (!_resend) {
    const { Resend } = require('resend');
    _resend = new Resend(config.resend.apiKey);
  }
  return _resend;
}

function getSmtpTransporter() {
  if (!_transporter) {
    _transporter = nodemailer.createTransport({
      host: config.smtp.host,
      port: config.smtp.port,
      secure: config.smtp.port === 465,
      auth: {
        user: config.smtp.user,
        pass: config.smtp.pass,
      },
      connectionTimeout: 10000,
      greetingTimeout:  10000,
      socketTimeout:    15000,
    });
  }
  return _transporter;
}

async function sendMail({ to, subject, html }) {
  // Prefer Resend API (works via HTTPS, not blocked by firewalls)
  if (config.resend.apiKey) {
    try {
      const client = getResendClient();
      await client.emails.send({
        from: config.resend.from,
        to: [to],
        subject,
        html,
      });
      console.log('[mailer] Email sent via Resend to', to, '| subject:', subject);
      return true;
    } catch (err) {
      console.error('[mailer] Resend failed for', to, ':', err.message);
      return false;
    }
  }

  // Fallback to SMTP (for local development)
  if (!config.smtp.user || !config.smtp.pass) {
    console.warn('[mailer] No email provider configured — email not sent to', to);
    return false;
  }
  try {
    const transporter = getSmtpTransporter();
    await transporter.sendMail({ from: config.smtp.from, to, subject, html });
    console.log('[mailer] Email sent via SMTP to', to, '| subject:', subject);
    return true;
  } catch (err) {
    _transporter = null;
    console.error('[mailer] SMTP failed for', to, ':', err.message);
    return false;
  }
}

module.exports = { sendMail };
