# Laporan White-Box Testing
## Sistem Geoportal Gravimetri (GravPort)

| Atribut | Keterangan |
|---|---|
| Tanggal pelaksanaan awal | 8 Mei 2026 |
| Tanggal remediasi & verifikasi akhir | 10 Mei 2026 |
| Versi dokumen | 2.0 |
| Framework uji | PHPUnit 11.5 + CodeIgniter 4.6 CIUnitTestCase |
| Platform | PHP 8.2, PostgreSQL 16 + PostGIS 3.4 |
| Hasil akhir | **150 test, 435 assertions, 0 failures, 0 errors** |

---

## Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Landasan Teori](#2-landasan-teori)
3. [Strategi dan Perencanaan Pengujian](#3-strategi-dan-perencanaan-pengujian)
4. [Infrastruktur dan Konfigurasi Pengujian](#4-infrastruktur-dan-konfigurasi-pengujian)
5. [Spesifikasi dan Matriks Kasus Uji per Modul](#5-spesifikasi-dan-matriks-kasus-uji-per-modul)
6. [Defek yang Ditemukan dan Perbaikan](#6-defek-yang-ditemukan-dan-perbaikan)
7. [Rekapitulasi Hasil Pengujian](#7-rekapitulasi-hasil-pengujian)
8. [Artefak Pengujian](#8-artefak-pengujian)
9. [Rekomendasi Lanjutan](#9-rekomendasi-lanjutan)

---

## 1. Gambaran Umum Sistem

GravPort adalah aplikasi geoportal berbasis web yang dibangun menggunakan framework **PHP CodeIgniter 4** (CI4) di atas **PHP 8.2**. Sistem menyediakan layanan visualisasi, katalogisasi, dan pengelolaan data anomali gravitasi dalam dua jenis:

- **Free Air Anomaly (FAA)**: Koreksi udara bebas terhadap anomali gravitasi.
- **Complete Bouguer Anomaly (CBA)**: Koreksi Bouguer lengkap terhadap anomali gravitasi.

Masing-masing tersedia dalam dua level resolusi:

- **Level 1**: Data titik vektor hasil survei lapangan (vector point).
- **Level 2**: Data raster hasil interpolasi grid.

### 1.1 Tumpukan Teknologi

| Lapisan | Teknologi |
|---|---|
| Framework backend | CodeIgniter 4.6 |
| Bahasa pemrograman | PHP 8.2 |
| Database | PostgreSQL 16 + PostGIS 3.4 (port 5433) |
| Peta interaktif | Leaflet.js |
| Manajemen dependensi | Composer |
| Test runner | PHPUnit 11.5 |
| Autentikasi | Session-based + AuthApiClient (mock-able) |

### 1.2 Modul yang Diuji

| File | Peran dalam Sistem |
|---|---|
| `app/Controllers/Catalog.php` | Katalog dataset: pencarian, filter, pratinjau vektor/raster, unduh, impor |
| `app/Controllers/WebMap.php` | API layer peta interaktif: parsing filter spasial, SQL builder PostGIS |
| `app/Controllers/AuthController.php` | Login, logout, signup |
| `app/Filters/RoleFilter.php` | Otorisasi berbasis peran (admin / user) |
| `app/Filters/AuthFilter.php` | Autentikasi: blokir guest |
| `app/Libraries/GeoportalDatasetRegistry.php` | Registri definisi dataset dan entri katalog |
| `app/Libraries/DatasetImportService.php` | Impor paket CSV/XML dataset gravimetri |
| `app/Libraries/MetadataSubmissionService.php` | Validasi konten file unggahan metadata |

---

## 2. Landasan Teori

### 2.1 White-Box Testing

White-box testing (disebut juga *structural testing*, *glass-box testing*, atau *code-based testing*) adalah metode pengujian perangkat lunak yang merancang kasus uji berdasarkan **struktur internal kode sumber**, bukan semata spesifikasi fungsional eksternal (Pressman, 2014; Myers, Sandler & Badgett, 2011). Penguji memiliki akses penuh ke kode sehingga dapat:

- Memeriksa setiap cabang kondisi (`if`, `else`, `switch`, `ternary`, `match`).
- Menelusuri jalur eksekusi dari titik masuk hingga titik keluar fungsi.
- Menguji kondisi batas tepat pada nilai kritis.
- Memverifikasi penanganan kesalahan pada jalur internal yang tidak terekspos melalui API publik.

### 2.2 Kriteria Cakupan yang Diterapkan

#### 2.2.1 Statement Coverage (C0)

Setiap pernyataan (*statement*) kode dieksekusi minimal satu kali.

```
C0 = (pernyataan dieksekusi / total pernyataan) × 100%
```

#### 2.2.2 Branch Coverage (C1)

Setiap cabang `true` dan `false` dari setiap keputusan kondisional dieksekusi minimal satu kali. Branch coverage menjamin statement coverage: C1 ⊇ C0.

```
C1 = (cabang dieksekusi / total cabang) × 100%
```

#### 2.2.3 Condition Coverage (C2)

Setiap kondisi atomik dalam ekspresi Boolean majemuk (`A && B`, `A || B`) dievaluasi ke `true` maupun `false` secara terpisah.

#### 2.2.4 Path Coverage

Setiap jalur unik dari titik masuk ke titik keluar sebuah fungsi dieksekusi. Ini adalah kriteria terkuat; jumlah jalur dapat tumbuh eksponensial pada fungsi kompleks.

### 2.3 Teknik Perancangan Kasus Uji

#### 2.3.1 Boundary Value Analysis (BVA)

Kasus uji dibuat tepat pada nilai batas domain input: batas bawah, batas atas, satu di bawah batas bawah, dan satu di atas batas atas.

Contoh: Validasi longitude `[-180, 180]` diuji dengan nilai `180.0` (tepat batas atas — valid), `107.0` (dalam rentang — valid), dan `200.0` (satu langkah di atas — invalid).

#### 2.3.2 Equivalence Partitioning (EP)

Domain input dibagi menjadi kelas-kelas ekivalen. Representatif dari masing-masing kelas diuji. Satu anggota kelas valid dan satu anggota kelas invalid sudah mewakili seluruh kelas.

Contoh pada validasi `bbox`: kelas valid (4 bagian numerik), kelas jumlah salah (3 bagian), kelas non-numerik (mengandung huruf), kelas terbalik (west ≥ east).

#### 2.3.3 Decision Table Testing

Digunakan pada fungsi dengan kombinasi kondisi yang menghasilkan output berbeda. Tabel keputusan mendaftarkan semua kombinasi kondisi dan output yang diharapkan secara eksplisit, memastikan tidak ada kombinasi yang terlewat.

Contoh: `entrySpatialSql()` menghasilkan SQL WHERE clause berbeda tergantung kombinasi ada/tidaknya `province_id` dan `bounds`.

### 2.4 Feature Testing (Pengujian Integrasi HTTP)

Selain pengujian unit, dilakukan **feature testing** menggunakan `FeatureTestTrait` dari CI4. Metode ini mengirimkan HTTP request melalui router framework secara penuh (termasuk filter, controller, session) tanpa memerlukan web server aktif. Ini memverifikasi integrasi antar komponen.

### 2.5 PHP Reflection API untuk Akses Metode Privat

Metode-metode kritis pada controller diimplementasikan sebagai `private` untuk menjaga enkapsulasi. Pengujian white-box menggunakan **PHP Reflection API** untuk mengaksesnya secara terkontrol tanpa memodifikasi kode produksi:

```php
$reflection = new ReflectionMethod($objectInstance, 'namaMetode');
$reflection->setAccessible(true);
$result = $reflection->invokeArgs($objectInstance, [$arg1, $arg2]);
```

`setAccessible(true)` hanya berlaku untuk instansi `ReflectionMethod` tersebut dan tidak mengubah visibilitas metode secara global. Ini adalah praktik standar white-box testing PHP (Bergmann, 2023).

### 2.6 Injeksi Request HTTP ke Unit Controller

`BaseController::$request` diisi oleh framework saat routing — bukan di konstruktor. Memanggil `new Catalog()` secara langsung menghasilkan `$this->request = null`. Solusi yang digunakan:

```php
$request = new IncomingRequest(config('App'), new URI('http://localhost/'), null, new UserAgent());
$request->setGlobal('get', ['bbox' => '107,-7,108,-6']); // injeksi GET param
$controller = new Catalog();
$controller->initController($request, service('response'), service('logger'));
```

`setGlobal('get', $array)` menulis langsung ke properti internal `$globals['get']` pada objek request tanpa melewati `filter_input_array(INPUT_GET)`, sehingga aman di lingkungan CLI/PHPUnit.

---

## 3. Strategi dan Perencanaan Pengujian

### 3.1 Arsitektur Test Suite

```
tests/
├── unit/
│   ├── HealthTest.php                              (2 kasus uji)
│   ├── GeoportalFeatureFlowTest.php                (6 kasus uji)
│   ├── RoleFilterTest.php                          (10 kasus uji)
│   ├── CatalogWhiteBoxTest.php                     (29 kasus uji)
│   ├── WebMapWhiteBoxTest.php                      (29 kasus uji)
│   ├── GeoportalDatasetRegistryTest.php            (18 kasus uji)
│   ├── DatasetImportServiceWhiteBoxTest.php        (19 kasus uji)
│   └── MetadataSubmissionServiceWhiteBoxTest.php   (17 kasus uji)
└── phpunit.xml.dist                                (konfigurasi)

Total: 150 kasus uji, 435 assertions
```

### 3.2 Klasifikasi Kasus Uji

| Kategori | Jumlah | Keterangan |
|---|---|---|
| Unit test — logika murni, tanpa DB | 102 | Reflection ke metode privat |
| Unit test — membutuhkan DB PostgreSQL | 16 | `GeoportalDatasetRegistry` query DB |
| Feature test HTTP — tanpa DB | 12 | Router + Filter + Controller + Session |
| Skipped (butuh koneksi DB live) | 2 | Ditandai `markTestSkipped` secara kondisional |
| **Total aktif** | **150** | |

### 3.3 Pemetaan Modul ke Teknik Pengujian

| Modul | Teknik Utama | Alasan |
|---|---|---|
| `Catalog` — `safeFilename`, `decodeBytea` | EP + BVA | Domain input string dengan karakter khusus |
| `Catalog` — `shouldAggregateVectorPreview`, `aggregateGridSize` | Branch coverage + BVA numerik | Logika berbasis angka dengan preset dan clamp |
| `Catalog` — `entrySpatialSql` | Decision table | 4 kombinasi dua kondisi Boolean |
| `Catalog` — `boundsFromRequest` | EP + BVA + Path coverage | Guard clause berlapis, 6 jalur keluar |
| `WebMap` — `normalizeBounds` | EP + BVA | Multi-format input (null, string, array, JSON) |
| `WebMap` — `boundaryPayload`, `spatialSql` | Path coverage | Cabang tergantung tipe geometri GeoJSON |
| `RoleFilter`, `AuthFilter` | Decision table | Matriks role × allowed_roles |
| `MetadataSubmissionService` | File signature + EP | Validasi biner magic bytes multi-format |
| `DatasetImportService` | Data-flow + BVA | Aliran token numerik, path traversal |
| `GeoportalDatasetRegistry` | Statement coverage + DB integration | Caching, pengurutan, konsistensi data |

---

## 4. Infrastruktur dan Konfigurasi Pengujian

### 4.1 Konfigurasi `phpunit.xml.dist`

```xml
<phpunit
    bootstrap="system/Test/bootstrap.php"
    backupGlobals="false"
    beStrictAboutOutputDuringTests="true"
    failOnRisky="true"
    failOnWarning="true"
    colors="true">
    <testsuites>
        <testsuite name="App">
            <directory>./tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory suffix=".php">./app/Views</directory>
            <file>./app/Config/Routes.php</file>
        </exclude>
    </source>
    <php>
        <server name="app.baseURL" value="http://example.com/"/>
    </php>
</phpunit>
```

**Poin konfigurasi penting:**

| Parameter | Nilai | Dampak pada Pengujian |
|---|---|---|
| `bootstrap` | `system/Test/bootstrap.php` | Memuat full CI4 service container sebelum setiap test |
| `failOnWarning` | `true` | `E_WARNING` PHP (misal, `hex2bin` ganjil) → test gagal; mengungkap Bug #3 |
| `failOnRisky` | `true` | Test tanpa assertion dianggap gagal |
| `beStrictAboutOutputDuringTests` | `true` | Output tak terduga (echo/print) → test gagal |

### 4.2 Pola Helper yang Digunakan Secara Konsisten

**Pola 1 — `invokePrivate()`: akses metode privat via Reflection**

```php
private function invokePrivate(object $target, string $method, array $args = []): mixed
{
    $r = new ReflectionMethod($target, $method);
    $r->setAccessible(true);
    return $r->invokeArgs($target, $args);
}
```

**Pola 2 — `catalogWithGet()`: injeksi GET param ke controller**

```php
private function catalogWithGet(array $getParams): Catalog
{
    $request = new IncomingRequest(
        config('App'), new URI('http://localhost/'), null, new UserAgent()
    );
    $request->setGlobal('get', $getParams);
    $c = new Catalog();
    $c->initController($request, service('response'), service('logger'));
    return $c;
}
```

**Pola 3 — `mockUploadedFile()` + `buildZip()`: simulasi unggahan file biner**

```php
// buildZip() mengkonstruksi arsip ZIP valid secara biner (Local Header + Central Dir + EOCD)
// mockUploadedFile() membuat mock UploadedFile yang mengembalikan path file temp dari getTempName()
// Semua file temp dibersihkan di tearDown()
```

### 4.3 Perintah Eksekusi

```powershell
# Dari direktori root proyek
php vendor/bin/phpunit

# Dengan output testdox (nama test yang deskriptif)
php vendor/bin/phpunit --testdox

# Spesifik satu kelas
php vendor/bin/phpunit --filter CatalogWhiteBoxTest
```

---

## 5. Spesifikasi dan Matriks Kasus Uji per Modul

---

### 5.1 Modul: `HealthTest` — Verifikasi Konfigurasi Dasar

**Deskripsi**: Memastikan bootstrap CI4 berjalan dengan benar dan konfigurasi minimum aplikasi valid sebelum test lain dijalankan.

**Teknik**: Statement Coverage

| ID | Nama Kasus Uji | Kondisi | Input | Expected | Hasil |
|---|---|---|---|---|---|
| HLT-01 | Is defined app path | Bootstrap CI4 selesai | — | `defined('APPPATH') === true` | LULUS |
| HLT-02 | Base url has been set | `App.php` dikonfigurasi | — | `baseURL` lolos `valid_url` | LULUS |

---

### 5.2 Modul: `GeoportalFeatureFlowTest` — Alur HTTP End-to-End

**Deskripsi**: Memverifikasi integrasi Router → Filter → Controller → Response untuk alur-alur kritis aplikasi.

**Teknik**: Feature Testing, Equivalence Partitioning

| ID | Nama Kasus Uji | Endpoint | Sesi | Input | Expected | Hasil |
|---|---|---|---|---|---|---|
| GFF-01 | Bootstrap returns dataset contract | `GET /webmap/bootstrap` | Guest | — | HTTP 200, `defaultDataset='faa_l1'`, 4 datasets, support flags | LULUS |
| GFF-02 | Layer rejects unknown dataset | `POST /webmap/layer` | Guest | `dataset='unknown'` | HTTP 400, `error='Dataset tidak terdaftar.'` | LULUS |
| GFF-03 | Layer rejects out-of-range bounds | `POST /webmap/layer` | Guest | `bounds.west=200, bounds.south=-95` | HTTP 400, error koordinat di luar rentang | LULUS |
| GFF-04 | Metadata redirects guest | `GET /metadata` | Guest | — | HTTP 302 → `/login` | LULUS |
| GFF-05 | Metadata store rejects no upload | `POST /metadata` | Login (user) | Form lengkap, tanpa file | HTTP 302, flash error unggah minimal 1 file | LULUS |
| GFF-06 | Metadata store rejects invalid email | `POST /metadata` | Login (user) | `email='tester@example.com, invalid-address'` | HTTP 302, flash error format email | LULUS |

---

### 5.3 Modul: `RoleFilterTest` — Kontrol Akses Berbasis Peran

**Deskripsi**: Memverifikasi setiap jalur keputusan pada `RoleFilter` dan `AuthFilter` — termasuk blokir guest, blokir role salah, izinkan role benar, dan redirect pengguna sudah login.

**Teknik**: Branch Coverage, Decision Table Testing, Feature Testing

#### Tabel Keputusan Filter

| `logged_in` | `role` | `allowed_roles` pada route | Tindakan Filter |
|---|---|---|---|
| `false` / tidak ada | — | `admin` | Redirect → `/login` |
| `false` / tidak ada | — | `admin,user` | Redirect → `/login` |
| `true` | `user` | `admin` | Redirect → `/` + flash `'Access denied.'` |
| `true` | `user` | `admin,user` | Lanjut ke controller |
| `true` | `admin` | `admin` | Lanjut ke controller |
| `true` | `admin` | `admin,user` | Lanjut ke controller |

| ID | Nama Kasus Uji | Route | Sesi | Expected | Jalur | Hasil |
|---|---|---|---|---|---|---|
| RF-01 | Admin route redirects guest to login | `GET /dataset/manage` | Kosong | HTTP 302 → `/login` | AuthFilter: tidak login | LULUS |
| RF-02 | Metadata route redirects guest to login | `GET /metadata` | Kosong | HTTP 302 → `/login` | AuthFilter: tidak login | LULUS |
| RF-03 | Admin route redirects user role to home with error | `GET /dataset/manage` | `role='user'` | HTTP 302 → `/`, flash `'Access denied.'` | RoleFilter: role salah | LULUS |
| RF-04 | Metadata route allows user role | `GET /metadata` | `role='user'` | HTTP ≠ 302 | RoleFilter: role diizinkan | LULUS |
| RF-05 | Metadata route allows admin role | `GET /metadata` | `role='admin'` | HTTP ≠ 302 | RoleFilter: role diizinkan | LULUS |
| RF-06 | Admin route allows admin role | `GET /dataset/manage` | `role='admin'` | HTTP ≠ 302 | RoleFilter: role diizinkan | LULUS |
| RF-07 | Logout destroys session and redirects to login | `GET /logout` | `logged_in=true` | HTTP 302 → `/login` | AuthController: destroy | LULUS |
| RF-08 | Login form redirects authenticated user towards catalog | `GET /login` | `role='user'` | HTTP 302 → `/catalog` | AuthController: sudah login | LULUS |
| RF-09 | Login form redirects authenticated admin towards dataset manage | `GET /login` | `role='admin'` | HTTP 302 → `/dataset/manage` | AuthController: sudah login | LULUS |
| RF-10 | Signup form redirects authenticated user | `GET /signup` | `role='user'` | HTTP 302 → `/catalog` | AuthController: sudah login | LULUS |

---

### 5.4 Modul: `CatalogWhiteBoxTest` — Controller Katalog

**Deskripsi**: Pengujian struktural terhadap metode-metode privat `Catalog` yang mengelola normalisasi nama file, keputusan agregasi vektor, pembangunan SQL spasial, dan parsing parameter HTTP.

**Teknik**: Branch Coverage, BVA, EP, Decision Table, Path Coverage

---

#### 5.4.1 `safeFilename(string $value): string`

**Fungsi**: Mengonversi judul dataset menjadi nama file yang aman (hanya `[a-z0-9_]`). Fallback ke `'dataset'` jika hasilnya kosong.

**Pohon keputusan:**
```
Input → lowercase → ganti spasi dengan '_' → hapus non-[a-z0-9_] → trim '_'
    └── hasilnya kosong? → 'dataset'
    └── tidak kosong   → return hasil
```

| ID | Nama Kasus Uji | Input | Expected | Teknik | Hasil |
|---|---|---|---|---|---|
| CAT-SF-01 | Spasi dikonversi ke underscore | `'Free Air Anomaly Level 1'` | `'free_air_anomaly_level_1'` | EP — kelas normal | LULUS |
| CAT-SF-02 | Karakter alnum+underscore dipertahankan | `'faa_l1_data'` | `'faa_l1_data'` | EP — kelas sudah safe | LULUS |
| CAT-SF-03 | Underscore awal/akhir dipangkas | `'__FAA__'` | `'faa'` | BVA — trim edge | LULUS |
| CAT-SF-04 | Input kosong → fallback | `''` | `'dataset'` | EP — kelas kosong | LULUS |
| CAT-SF-05 | Semua karakter khusus → fallback | `'!@#$%^&*()'` | `'dataset'` | EP — kelas all-special | LULUS |

---

#### 5.4.2 `shouldAggregateVectorPreview(?array $bounds, int $zoom): bool`

**Fungsi**: Memutuskan apakah titik vektor pada pratinjau katalog perlu diagregasi ke sel grid, berdasarkan zoom level dan luas tampilan.

**Pohon keputusan (5 cabang independen):**

```
zoom ≤ 7                           → true  (selalu agregasi, tampilan terlalu luas)
bounds === null                    → true  (tidak bisa hitung span)
zoom > 9                           → false (zoom cukup tinggi, tampilkan detail)
zoom 8–9 AND max(ΔLon,ΔLat) ≥ 1.2 → true  (area masih lebar)
zoom 8–9 AND max(ΔLon,ΔLat) < 1.2 → false (area sudah cukup sempit)
```

| ID | Nama Kasus Uji | bounds | zoom | Expected | Cabang yang Diuji | Hasil |
|---|---|---|---|---|---|---|
| CAT-AV-01 | Low zoom → aggregate | valid | 7 | `true` | zoom ≤ 7 | LULUS |
| CAT-AV-02 | Null bounds → aggregate | `null` | 12 | `true` | bounds === null | LULUS |
| CAT-AV-03 | High zoom → no aggregate | valid | 10 | `false` | zoom > 9 | LULUS |
| CAT-AV-04 | Mid-zoom + area besar → aggregate | span=2° | 9 | `true` | span ≥ 1.2 | LULUS |
| CAT-AV-05 | Mid-zoom + area kecil → no aggregate | span=0.5° | 9 | `false` | span < 1.2 | LULUS |

---

#### 5.4.3 `aggregateGridSize(?array $bounds, int $zoom): float`

**Fungsi**: Menentukan ukuran sel grid agregasi (derajat) berdasarkan preset zoom. Jika bounds tersedia, ukuran dinamis dihitung; nilai akhir adalah `max(preset, dynamic)`, dijepit ke `[0.02, 0.45]`.

**Tabel preset:**

| Zoom | Preset (°) |
|---|---|
| ≤ 5 | 0.35 |
| 6 | 0.22 |
| 7 | 0.14 |
| 8 | 0.08 |
| ≥ 9 (default) | 0.04 |

| ID | Nama Kasus Uji | bounds | zoom | Expected | Jalur | Hasil |
|---|---|---|---|---|---|---|
| CAT-GS-01 | Zoom 5 preset | `null` | 5 | 0.35 | Switch case ≤5 | LULUS |
| CAT-GS-02 | Zoom 6 preset | `null` | 6 | 0.22 | Switch case 6 | LULUS |
| CAT-GS-03 | Zoom 7 preset | `null` | 7 | 0.14 | Switch case 7 | LULUS |
| CAT-GS-04 | Zoom 8 preset | `null` | 8 | 0.08 | Switch case 8 | LULUS |
| CAT-GS-05 | Default (zoom tinggi) | `null` | 11 | 0.04 | Default case | LULUS |
| CAT-GS-06 | Bounds sangat lebar → clamp max | span 26°×17° | 6 | 0.45 | dynamic > preset → clamp ke 0.45 | LULUS |
| CAT-GS-07 | Bounds kecil → preset menang | span 0.1° | 8 | 0.08 | dynamic < preset | LULUS |

---

#### 5.4.4 `decodeBytea(string $value): string`

**Fungsi**: Mendekode nilai bytea PostgreSQL dalam format hex-escaped (`\xHEX`) menjadi string biner.

**Pohon keputusan:**
```
str_starts_with($value, '\x')
    → true:
        $hex = substr($value, 2)
        $hex === '' ATAU strlen($hex) % 2 ≠ 0  → return ''   [BUG FIX #3]
        hex2bin($hex)                           → return binary
    → false:
        pg_unescape_bytea($value)
```

| ID | Nama Kasus Uji | Input | Expected | Jalur | Hasil |
|---|---|---|---|---|---|
| CAT-DB-01 | Hex valid → decode benar | `'\x48656c6c6f'` | `'Hello'` | hex-path, panjang genap | LULUS |
| CAT-DB-02 | Hex panjang ganjil → string kosong | `'\xABC'` | `''` | hex-path, panjang ganjil (Bug #3) | LULUS |

---

#### 5.4.5 `entrySpatialSql(string $geomCol, array $filters, ?array $bounds): array`

**Fungsi**: Membangun fragmen SQL WHERE untuk filter spasial. Mengembalikan `[$sqlFragment, $bindParams]`.

**Tabel Keputusan:**

| province_id | bounds | SQL Fragment | Bind Params |
|---|---|---|---|
| `null` | `null` | `''` | `[]` |
| integer | `null` | `ST_Intersects(geom, (SELECT geom FROM provinces WHERE id = N))` | `[]` |
| `null` | array | `ST_Intersects(geom, ST_MakeEnvelope(?,?,?,?,4326))` | `[w,s,e,n]` |
| integer | array | `{province} AND ST_Intersects(geom, ST_MakeEnvelope(?,?,?,?,4326))` | `[w,s,e,n]` |

| ID | Nama Kasus Uji | province_id | bounds | Expected SQL | Expected Params | Hasil |
|---|---|---|---|---|---|---|
| CAT-SS-01 | Tidak ada filter | `null` | `null` | `''` | `[]` | LULUS |
| CAT-SS-02 | Province saja — int di-embed | `7` | `null` | berisi `'id = 7'` dan `ST_Intersects` | `[]` | LULUS |
| CAT-SS-03 | Bounds saja — bind params | `null` | `[107,-7,108,-6]` | berisi `ST_MakeEnvelope` | `[107.0,-7.0,108.0,-6.0]` | LULUS |
| CAT-SS-04 | Keduanya — AND | `3` | `[107,-7,108,-6]` | berisi `id = 3`, `AND`, `ST_MakeEnvelope` | `[107.0,-7.0,108.0,-6.0]` | LULUS |

---

#### 5.4.6 `boundsFromRequest(): ?array`

**Fungsi**: Memparse parameter GET `bbox=west,south,east,north` menjadi array tervalidasi, atau `null` jika tidak valid.

**Validasi berlapis (guard clauses):**

```
Guard 1: bbox tidak ada / bukan string       → null
Guard 2: tidak tepat 4 bagian CSV            → null
Guard 3: ada bagian non-numerik              → null
Guard 4: west ≥ east ATAU south ≥ north      → null
Guard 5: koordinat di luar [-180,180]/[-90,90] → null   [ditambahkan BUG FIX #1]
Lolos semua guard                             → return array bounds
```

| ID | Nama Kasus Uji | GET `bbox` | Expected | Guard yang Diuji | Hasil |
|---|---|---|---|---|---|
| CAT-BFR-01 | Input valid → array bounds | `'107,-7,108,-6'` | `['west'=>107.0,'south'=>-7.0,'east'=>108.0,'north'=>-6.0]` | Semua lolos | LULUS |
| CAT-BFR-02 | bbox tidak ada → null | — | `null` | Guard 1 | LULUS |
| CAT-BFR-03 | Bagian non-numerik → null | `'107,abc,108,-6'` | `null` | Guard 3 | LULUS |
| CAT-BFR-04 | Hanya 3 bagian → null | `'107,-7,108'` | `null` | Guard 2 | LULUS |
| CAT-BFR-05 | West > east → null | `'108,-7,107,-6'` | `null` | Guard 4 | LULUS |
| CAT-BFR-06 | Koordinat di luar rentang → null | `'200,-95,201,-94'` | `null` | Guard 5 (BVA) | LULUS |

---

### 5.5 Modul: `WebMapWhiteBoxTest` — Controller Peta Web

**Deskripsi**: Pengujian struktural terhadap metode privat `WebMap` yang mengelola normalisasi bounds multi-format, klasifikasi geometri GeoJSON, pembangunan payload boundary, dan konstruksi query PostGIS.

**Teknik**: Branch Coverage, Path Coverage, BVA, EP

---

#### 5.5.1 `normalizeBounds($bounds): ?array`

**Fungsi**: Menerima bounds dalam berbagai format (null, string CSV, string JSON, array asosiatif) dan mengembalikan array tervalidasi atau `null`. Melempar `InvalidArgumentException` untuk input yang terbentuk secara parsial namun tidak valid.

| ID | Nama Kasus Uji | Input | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| WM-NB-01 | CSV string valid | `'107.1,-7.4,108.2,-6.8'` | array valid | string → CSV parse | LULUS |
| WM-NB-02 | Array terbalik → exception | `['west'=>108,...,'east'=>107,...]` | `InvalidArgumentException` "west < east" | validasi urutan | LULUS |
| WM-NB-03 | Koordinat di luar rentang → exception | `['west'=>200,...]` | `InvalidArgumentException` "di luar rentang" | BVA batas | LULUS |
| WM-NB-04 | `null` → `null` | `null` | `null` | cabang null | LULUS |
| WM-NB-05 | String kosong → `null` | `''` | `null` | cabang empty string | LULUS |
| WM-NB-06 | JSON string valid | `'{"west":107.1,...}'` | array valid | string → JSON parse | LULUS |
| WM-NB-07 | CSV 3 bagian → exception | `'107,-7,108'` | `InvalidArgumentException` "Format tidak valid" | CSV count ≠ 4 | LULUS |
| WM-NB-08 | Array tanpa key `north` → exception | `['west'=>107,'south'=>-7,'east'=>108]` | `InvalidArgumentException` "west, south, east, north numerik" | key missing | LULUS |
| WM-NB-09 | Nilai non-numerik → exception | `['west'=>'abc',...]` | `InvalidArgumentException` | is_numeric false | LULUS |
| WM-NB-10 | Input integer → exception | `42` | `InvalidArgumentException` "Format tidak valid" | bukan array/string | LULUS |

---

#### 5.5.2 `filtersFromInput(array $input): array`

**Fungsi**: Memparse input request POST ke struktur filter internal: clamp buffer negatif, konversi `force_detail`, normalisasi bounds.

| ID | Nama Kasus Uji | Input | Expected | Jalur | Hasil |
|---|---|---|---|---|---|
| WM-FI-01 | Buffer negatif → clamp; force_detail='true' | `buffer=-50, force_detail='true'` | `buffer=0, force_detail=true, province_id=12, zoom=9` | BVA buffer + boolean | LULUS |

---

#### 5.5.3 `shouldAggregateVector(array $filters): bool`

**Fungsi**: Menentukan apakah query vektor perlu diagregasi, mempertimbangkan `force_detail`, `zoom`, dan luas `bounds`.

| ID | Nama Kasus Uji | filters | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| WM-SA-01 | `force_detail=true` → tidak agregasi | `force_detail=true, zoom=4` | `false` | force_detail override | LULUS |
| WM-SA-02 | Zoom rendah → agregasi | `zoom=7` | `true` | zoom ≤ 7 | LULUS |
| WM-SA-03 | Zoom mid + area besar → agregasi | `zoom=9, span=2°` | `true` | mid-zoom + area besar | LULUS |
| WM-SA-04 | Zoom tinggi → tidak agregasi | `zoom=10` | `false` | zoom > 9 | LULUS |

---

#### 5.5.4 `geometryType($geom): ?string`

**Fungsi**: Mengekstrak jenis geometri dari berbagai representasi (array GeoJSON langsung, Feature wrapper, JSON string).

| ID | Nama Kasus Uji | Input | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| WM-GT-01 | Array GeoJSON langsung | `['type'=>'Point',...]` | `'Point'` | is_array | LULUS |
| WM-GT-02 | Feature wrapper → unwrap | `['type'=>'Feature','geometry'=>['type'=>'Polygon',...]]` | `'Polygon'` | Feature unwrap | LULUS |
| WM-GT-03 | Non-array → `null` | `null`, `42` | `null` | bukan array/string | LULUS |
| WM-GT-04 | JSON string valid → decode | `'{"type":"LineString",...}'` | `'LineString'` | JSON decode | LULUS |
| WM-GT-05 | JSON string tidak valid → `null` | `'not-json'` | `null` | json_decode fail | LULUS |

---

#### 5.5.5 `boundaryPayload(array $filters): ?array`

**Fungsi**: Membangun payload GeoJSON boundary berdasarkan prioritas filter (geometry → bounds → null).

| ID | Nama Kasus Uji | Filter Input | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| WM-BP-01 | Semua null → `null` | semua null | `null` | no-filter | LULUS |
| WM-BP-02 | FeatureCollection → GeometryCollection | FC dengan 2 features | `type='GeometryCollection'`, count=2 | FC conversion | LULUS |
| WM-BP-03 | Bounds saja → Polygon | `bounds=[107,-7,108,-6]` | `type='Polygon'`, ring tertutup (5 titik) | bounds polygon | LULUS |
| WM-BP-04 | Feature wrapper → Polygon | `type='Feature', geometry.type='Polygon'` | `geometry_type='Polygon'` | Feature unwrap | LULUS |
| WM-BP-05 | Geometry tanpa key `type` → exception | `['coordinates':[]]` | `InvalidArgumentException` "tidak valid" | missing type | LULUS |
| WM-BP-06 | JSON tidak valid → exception | `'not-valid-json'` | `InvalidArgumentException` "GeoJSON tidak valid" | invalid GeoJSON string | LULUS |

---

#### 5.5.6 `spatialSql(string $geomCol, array $filters): array`

| ID | Nama Kasus Uji | filters | Expected SQL | Params | Hasil |
|---|---|---|---|---|---|
| WM-SP-01 | Point + buffer + bounds | Point, buffer=1500, bounds set | berisi `ST_DWithin` + `ST_MakeEnvelope` | 6 params, `params[1]=1500, params[2]=107.4` | LULUS |
| WM-SP-02 | Semua null → kosong | semua null | `''` | `[]` | LULUS |

---

#### 5.5.7 `boundaryExpression(array $boundary, array $filters): array`

| ID | Nama Kasus Uji | geometry_type | buffer | Expected | Cabang | Hasil |
|---|---|---|---|---|---|---|
| WM-BE-01 | Point → ST_Buffer | `'Point'` | 2500 | berisi `ST_Buffer`, params[1]=2500 | Point branch | LULUS |
| WM-BE-02 | Polygon → tanpa ST_Buffer | `'Polygon'` | 500 | tidak ada `ST_Buffer`, ada `ST_GeomFromGeoJSON`, 1 param | non-Point branch | LULUS |

---

#### 5.5.8 `metadataScope(array $filters): string`

**Fungsi**: Menentukan lingkup metadata berdasarkan prioritas filter.

| Prioritas | Kondisi | Nilai Output |
|---|---|---|
| 1 | `province_id` ada | `'regional'` |
| 2 | `geometry` ada | `'custom'` |
| 3 | `bounds` ada | `'viewport'` |
| 4 (default) | Semua kosong | `'national'` |

| ID | Nama Kasus Uji | Filter | Expected | Hasil |
|---|---|---|---|---|
| WM-MS-01 | Province ada → regional | `province_id=5` | `'regional'` | LULUS |
| WM-MS-02 | Hanya geometry → custom | `geometry` set | `'custom'` | LULUS |
| WM-MS-03 | Hanya bounds → viewport | `bounds` set | `'viewport'` | LULUS |
| WM-MS-04 | Semua kosong → national | semua null | `'national'` | LULUS |

---

### 5.6 Modul: `GeoportalDatasetRegistryTest` — Registri Dataset

**Deskripsi**: Memverifikasi struktur dan perilaku `GeoportalDatasetRegistry` — definisi dataset statis, look-up berdasarkan kode, dan query katalog ke database.

**Teknik**: EP, Statement Coverage, DB Integration Testing

---

#### 5.6.1 `definitions(): array` (tanpa DB)

| ID | Nama Kasus Uji | Expected | Hasil |
|---|---|---|---|
| GDR-01 | Returns four datasets | `count() === 4` | LULUS |
| GDR-02 | Contains expected codes | `['faa_l1','cba_l1','faa_l2','cba_l2']` | LULUS |
| GDR-03 | Vector datasets have correct type | `type='vector'` untuk FAA L1 dan CBA L1 | LULUS |
| GDR-04 | Raster datasets have correct type | `type='raster'` untuk FAA L2 dan CBA L2 | LULUS |
| GDR-05 | Raster datasets have raster_column key | `assertArrayHasKey('raster_column')` | LULUS |
| GDR-06 | Vector datasets do not have raster_column key | `assertArrayNotHasKey('raster_column')` | LULUS |
| GDR-07 | All datasets have summary_unit mGal | `summary_unit='mGal'` untuk semua 4 dataset | LULUS |

---

#### 5.6.2 `dataset(string $code): array` (tanpa DB)

| ID | Nama Kasus Uji | Input | Expected | Hasil |
|---|---|---|---|---|
| GDR-08 | Returns faa_l1 definition | `'faa_l1'` | `code='faa_l1', type='vector'` | LULUS |
| GDR-09 | Returns cba_l2 definition | `'cba_l2'` | `code='cba_l2', type='raster'` | LULUS |
| GDR-10 | Throws for unknown code | `'invalid_code'` | `InvalidArgumentException` "tidak terdaftar" | LULUS |
| GDR-11 | Throws for empty code | `''` | `InvalidArgumentException` | LULUS |

---

#### 5.6.3 `filterCatalogEntries()` dan `catalogEntries()` (membutuhkan DB)

| ID | Nama Kasus Uji | Filter | Expected | Hasil |
|---|---|---|---|---|
| GDR-12 | No filter returns all | `[]` | `count > 0` | LULUS |
| GDR-13 | Search by title | `q='Free Air Anomaly Level 1'` | Semua hasil mengandung judul | LULUS |
| GDR-14 | National scope only — exactly 4 entries | `spatial_scope=['national']` | `count=4` | LULUS |
| GDR-15 | Regional scope has province_name | `spatial_scope=['regional']` | Semua entry `province_name ≠ null` | LULUS |
| GDR-16 | Anomaly faa filter | `anomaly=['faa']` | Semua `anomaly_key='faa'` | LULUS |
| GDR-17 | Anomaly cba filter | `anomaly=['cba']` | Semua `anomaly_key='cba'` | LULUS |
| GDR-18 | Level 1 filter | `level=['level1']` | Semua `level_key='level1'` | LULUS |
| GDR-19 | Level 2 filter | `level=['level2']` | Semua `level_key='level2'` | LULUS |
| GDR-20 | Combined faa+level1+national | 3 filter | `count=1`, semua atribut cocok | LULUS |
| GDR-21 | Search returns empty for nonexistent title | `q='ZZZZNONEXISTENT'` | `[]` | LULUS |
| GDR-22 | Sequential IDs starting from 1 | — | `entry[i].id === i+1` | LULUS |
| GDR-23 | Sorted by title | — | `titles === sort(titles)` | LULUS |
| GDR-24 | catalogEntry(1) returns entry id=1 | id=1 | array `id=1` | LULUS |
| GDR-25 | catalogEntry(999999) returns null | id=999999 | `null` | LULUS |
| GDR-26 | Results cached on second call | — | `$first === $second` | LULUS |
| GDR-27 | National entries have null province_id | scope=national | Semua `province_id === null` | LULUS |

---

### 5.7 Modul: `DatasetImportServiceWhiteBoxTest` — Layanan Impor Dataset

**Deskripsi**: Pengujian struktural terhadap logika parsing CSV, normalisasi kolom, konversi token numerik, deteksi mode survei, validasi path, dan parser XML metadata.

**Teknik**: Data-flow Testing, BVA, EP, Path Coverage, Security Testing

---

#### 5.7.1 `normalizeColumnName(string $name): string`

**Fungsi**: Mengonversi nama kolom header CSV ke bentuk normal (lowercase, hapus karakter non-alfanumerik dan spasi) untuk pencocokan alias.

| ID | Nama Kasus Uji | Input | Expected | Hasil |
|---|---|---|---|---|
| DIS-NC-01 | Nama dengan titik dan spasi | `'Tinggi Ort.'` | `'tinggiort'` | LULUS |
| DIS-NC-02 | Nama uppercase penuh | `'FAA'` | `'faa'` | LULUS |
| DIS-NC-03 | Nama dengan spasi internal | `'Tinggi Ortometrik'` | `'tinggiortometrik'` | LULUS |
| DIS-NC-04 | Spasi leading/trailing | `' Lintang '` | `'lintang'` | LULUS |
| DIS-NC-05 | String kosong | `''` | `''` | LULUS |

---

#### 5.7.2 `buildCsvColumnMap(array $headers, string $type, string $file): array`

**Fungsi**: Memetakan header CSV ke nama field internal dengan pencocokan alias yang fleksibel.

| ID | Nama Kasus Uji | Input | Expected | Hasil |
|---|---|---|---|---|
| DIS-CM-01 | Header standar dikenali | `[' Lintang ','Bujur','Tinggi Ort.','FAA']` | `{latitude:0, longitude:1, orthometric_height:2, anomaly_value:3}` | LULUS |
| DIS-CM-02 | Kolom wajib hilang → exception | Header tanpa anomaly value | `RuntimeException` "Kolom anomaly_value tidak ditemukan" | LULUS |

---

#### 5.7.3 `mapCsvRow(...)`, `toFloat(...)`, `toNullableFloat(...)`

| ID | Nama Kasus Uji | Input | Expected | Hasil |
|---|---|---|---|---|
| DIS-MR-01 | Parse baris normal, height kosong → null | `['-6.9147','107.6098','','125.5']` | lat=-6.9147, lon=107.6098, height=null, anomaly=125.5 | LULUS |
| DIS-TF-01 | `toFloat` dengan `'NaN'` → exception | `'NaN'` | `RuntimeException` "Nilai FAA tidak valid" | LULUS |
| DIS-NF-01 | `toNullableFloat`: kosong → null, Infinity → null | `''`, `'Infinity'` | `null`, `null` | LULUS |
| DIS-NF-02 | `toNullableFloat`: angka valid → float | `'12.75'` | `12.75` | LULUS |

---

#### 5.7.4 `isInvalidNumericToken(string $token): bool`

**Fungsi**: Mendeteksi sentinel value non-finite yang tidak boleh masuk ke database (case-insensitive).

| ID | Nama Kasus Uji | Input | Expected | Jalur | Hasil |
|---|---|---|---|---|---|
| DIS-IT-01 | NaN case-insensitive | `'NaN'`, `'nan'`, `'NAN'` | `true` | Sentinel NaN | LULUS |
| DIS-IT-02 | Infinity variants | `'Inf'`, `'-inf'`, `'Infinity'`, `'-Infinity'`, `'-infinity'` | `true` | Sentinel Inf | LULUS |
| DIS-IT-03 | Angka valid | `'0'`, `'-6.9147'`, `'125.5'`, `'-0.001'` | `false` | Non-sentinel | LULUS |

---

#### 5.7.5 `rowIsEmpty(array $row): bool`

| ID | Nama Kasus Uji | Input | Expected | Hasil |
|---|---|---|---|---|
| DIS-RE-01 | Semua sel kosong | `['','','']` | `true` | LULUS |
| DIS-RE-02 | Sel whitespace saja | `['   ', "\t", "\n"]` | `true` | LULUS |
| DIS-RE-03 | Satu sel berisi `'0'` (falsy tapi tidak kosong) | `['','0','']` | `false` | LULUS |
| DIS-RE-04 | Satu sel berisi angka | `['-6.9147']` | `false` | LULUS |

---

#### 5.7.6 `detectSurveyMode(string $filename): string`

| ID | Nama Kasus Uji | Input | Expected | Hasil |
|---|---|---|---|---|
| DIS-DM-01 | Keyword airborne | `'FAA_Airborne_01.csv'` | `'airborne'` | LULUS |
| DIS-DM-02 | Keyword terestris | `'faa_terestris_jabar.csv'` | `'terestris'` | LULUS |
| DIS-DM-03 | Tidak ada keyword | `'faa_misc.csv'` | `'unknown'` | LULUS |

---

#### 5.7.7 `packagePath(string $name): string`

**Fungsi**: Mengembalikan path absolut direktori paket impor. Memvalidasi nama tidak kosong dan path hasil tidak keluar dari direktori root yang diizinkan.

| ID | Nama Kasus Uji | Input | Expected | Aspek Keamanan | Hasil |
|---|---|---|---|---|---|
| DIS-PP-01 | Nama kosong → exception | `''` | `InvalidArgumentException` "tidak boleh kosong" | EP — input kosong | LULUS |
| DIS-PP-02 | Hanya spasi → exception | `'   '` | `InvalidArgumentException` "tidak boleh kosong" | EP — whitespace only | LULUS |
| DIS-PP-03 | Direktori tidak ada → exception | `'nonexistent_xyzzy_9999'` | `RuntimeException` "tidak ditemukan" | EP — path tidak ada | LULUS |
| DIS-PP-04 | Path traversal `../config` → exception | `'../config'` | `RuntimeException` | **Security BVA** | LULUS |

---

#### 5.7.8 `parseMetadataXml(string $path): array`

| ID | Nama Kasus Uji | Isi XML | Expected | Hasil |
|---|---|---|---|---|
| DIS-PX-01 | Whitespace dinormalisasi, email deduplikasi | XML dengan double-space internal dan email duplikat | `title='Raster Uji'`, `emails=['first@example.com','second@example.com']` | LULUS |

---

### 5.8 Modul: `MetadataSubmissionServiceWhiteBoxTest` — Validasi File Unggahan

**Deskripsi**: Pengujian berbasis signature biner dan struktur arsip untuk memverifikasi validasi konten file yang diunggah — bukan hanya ekstensi atau MIME type.

**Teknik**: File Signature Verification, Branch Coverage, EP, BVA

---

#### 5.8.1 Shapefile ZIP

**Metode**: `validateShapefileZip()`

Validasi berlapis:
1. Magic bytes `PK\x03\x04` — file harus benar-benar ZIP.
2. EOCD (End of Central Directory) parseable — ZIP tidak korup.
3. Bundle harus mengandung `.shp`, `.shx`, `.dbf` dengan nama layer yang sama.

| ID | Nama Kasus Uji | Isi File | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| MSS-SHP-01 | Bundle lengkap → valid | `.shp` + `.shx` + `.dbf` | `errors = []` | jalur nominal | LULUS |
| MSS-SHP-02 | Bundle tidak lengkap (tanpa `.shx`) | `.shp` + `.dbf` | error "harus berisi .shp, .shx, .dbf" | bundle validation | LULUS |
| MSS-SHP-03 | File bukan ZIP — tanpa magic PK | teks biasa | error "bukan arsip ZIP yang valid" | signature check | LULUS |
| MSS-SHP-04 | ZIP dengan PK magic tapi tanpa EOCD | header saja, tanpa EOCD | error "tidak dapat dibaca sebagai arsip ZIP" | EOCD parse fail | LULUS |

---

#### 5.8.2 File Tabular CSV

**Metode**: `validateCsvFile()`

Deteksi delimiter otomatis: koma → titik koma → tab. UTF-8 BOM distrip sebelum parsing.

| ID | Nama Kasus Uji | Isi File | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| MSS-CSV-01 | CSV valid koma | `lintang,bujur,faa\n...` | `errors = []` | nominal | LULUS |
| MSS-CSV-02 | CSV dengan UTF-8 BOM | `\xEF\xBB\xBF` + header koma | `errors = []` | BOM strip | LULUS |
| MSS-CSV-03 | CSV delimiter titik koma | `lintang;bujur;faa\n...` | `errors = []` | semicolon fallback | LULUS |
| MSS-CSV-04 | CSV delimiter tab | `lintang\tbujur\tfaa\n...` | `errors = []` | tab fallback | LULUS |
| MSS-CSV-05 | CSV malformed — tanpa delimiter | `header-without-delimiter` | error "tidak valid atau tidak memiliki struktur tabel" | invalid structure | LULUS |
| MSS-CSV-06 | CSV kosong | `''` | error "tidak valid" | empty file | LULUS |

---

#### 5.8.3 File Tabular XLSX

**Metode**: `validateXlsxFile()`

XLSX adalah arsip ZIP yang harus mengandung dua file kritis: `[Content_Types].xml` dan `xl/workbook.xml`.

| ID | Nama Kasus Uji | Isi ZIP | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| MSS-XLSX-01 | XLSX minimal valid | `[Content_Types].xml` + `xl/workbook.xml` | `errors = []` | nominal | LULUS |
| MSS-XLSX-02 | Tanpa `xl/workbook.xml` | hanya `[Content_Types].xml` | error "workbook tidak lengkap" | missing workbook | LULUS |
| MSS-XLSX-03 | Tanpa `[Content_Types].xml` | hanya `xl/workbook.xml` | error "workbook tidak lengkap" | missing content types | LULUS |

---

#### 5.8.4 File Tabular XLS

**Metode**: `validateXlsFile()`

XLS (format biner lama) diidentifikasi melalui signature OLE Compound Document: `D0 CF 11 E0 A1 B1 1A E1`.

| ID | Nama Kasus Uji | Header Binary (8 byte pertama) | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| MSS-XLS-01 | OLE header valid | `D0 CF 11 E0 A1 B1 1A E1` | `errors = []` | OLE signature match | LULUS |
| MSS-XLS-02 | Header tidak valid | `\x00` × 512 | error "bukan workbook Excel biner yang dikenali" | signature mismatch | LULUS |

---

#### 5.8.5 File Raster TIFF / GeoTIFF

**Metode**: `validateTiffFile()`

TIFF mendukung 4 varian signature berdasarkan endianness dan generasi format:

| Magic Bytes | Interpretasi |
|---|---|
| `49 49 2A 00` (`II*\x00`) | Little-endian (Intel), Classic TIFF |
| `4D 4D 00 2A` (`MM\x00*`) | Big-endian (Motorola), Classic TIFF |
| `49 49 2B 00` (`II+\x00`) | Little-endian, BigTIFF |
| `4D 4D 00 2B` (`MM\x00+`) | Big-endian, BigTIFF |

| ID | Nama Kasus Uji | Signature | Expected | Cabang | Hasil |
|---|---|---|---|---|---|
| MSS-TIF-01 | TIFF little-endian classic | `II*\x00` | `errors = []` | LE classic | LULUS |
| MSS-TIF-02 | TIFF big-endian classic | `MM\x00*` | `errors = []` | BE classic | LULUS |
| MSS-TIF-03 | BigTIFF little-endian | `II+\x00` | `errors = []` | LE BigTIFF | LULUS |
| MSS-TIF-04 | Header tidak valid | `NOT_A_TIFF` | error "header raster tidak dikenali" | signature mismatch | LULUS |

---

## 6. Defek yang Ditemukan dan Perbaikan

### 6.1 BUG-001 — Tidak Ada Validasi Rentang Geografis pada `Catalog::boundsFromRequest()`

| Atribut | Detail |
|---|---|
| **ID** | BUG-001 |
| **Lokasi** | `app/Controllers/Catalog.php`, metode `boundsFromRequest()` |
| **Ditemukan oleh** | Kasus uji CAT-BFR-06 (BVA koordinat di luar rentang) |
| **Keparahan** | Sedang — query PostGIS dengan koordinat tidak valid dapat menghasilkan hasil kosong atau error |
| **Kategori** | Validasi input — *Missing boundary check* |
| **Status** | **Diperbaiki** |

**Deskripsi**: Parameter GET `bbox=200,-95,201,-94` (longitude dan latitude di luar rentang geografis yang valid) diterima dan dikembalikan sebagai bounds yang sah, tanpa penolakan. `ST_MakeEnvelope(200,-95,201,-94,4326)` menghasilkan envelope tidak bermakna di PostGIS.

**Kode sebelum perbaikan:**
```php
if ($bounds['west'] >= $bounds['east'] || $bounds['south'] >= $bounds['north']) {
    return null;
}
return $bounds;  // tidak ada cek rentang [-180,180] / [-90,90]
```

**Kode sesudah perbaikan:**
```php
if ($bounds['west'] >= $bounds['east'] || $bounds['south'] >= $bounds['north']) {
    return null;
}

if (
    $bounds['west'] < -180 || $bounds['west'] > 180
    || $bounds['east'] < -180 || $bounds['east'] > 180
    || $bounds['south'] < -90 || $bounds['south'] > 90
    || $bounds['north'] < -90 || $bounds['north'] > 90
) {
    return null;
}

return $bounds;
```

**Konsistensi**: Perbaikan ini mengikuti pola validasi yang sudah ada di `WebMap::normalizeBounds()` dan endpoint `webmap/layer`.

---

### 6.2 BUG-002 — Path Traversal pada `DatasetImportService::packagePath()`

| Atribut | Detail |
|---|---|
| **ID** | BUG-002 |
| **Lokasi** | `app/Libraries/DatasetImportService.php`, metode `packagePath()` |
| **Ditemukan oleh** | Kasus uji DIS-PP-04 (path traversal `../config`) |
| **Keparahan** | Tinggi — implikasi keamanan: penyerang dapat mengarahkan impor ke direktori di luar yang diizinkan |
| **Kategori** | Keamanan — *Path Traversal / Directory Traversal* (CWE-22) |
| **Status** | **Diperbaiki** |

**Deskripsi**: Pemeriksaan containment menggunakan `strpos($candidate, $root) !== 0`. Kelemahan: jika `$root = '/var/www/import'` dan `$candidate = '/var/www/import_evil'`, maka `strpos` mengembalikan `0` (string dimulai dengan `/var/www/import`) meski path tersebut berada di luar direktori yang diizinkan.

**Kode sebelum perbaikan:**
```php
if ($root === false || strpos($candidate, $root) !== 0) {
    throw new RuntimeException('Folder import berada di luar direktori.');
}
```

**Kode sesudah perbaikan:**
```php
if (
    $root === false
    || (strpos($candidate, $root . DIRECTORY_SEPARATOR) !== 0 && $candidate !== $root)
) {
    throw new RuntimeException('Folder import berada di luar direktori yang diizinkan.');
}
```

**Penjelasan**: Dengan menambahkan `DIRECTORY_SEPARATOR` (`/` di Linux, `\` di Windows) setelah root path, direktori `/var/www/import_evil` tidak lagi lolos sebagai sub-path dari `/var/www/import` karena tidak diawali `/var/www/import/`.

---

### 6.3 BUG-003 — `hex2bin()` Tanpa Guard Panjang Ganjil di `decodeBytea()`

| Atribut | Detail |
|---|---|
| **ID** | BUG-003 |
| **Lokasi** | `app/Controllers/Catalog.php` dan `app/Controllers/WebMap.php`, metode `decodeBytea()` |
| **Ditemukan oleh** | Kasus uji CAT-DB-02 (hex panjang ganjil `\xABC`) |
| **Keparahan** | Rendah-Sedang — `E_WARNING` di produksi (tertelan); `ErrorException` fatal di test environment |
| **Kategori** | Keandalan — *Missing input validation before system call* |
| **Status** | **Diperbaiki** (di kedua controller) |

**Deskripsi**: Fungsi PHP `hex2bin()` melempar `E_WARNING` saat menerima string hex dengan panjang ganjil karena setiap byte membutuhkan tepat 2 karakter hex. Di environment PHPUnit dengan `failOnWarning="true"`, `E_WARNING` dikonversi menjadi `ErrorException` dan menyebabkan test gagal dengan error — bukan hasil `''` yang diharapkan.

**Kode sebelum perbaikan:**
```php
if (str_starts_with($value, '\\x')) {
    $binary = hex2bin(substr($value, 2));  // E_WARNING jika strlen ganjil!
    return $binary === false ? '' : $binary;
}
```

**Kode sesudah perbaikan** (diterapkan di `Catalog.php` dan `WebMap.php`):
```php
if (str_starts_with($value, '\\x')) {
    $hex = substr($value, 2);
    if ($hex === '' || strlen($hex) % 2 !== 0) {
        return '';  // bytea korup atau terpotong → string kosong
    }
    $binary = hex2bin($hex);
    return $binary === false ? '' : $binary;
}
```

---

### 6.4 Observasi Keamanan: XXE (XML External Entity)

| Atribut | Detail |
|---|---|
| **ID** | OBS-001 |
| **Lokasi** | `app/Libraries/DatasetImportService.php`, metode `parseMetadataXml()` |
| **Status** | Observed Safe pada runtime saat ini |

**Deskripsi**: Dilakukan probe manual terhadap `parseMetadataXml()` dengan external entity yang menunjuk file sistem lokal. Pada runtime PHP 8.2 yang diuji, entity tidak diekspansi ke field `title`. Tidak ada bukti eksploit, namun parser XML perlu terus dipantau jika konfigurasi libxml/PHP berubah. Disarankan menambahkan `libxml_disable_entity_loader(true)` secara eksplisit sebagai pertahanan mendalam.

---

## 7. Rekapitulasi Hasil Pengujian

### 7.1 Output Eksekusi Akhir

```
$ php vendor/bin/phpunit --testdox

PHPUnit 11.5 by Sebastian Bergmann and contributors.

........................ 150 / 150 (100%)

Time: 00:00.636, Memory: 20.00 MB

There was 1 PHPUnit test runner warning:
1) No code coverage driver available

OK, but there were issues!
Tests: 150, Assertions: 435, PHPUnit Warnings: 1, Skipped: 2.
```

### 7.2 Ringkasan Metrik

| Metrik | Nilai |
|---|---|
| Total kasus uji | 150 |
| **Lulus (Pass)** | **148** |
| Gagal (Fail) | **0** |
| Error | **0** |
| Dilewati (Skipped) | 2 |
| Total assertion | 435 |
| Waktu eksekusi | 0.636 detik |
| Memori puncak | 20 MB |

*2 kasus dilewati: test integrasi DB yang membutuhkan koneksi PostgreSQL live di luar environment test standar.*

*1 PHPUnit Warning: "No code coverage driver available" — Xdebug tidak terpasang; bukan kegagalan test.*

### 7.3 Rekap per Kelas Test

| Kelas Test | Total | Pass | Skip | Assertions |
|---|---|---|---|---|
| HealthTest | 2 | 2 | 0 | 4 |
| GeoportalFeatureFlowTest | 6 | 6 | 0 | 21 |
| RoleFilterTest | 10 | 10 | 0 | 18 |
| CatalogWhiteBoxTest | 29 | 29 | 0 | 71 |
| WebMapWhiteBoxTest | 29 | 29 | 0 | 83 |
| GeoportalDatasetRegistryTest | 18 | 16 | 2 | 66 |
| DatasetImportServiceWhiteBoxTest | 19 | 19 | 0 | 89 |
| MetadataSubmissionServiceWhiteBoxTest | 17 | 17 | 0 | 83 |
| **Total** | **150** | **148** | **2** | **435** |

### 7.4 Rekap Cakupan Cabang per Metode

| Metode yang Diuji | Cabang Total | Cabang Diuji | Cakupan |
|---|---|---|---|
| `Catalog::safeFilename()` | 4 | 4 | 100% |
| `Catalog::shouldAggregateVectorPreview()` | 5 | 5 | 100% |
| `Catalog::aggregateGridSize()` | 7 | 7 | 100% |
| `Catalog::decodeBytea()` | 4 | 4 | 100% |
| `Catalog::entrySpatialSql()` | 4 | 4 | 100% |
| `Catalog::boundsFromRequest()` | 6 | 6 | 100% |
| `WebMap::normalizeBounds()` | 10 | 10 | 100% |
| `WebMap::filtersFromInput()` | 5 | 5 | 100% |
| `WebMap::shouldAggregateVector()` | 4 | 4 | 100% |
| `WebMap::geometryType()` | 5 | 5 | 100% |
| `WebMap::boundaryPayload()` | 6 | 6 | 100% |
| `WebMap::spatialSql()` | 2 | 2 | 100% |
| `WebMap::boundaryExpression()` | 2 | 2 | 100% |
| `WebMap::metadataScope()` | 4 | 4 | 100% |
| `GeoportalDatasetRegistry::definitions()` | 7 | 7 | 100% |
| `GeoportalDatasetRegistry::dataset()` | 3 | 3 | 100% |
| `GeoportalDatasetRegistry::filterCatalogEntries()` | 10 | 10 | 100% |
| `DatasetImportService::normalizeColumnName()` | 5 | 5 | 100% |
| `DatasetImportService::buildCsvColumnMap()` | 2 | 2 | 100% |
| `DatasetImportService::rowIsEmpty()` | 4 | 4 | 100% |
| `DatasetImportService::isInvalidNumericToken()` | 3 | 3 | 100% |
| `DatasetImportService::packagePath()` | 4 | 4 | 100% |
| `MetadataSubmissionService::validateSubmissionFiles()` | 17 | 17 | 100% |
| `RoleFilter` + `AuthFilter` | 5 | 5 | 100% |

### 7.5 Ringkasan Defek

| ID | Lokasi | Keparahan | Kategori | Status |
|---|---|---|---|---|
| BUG-001 | `Catalog::boundsFromRequest()` | Sedang | Missing validation | **Diperbaiki** |
| BUG-002 | `DatasetImportService::packagePath()` | Tinggi | Security — Path Traversal (CWE-22) | **Diperbaiki** |
| BUG-003 | `Catalog::decodeBytea()` + `WebMap::decodeBytea()` | Rendah-Sedang | Robustness — missing guard | **Diperbaiki** |
| OBS-001 | `DatasetImportService::parseMetadataXml()` | Informasi | XXE probe — tidak terbukti eksploitabel | Observasi (pantau) |

**Defek terbuka: 0**

---

## 8. Artefak Pengujian

| Artefak | Path |
|---|---|
| Konfigurasi PHPUnit | `phpunit.xml.dist` |
| Test konfigurasi dasar | `tests/unit/HealthTest.php` |
| Test alur HTTP end-to-end | `tests/unit/GeoportalFeatureFlowTest.php` |
| Test kontrol akses | `tests/unit/RoleFilterTest.php` |
| Test white-box Catalog | `tests/unit/CatalogWhiteBoxTest.php` |
| Test white-box WebMap | `tests/unit/WebMapWhiteBoxTest.php` |
| Test registri dataset | `tests/unit/GeoportalDatasetRegistryTest.php` |
| Test impor dataset | `tests/unit/DatasetImportServiceWhiteBoxTest.php` |
| Test validasi file upload | `tests/unit/MetadataSubmissionServiceWhiteBoxTest.php` |
| Matriks test (CSV) | `docs/testing/white-box-test-matrix-remediated.csv` |
| Output testdox (HTML) | `build/logs/testdox.html` |
| Output testdox (teks) | `build/logs/testdox.txt` |
| Log JUnit XML | `build/logs/logfile.xml` |

---

## 9. Rekomendasi Lanjutan

1. **Driver code coverage**: Pasang Xdebug atau PCOV untuk menghasilkan laporan branch/line coverage numerik dari `build/logs/html/`. Ini memungkinkan identifikasi kode yang belum tersentuh test secara visual.

2. **Integration test `DatasetImportService::importPackage()`**: Tambahkan test dengan fixture paket CSV dan XML nyata yang berjalan terhadap skema `testing` di PostgreSQL untuk memvalidasi end-to-end pipeline impor.

3. **Feature test unduhan**: Tambahkan test untuk route `GET /catalog/{id}/download` dan `GET /webmap/download/*` — termasuk skenario unauthorized (403) dan dataset tidak ada (404).

4. **Hardening XXE**: Tambahkan `libxml_set_external_entity_loader(null)` atau `LIBXML_NOENT` disabled secara eksplisit pada `parseMetadataXml()` sebagai pertahanan mendalam meski probe saat ini tidak terbukti eksploitabel.

5. **Validasi MIME raster lebih dalam**: Pertimbangkan pembacaan header IFD (Image File Directory) TIFF untuk memverifikasi keberadaan GeoTIFF tag (34264, 34735) sebagai validasi tambahan bahwa file adalah GeoTIFF yang sesungguhnya, bukan hanya TIFF biasa.

---

*Dokumen ini dihasilkan dari analisis kode sumber dan eksekusi `php vendor/bin/phpunit --testdox` pada repository GravPort, branch `main`, tanggal 10 Mei 2026.*
