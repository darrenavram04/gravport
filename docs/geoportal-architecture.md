# Geoportal Architecture Guide

Dokumen ini menjelaskan alur hubungan kode aktif di repo `geoportal`, dengan fokus pada modul aplikasi yang berada di `app/`, `public/site/js/`, dan paket impor di `writable/imports/`. Isi di bawah merujuk ke kondisi kode per 29 April 2026.

## 1. Gambaran Umum Sistem

Geoportal ini dibangun di atas CodeIgniter 4 dan saat ini punya empat alur utama:

1. Halaman publik dan layout umum.
2. Katalog dataset dan unduhan berbasis registry tabel dataset.
3. WebMap interaktif untuk preview data Level 1 dan Level 2.
4. Area admin untuk impor paket data dan metadata XML.

Secara sederhana, alurnya seperti ini:

`Browser -> Routes -> Controller -> Model/Library/Database -> View atau JSON -> JavaScript frontend`

Ada tiga kelompok database yang dipakai:

1. `mockup` -> database `MockUp`, schema `public`.
2. `gravport` -> database `gravport`, schema `testing`.
3. `auth` -> database `MockUp`, schema `auth`.

## 2. Struktur File Aktif

### Entry dan konfigurasi utama

- `app/Config/Routes.php`
  Menghubungkan URL ke controller. Ini file pertama yang perlu dibaca kalau ingin tahu request masuk ke mana.
- `app/Config/Database.php`
  Mendefinisikan tiga koneksi database yang dipakai aplikasi.
- `app/Config/Filters.php`
  Mendaftarkan alias filter `auth` dan `role`, lalu menghubungkan route dengan lapisan otorisasi.
- `app/Common.php`
  Menyediakan helper global untuk cek login dan role dari session.

### Controller utama

- `app/Controllers/Home.php`
  Landing dan beberapa halaman template lama.
- `app/Controllers/AuthController.php`
  Alur login/logout yang aktif dipakai route saat ini.
- `app/Controllers/Catalog.php`
  Halaman katalog, detail dataset, download CSV, dan output GeoJSON.
- `app/Controllers/WebMap.php`
  API utama untuk halaman WebMap.
- `app/Controllers/DatasetAdmin.php`
  Hub admin untuk membaca paket import dan menjalankan import.
- `app/Controllers/Metadata.php`
  Form metadata manual. Saat ini hanya validasi form, belum menyimpan ke tabel metadata XML.

### Library

- `app/Libraries/DatasetImportService.php`
  Pusat logika impor paket Level 1, Level 2, dan metadata XML.

### Model

- `app/Models/BaseDatasetModel.php`
  Logika filtering dasar untuk registry dataset.
- `app/Models/GravportDatasetModel.php`
  Registry dataset yang membaca tabel `gravport.testing.datasets`.
- `app/Models/MockUpDatasetModel.php`
  Registry dataset untuk database `mockup`.
- `app/Models/AuthUserModel.php`
  Lookup user pada schema `auth`.
- `app/Models/AuthUserRoleModel.php`
  Lookup dan bootstrap role user.
- `app/Models/AuthRoleModel.php`
  Model tabel role.

### View dan frontend

- `app/Views/partials/site_header.php`
  Header yang dipakai lintas halaman.
- `app/Views/v_catalog.php`
  Halaman katalog dataset.
- `app/Views/v_login.php`
  Halaman login.
- `app/Views/v_webmap.php`
  Shell HTML untuk WebMap dan injeksi endpoint ke JavaScript.
- `app/Views/v_admin_manage.php`
  Halaman admin untuk ringkasan paket dan tombol import.
- `public/site/js/webmap.js`
  Seluruh perilaku WebMap di sisi browser.

### Command line

- `app/Commands/ImportDatasetPackage.php`
  Jalur CLI untuk menjalankan import yang sama seperti tombol admin.

## 3. Peta Request dan Route

Route aktif dirangkai di `app/Config/Routes.php`.

### Route publik

- `/` -> `Home::index`
- `/catalog` -> `Catalog::index`
- `/catalog/view/{id}` -> `Catalog::view`
- `/catalog/download/{id}` -> `Catalog::download`
- `/catalog/geojson/{id}` -> `Catalog::geojson`
- `/webmap` -> `WebMap::index`
- `/login` -> `AuthController::loginForm` dan `AuthController::loginPost`
- `/logout` -> `AuthController::logout`

### Route admin

- `/metadata` -> `Metadata::index` dan `Metadata::store`
- `/dataset/manage` -> `DatasetAdmin::index`
- `/dataset/upload` -> `DatasetAdmin::upload`

Route admin memakai filter `role:admin`.

### Route API WebMap

- `/webmap/bootstrap`
- `/webmap/provinces`
- `/webmap/layer`
- `/webmap/feature-meta/{dataset}/{feature}`
- `/webmap/download/vector`
- `/webmap/clip/raster`
- `/webmap/download/raster/grid/{rid}`
- `/webmap/download/raster/province/{provinceId}`

Halaman `v_webmap.php` tidak mengambil data langsung dari database. Ia hanya memanggil endpoint ini dari `public/site/js/webmap.js`.

## 4. Alur Auth dan Session

### Komponen yang terlibat

- `AuthController`
- `AuthUserModel`
- `AuthUserRoleModel`
- `app/Common.php`
- `app/Filters/AuthFilter.php`
- `app/Filters/RoleFilter.php`
- `app/Views/partials/site_header.php`

### Urutan login

1. User membuka `/login`.
2. `AuthController::loginForm()` merender `v_login`.
3. Form submit ke `POST /login`.
4. `AuthController::loginPost()`:
   - membaca email dan password,
   - lookup user di `auth.users`,
   - memverifikasi `password_hash`,
   - mengambil role primer dari `auth.user_roles` dan `auth.roles`,
   - meregenerasi session,
   - menyimpan flag `logged_in`, `isLoggedIn`, `user_id`, `email`, `full_name`, dan `role`.
5. Jika role `admin`, user diarahkan ke `/dataset/manage`.
6. Jika role selain `admin`, user diarahkan ke `/catalog`.

### Helper global

`auth_is_logged_in()` di `app/Common.php` membaca `logged_in` atau `isLoggedIn` dari session.

`auth_current_role()`:

1. membaca role dari session,
2. bila email ada, mencoba sinkronkan lagi ke tabel auth,
3. menyimpan role hasil sinkronisasi kembali ke session,
4. fallback ke `user` jika tidak ada hasil.

### Filter

- `AuthFilter` hanya memastikan user login.
- `RoleFilter` memastikan user login lalu membandingkan role terhadap argumen route.

### Header

`app/Views/partials/site_header.php` memakai helper auth untuk:

- menampilkan tombol `Login` jika guest,
- menampilkan badge role dan tombol `Logout` jika login,
- menyembunyikan menu `Metadata` untuk non-admin.

## 5. Topologi Database

`app/Config/Database.php` mendefinisikan:

### Group `mockup`

- database: `MockUp`
- schema default: `public`
- dipakai oleh `MockUpDatasetModel`

### Group `gravport`

- database: `gravport`
- schema default: `testing`
- dipakai oleh katalog aktif, WebMap, dan hasil import paket

### Group `auth`

- database: `MockUp`
- schema default: `auth`
- dipakai oleh model login dan role

## 6. Registry Dataset dan Catalog

### Komponen yang terlibat

- `Catalog`
- `BaseDatasetModel`
- `GravportDatasetModel`
- `v_catalog`

### Alur

1. User membuka `/catalog`.
2. `Catalog::index()` membaca query string:
   - `q`
   - `per_page`
   - `page`
   - `downloadable`
   - `viewable`
   - `scope[]`
3. Filter dikirim ke `GravportDatasetModel::getFiltered()`.
4. `BaseDatasetModel::getFiltered()` membangun query ke tabel registry dataset.
5. Hasilnya dipakai untuk merender `v_catalog.php`.

### Download dari katalog

`Catalog::download($id)`:

1. memastikan dataset ada,
2. memastikan user login,
3. memastikan role `admin` atau `user`,
4. memastikan dataset `is_downloadable`,
5. membaca `data_schema` dan `data_table` dari registry,
6. mengeksekusi `SELECT * FROM schema.table`,
7. mengubah hasil menjadi CSV,
8. mengirim file ke browser.

### GeoJSON dari katalog

`Catalog::geojson($id)`:

1. memastikan dataset viewable,
2. membaca `data_schema`, `data_table`, `geom_column`,
3. mengambil daftar kolom selain geometri,
4. menyusun `FeatureCollection` langsung di SQL PostgreSQL,
5. mengembalikan JSON.

### Konsekuensi arsitektur

Katalog tidak otomatis tahu tabel hasil import terbaru. Agar katalog dan WebMap konsisten, registry `gravport.testing.datasets` harus diisi dengan schema, table, geom column, serta flag akses yang benar.

## 7. WebMap: Hubungan Frontend dan Backend

### Shell view

`app/Views/v_webmap.php` hanya merender:

- sidebar filter,
- container map Leaflet,
- detail drawer,
- variabel `window.WEBMAP_CONFIG`.

`window.WEBMAP_CONFIG` berisi URL backend, sehingga file JavaScript tidak menghardcode URL.

### State frontend

`public/site/js/webmap.js` memegang seluruh state browser:

- dataset aktif,
- mode filter spasial,
- layer provinsi,
- layer data,
- geometry hasil draw,
- geometry hasil upload,
- fitur yang sedang dipilih.

### Boot awal

Saat halaman siap:

1. `boot()` memanggil `/webmap/bootstrap`.
2. daftar dataset dimasukkan ke `Map()` di browser.
3. `loadProvinces()` memanggil `/webmap/provinces`.
4. `loadPreview(true)` memanggil `/webmap/layer`.

### Komposisi dataset aktif

Frontend membangun code dataset dengan pola:

`{anomaly}_{level}`

Contoh:

- `faa_l1`
- `cba_l1`
- `faa_l2`
- `cba_l2`

Daftar definisi dataset ada di properti `$datasets` milik `WebMap`.

### Level 1

Untuk dataset vektor, `WebMap::vectorLayer()` membaca:

- `testing.faa_l1_points`
- `testing.cba_l1_points`

Lalu mengembalikan GeoJSON `FeatureCollection`.

### Level 2

Untuk dataset raster, `WebMap::rasterGridLayer()` membaca:

- `testing.faa_l2_raster`
- `testing.cba_l2_raster`

Setiap row merepresentasikan satu grid. Data yang dikembalikan bukan TIFF penuh, tetapi poligon grid plus statistik raster:

- `min`
- `max`
- `mean`
- `width_px`
- `height_px`
- `grid_width_deg`
- `grid_height_deg`

Jika kolom `grid_geom` tersedia, WebMap memakai geometri itu. Jika belum, ia fallback ke `ST_Envelope(rast)`.

### Spatial filter

Filter spasial di WebMap punya tiga mode:

1. Provinsi.
2. Draw manual.
3. Upload GeoJSON/KML.

`webmap.js` menyatukan input itu menjadi payload JSON ke endpoint `/webmap/layer`, `/webmap/download/vector`, atau `/webmap/clip/raster`.

Di backend, `WebMap` mengubah payload itu menjadi ekspresi PostGIS melalui:

- `boundaryPayload()`
- `spatialSql()`
- `boundaryExpression()`

### Detail drawer

Saat user klik fitur:

1. popup cepat muncul di Leaflet,
2. browser memanggil `/webmap/feature-meta/{dataset}/{feature}`,
3. `WebMap::featureMeta()` mengarahkan request ke:
   - `vectorFeatureMeta()` untuk Level 1
   - `rasterFeatureMeta()` untuk Level 2
4. drawer di sisi kanan diisi ringkasan dan detail.

### Download

- Vector download -> `downloadVector()`
- Raster per grid -> `downloadRasterGrid($rid)`
- Raster per provinsi atau boundary -> `downloadRasterProvince()` atau `clipRaster()`

Semua unduhan raster memanfaatkan PostGIS `ST_AsTIFF`.

## 8. Alur Import Paket Dataset

### Komponen yang terlibat

- `DatasetAdmin`
- `DatasetImportService`
- `ImportDatasetPackage`
- `v_admin_manage`

### Titik masuk

Ada dua jalur eksekusi yang sama:

1. Tombol di `/dataset/manage`
2. CLI `php spark dataset:import <package>`

Keduanya memanggil `DatasetImportService::importPackage()`.

### Folder paket

Service membaca folder di bawah:

`writable/imports/<nama-paket>`

Struktur yang sekarang dipakai importer:

```text
writable/imports/<package>/
  level1/
    Metadata_Gravimetri_Level_1.xml
    faa/*.csv
    cba/*.csv
  level2/
    Metadata_Gravimetri_Level_2.xml
    faa/FAA.tif
    cba/CBA.tif
```

### Urutan import

`importPackage()` melakukan langkah ini dalam satu transaksi PostgreSQL:

1. memastikan tabel metadata ada,
2. memastikan tabel titik Level 1 ada,
3. memastikan tabel raster Level 2 ada,
4. import metadata XML level 1,
5. import metadata XML level 2,
6. import CSV FAA level 1,
7. import CSV CBA level 1,
8. import TIFF FAA level 2,
9. import TIFF CBA level 2,
10. commit.

Jika salah satu gagal, seluruh transaksi di-rollback.

### Import metadata XML

`importMetadataXml()`:

1. membaca file XML mentah,
2. mem-parse isi penting memakai `DOMDocument` dan `DOMXPath`,
3. menulis hasil ke `testing.dataset_metadata_xml`,
4. menyimpan juga `raw_xml`.

Metadata yang diambil meliputi:

- `file_identifier`
- `parent_identifier`
- `hierarchy_level_name`
- `metadata_date`
- `language_code`
- `character_set`
- `title`
- `abstract`
- data kontak
- email
- role kontak

### Import Level 1 CSV

`importLevel1Group()` dan `importCsvFile()`:

1. membaca semua CSV per grup `faa` atau `cba`,
2. mendeteksi mode survey dari nama file,
3. memetakan header ke field:
   - `Lintang`
   - `Bujur`
   - `Tinggi Ortometrik`
   - kolom `faa` atau `cba`
4. mengabaikan baris kosong,
5. melewati nilai invalid seperti `NaN`, `Inf`, atau non-numeric,
6. insert batch ke tabel titik,
7. membentuk `geom` dari longitude-latitude dengan SRID 4326.

Target tabel:

- `testing.faa_l1_points`
- `testing.cba_l1_points`

### Import Level 2 TIFF

`importRaster()` sekarang mengikuti grid geografis resmi BIG untuk indeks DEMNAS:

- ukuran grid: `0.125° x 0.125°`
- basis referensi: indeks `DEM_Nasional` di service resmi BIG

Urutan teknisnya:

1. membaca TIFF sebagai byte array,
2. membentuk raster PostGIS dengan `ST_FromGDALRaster`,
3. mengambil envelope raster,
4. melakukan snap extent ke kelipatan `0.125°`,
5. membangun grid poligon dengan `generate_series`,
6. melakukan `ST_Clip` raster ke setiap grid,
7. menyimpan hanya grid yang punya piksel valid,
8. menyimpan dua hal sekaligus:
   - `grid_geom`: poligon grid resmi
   - `rast`: raster hasil clip

Target tabel:

- `testing.faa_l2_raster`
- `testing.cba_l2_raster`

Struktur tabel raster sekarang:

- `rid`
- `source_file`
- `grid_geom`
- `rast`
- `imported_at`

Konsekuensi desain ini:

1. Tampilan grid di WebMap mengikuti grid resmi BIG, bukan amplop piksel sembarang.
2. Raster per grid tetap bisa diunduh per `rid`.
3. Statistik `mean`, `min`, dan `max` tetap dihitung dari data raster hasil clip.

## 9. Halaman Admin Manage

`DatasetAdmin::index()` merender `v_admin_manage` dengan dua blok data:

- `packageSummary`
- `importReport`

### `packageSummary`

Dipakai untuk:

- menampilkan paket terbaru,
- menampilkan jumlah CSV FAA/CBA,
- menampilkan path metadata XML,
- menampilkan path TIFF FAA/CBA.

### `importReport`

Dipakai untuk menampilkan hasil terakhir import:

- total titik FAA Level 1,
- total titik CBA Level 1,
- total grid FAA Level 2,
- total grid CBA Level 2,
- identifier metadata Level 1 dan Level 2.

## 10. Metadata Workspace

`Metadata` dan `v_metadata` saat ini masih berupa form manual dengan validasi input. Ia belum mengubah XML yang diimpor oleh `DatasetImportService`.

Artinya saat ini ada dua jalur metadata yang berbeda:

1. Jalur operasional nyata untuk paket import:
   - XML -> `DatasetImportService` -> `testing.dataset_metadata_xml`
2. Jalur form admin manual:
   - browser -> `Metadata::store()` -> validasi -> flash message

Kalau nanti ingin satu sumber kebenaran, form metadata ini perlu disambungkan ke tabel `dataset_metadata_xml` atau digunakan untuk menghasilkan XML baru.

## 11. File Legacy dan Technical Debt

Ada beberapa file dan pola yang masih hidup berdampingan:

### `Auth` vs `AuthController`

- `AuthController` adalah alur login aktif yang dipakai route sekarang.
- `Auth` adalah controller login lama berbasis `UserModel`.

Kalau dibiarkan, ini membingungkan karena ada dua gaya auth di repo.

### `UserModel` vs model auth baru

- `UserModel` dipakai controller lama `Auth`.
- `AuthUserModel` dan `AuthUserRoleModel` dipakai controller aktif `AuthController`.

### `GeodataModel`

`GeodataModel` mengandung query tabel lama seperti `faa_lvl2_grid` dan `faa_lvl1_scatter`, tetapi tidak dipakai oleh route WebMap aktif sekarang. Ini lebih cocok diperlakukan sebagai artefak prototipe lama.

### Duplicate route entries

`Routes.php` masih punya beberapa route `webmap` yang didefinisikan dua kali. Secara praktik route terakhir akan menang, tetapi file ini sebaiknya dirapikan agar tidak menimbulkan salah tafsir.

### `DatasetAdmin::delete()`

Method delete sudah ada endpoint-nya, tetapi isi logikanya masih `TODO`.

### Organisasi BIG di UI

UI WebMap menampilkan pilihan organisasi `BIG`, tetapi definisi dataset aktif di `WebMap::$datasets` saat ini semuanya masih bertanda organisasi `ITB`. Jadi filter organisasi BIG belum punya source aktif sendiri.

### Katalog dan WebMap belum otomatis sinkron

WebMap memakai definisi dataset hardcoded di `WebMap::$datasets`, sedangkan katalog memakai registry tabel `datasets`. Kalau dataset aktif berubah, dua tempat ini harus dijaga tetap selaras.

## 12. Extension Point yang Paling Aman

Kalau ingin mengembangkan sistem tanpa memecahkan alur yang ada, titik yang paling aman adalah:

1. Tambah field baru hasil import di `DatasetImportService`, lalu expose ke `WebMap`.
2. Hubungkan form `Metadata` ke `dataset_metadata_xml`.
3. Tambah sinkronisasi otomatis dari hasil import ke registry `testing.datasets`.
4. Rapikan route dan hapus controller/model legacy yang sudah tidak dipakai.
5. Tambahkan layer registry untuk dataset WebMap agar definisi dataset tidak hardcoded di controller.

## 13. Ringkasan Hubungan Antar Modul

### Untuk katalog

`Routes -> Catalog -> GravportDatasetModel -> gravport.testing.datasets -> v_catalog`

### Untuk login

`Routes -> AuthController -> AuthUserModel/AuthUserRoleModel -> auth.* tables -> session -> site_header/filter`

### Untuk WebMap

`v_webmap -> public/site/js/webmap.js -> WebMap API -> gravport.testing.* tables -> JSON -> Leaflet`

### Untuk import admin

`v_admin_manage -> DatasetAdmin -> DatasetImportService -> XML/CSV/TIFF package -> gravport.testing tables`

### Untuk metadata XML

`writable/imports/.../Metadata_*.xml -> DatasetImportService::parseMetadataXml() -> testing.dataset_metadata_xml`

## 14. Saran Membaca Repo

Urutan baca tercepat untuk memahami proyek ini:

1. `app/Config/Routes.php`
2. `app/Common.php`
3. `app/Controllers/AuthController.php`
4. `app/Controllers/Catalog.php`
5. `app/Controllers/WebMap.php`
6. `app/Libraries/DatasetImportService.php`
7. `app/Views/v_webmap.php`
8. `public/site/js/webmap.js`
9. `app/Views/v_admin_manage.php`

Dengan urutan ini, hubungan request, role, data, dan tampilan akan lebih cepat terlihat daripada membaca file acak satu per satu.
