const nodemailer = require('nodemailer');
const config = require('./config');

let _transporter = null;

function getTransporter() {
  if (!_transporter) {
    _transporter = nodemailer.createTransport({
      host: config.smtp.host,
      port: config.smtp.port,
      secure: false,
      auth: {
        user: config.smtp.user,
        pass: config.smtp.pass,
      },
      connectionTimeout: 5000,  // 5s connect timeout
      greetingTimeout:  5000,   // 5s greeting timeout
      socketTimeout:    8000,   // 8s socket inactivity timeout
    });
  }
  return _transporter;
}

/**
 * Send an email. Returns true on success, false on failure.
 * Never throws — caller decides how to handle failure.
 * Resets transporter on error so next call gets a fresh connection.
 */
async function sendMail({ to, subject, html }) {
  if (!config.smtp.user || !config.smtp.pass) {
    console.warn('[mailer] SMTP not configured — email not sent to', to);
    return false;
  }
  try {
    const transporter = getTransporter();
    await transporter.sendMail({ from: config.smtp.from, to, subject, html });
    console.log('[mailer] Email sent to', to, '| subject:', subject);
    return true;
  } catch (err) {
    // Reset cached transporter so next call gets a fresh connection
    _transporter = null;
    console.error('[mailer] Failed to send email to', to, ':', err.message);
    return false;
  }
}

module.exports = { sendMail };
