const dotenv = require('dotenv');

dotenv.config();

function readString(name, fallback = '') {
  const value = process.env[name];

  return typeof value === 'string' && value.trim() !== '' ? value.trim() : fallback;
}

function readNumber(name, fallback) {
  const raw = readString(name, '');
  const parsed = Number(raw);

  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

const config = {
  server: {
    host: readString('HOST', '127.0.0.1'),
    port: readNumber('PORT', 4010),
    sharedKey: readString('AUTH_API_SHARED_KEY', ''),
  },
  database: {
    host: readString('PGHOST', '127.0.0.1'),
    port: readNumber('PGPORT', 5433),
    database: readString('PGDATABASE', 'MockUp'),
    user: readString('PGUSER', 'postgres'),
    password: readString('PGPASSWORD', ''),
    schema: readString('PGSCHEMA', 'auth'),
  },
  auth: {
    bcryptRounds: 12,
    defaultRole: 'user',
    passwordRegex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{12,72}$/,
    resetTokenExpireMinutes: 30,
    otpExpireMinutes: 5,
  },
  smtp: {
    host: readString('SMTP_HOST', 'smtp.gmail.com'),
    port: readNumber('SMTP_PORT', 587),
    user: readString('SMTP_USER', ''),
    pass: readString('SMTP_PASS', ''),
    from: readString('SMTP_FROM', 'GravPort <noreply@gravport.id>'),
  },
  resend: {
    apiKey: readString('RESEND_API_KEY', ''),
    from: readString('RESEND_FROM', 'GravPort <onboarding@resend.dev>'),
  },
  app: {
    baseUrl: readString('APP_BASE_URL', 'http://localhost/geoportal/public'),
  },
};

module.exports = config;
