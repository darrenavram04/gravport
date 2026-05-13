// PM2 ecosystem config — Auth API service
// File: /var/www/geoportal/services/auth-api/ecosystem.config.js
// Jalankan: pm2 start ecosystem.config.js

module.exports = {
    apps: [
        {
            name: 'gravport-auth-api',
            script: 'src/server.js',
            cwd: '/var/www/geoportal/services/auth-api',
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '256M',
            env: {
                NODE_ENV: 'production',
            },
            env_file: '/var/www/geoportal/services/auth-api/.env',
            error_file: '/var/log/gravport/auth-api-error.log',
            out_file: '/var/log/gravport/auth-api-out.log',
            log_date_format: 'YYYY-MM-DD HH:mm:ss',
        },
    ],
};
