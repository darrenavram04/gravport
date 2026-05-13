const config = require('./config');

function normalizeEmail(value) {
  return String(value || '').trim().toLowerCase();
}

function normalizeName(value) {
  return String(value || '').trim().replace(/\s+/g, ' ');
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validateSignupPayload(payload) {
  const fullName = normalizeName(payload.full_name);
  const email = normalizeEmail(payload.email);
  const password = String(payload.password || '');
  const passwordConfirmation = String(payload.password_confirmation || '');

  if (fullName.length < 3 || fullName.length > 120) {
    return { ok: false, message: 'Nama lengkap harus 3 sampai 120 karakter.' };
  }

  if (!isValidEmail(email) || email.length > 160) {
    return { ok: false, message: 'Email tidak valid.' };
  }

  if (!config.auth.passwordRegex.test(password)) {
    return { ok: false, message: 'Password harus 12-72 karakter dan memuat huruf besar, huruf kecil, angka, serta simbol.' };
  }

  if (password !== passwordConfirmation) {
    return { ok: false, message: 'Konfirmasi password harus sama dengan password.' };
  }

  return {
    ok: true,
    data: {
      fullName,
      email,
      password,
    },
  };
}

function validateLoginPayload(payload) {
  const email = normalizeEmail(payload.email);
  const password = String(payload.password || '');

  if (!isValidEmail(email) || password === '') {
    return { ok: false, message: 'Email dan password wajib diisi.' };
  }

  return {
    ok: true,
    data: {
      email,
      password,
    },
  };
}

module.exports = {
  validateSignupPayload,
  validateLoginPayload,
};
