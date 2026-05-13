# GravPort Auth API

Service ini memisahkan proses autentikasi user dari aplikasi PHP utama. Geoportal tetap merender halaman dengan CodeIgniter, tetapi `login` dan `signup` diproses lewat API Node.js + Express.js.

## Kenapa dipisah?

- auth logic terpusat dalam API yang lebih mudah di-hardening
- frontend PHP tidak perlu memegang query auth langsung
- role `admin` tetap bisa dikontrol ketat di sisi backend
- tidak perlu membawa frontend bundler seperti Vite hanya untuk alur login/signup

## Endpoint

- `GET /health`
- `POST /v1/auth/login`
- `POST /v1/auth/signup`

## Fitur keamanan

- bind default ke `127.0.0.1`
- password di-hash dengan bcrypt
- rate limit untuk login dan signup
- validasi payload di sisi API
- optional shared key antar PHP app dan Auth API
- role default publik selalu `user`

## Menjalankan service

1. Install Node.js 20 atau lebih baru.
2. Masuk ke folder ini.
3. Copy `.env.example` menjadi `.env`.
4. Install dependency:

```bash
npm install
```

5. Jalankan:

```bash
npm start
```

Service default akan hidup di:

```text
http://127.0.0.1:4010
```

## Konfigurasi PHP client

Tambahkan ke `.env` aplikasi PHP bila ingin override:

```ini
authApi.baseUrl = http://127.0.0.1:4010
authApi.sharedKey =
```

## Frontend dan Vite

Untuk halaman login/signup di repo ini, Vite tidak wajib. Karena aplikasi saat ini server-rendered, memakai HTML/CSS/JS biasa biasanya lebih ringan dari sisi kompleksitas dan startup. Vite baru terasa penting bila nanti frontend berkembang menjadi SPA atau modul JavaScript yang besar.
