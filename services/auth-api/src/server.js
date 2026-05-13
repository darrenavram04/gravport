const app = require('./app');
const config = require('./config');
const { pool } = require('./db');

async function boot() {
  try {
    await pool.query('SELECT 1');

    app.listen(config.server.port, config.server.host, () => {
      console.log(`[auth-api] listening on http://${config.server.host}:${config.server.port}`);
    });
  } catch (error) {
    console.error('[auth-api] failed to start', error);
    process.exit(1);
  }
}

boot();
