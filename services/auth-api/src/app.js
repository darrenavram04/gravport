const express = require('express');
const helmet = require('helmet');
const authRoutes   = require('./routes/auth');
const forgotRoutes = require('./routes/forgot');

const app = express();

app.disable('x-powered-by');
app.use(helmet({
  contentSecurityPolicy: false,
}));
app.use(express.json({ limit: '32kb' }));

app.get('/health', (_req, res) => {
  res.status(200).json({
    status: 'ok',
  });
});

app.use('/v1/auth', authRoutes);
app.use('/v1/auth', forgotRoutes);

app.use((req, res) => {
  res.status(404).json({
    error: {
      message: 'Route Auth API tidak ditemukan.',
    },
  });
});

app.use((error, _req, res, _next) => {
  console.error('[auth-api]', error);

  res.status(500).json({
    error: {
      message: 'Auth API mengalami gangguan internal.',
    },
  });
});

module.exports = app;
