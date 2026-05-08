# Skema Pengujian Geoportal untuk Capstone

Dokumen ini menyusun strategi pengujian yang sesuai dengan kondisi aplikasi geoportal pada repo ini. Fokusnya bukan hanya memastikan fitur berjalan, tetapi juga membuktikan bahwa alur data spasial, impor dataset, katalog, metadata, dan WebMap konsisten dari sisi backend sampai antarmuka pengguna.

## 1. Ringkasan rekomendasi

Jika Anda perlu memilih metode yang paling kuat namun tetap realistis untuk capstone, gunakan kombinasi berikut:

1. Black-box functional testing
2. Integration testing
3. User Acceptance Testing (UAT)
4. Smoke performance testing

Ini adalah kombinasi terbaik untuk dipresentasikan karena:

- mudah dipahami pembimbing dan penguji,
- langsung terkait dengan fitur nyata aplikasi,
- kuat untuk sistem geoportal yang bergantung pada integrasi database spasial,
- masih realistis dijalankan tanpa membangun pipeline QA industri penuh.

Jika ingin menambah nilai teknis, tambahkan:

1. API regression testing
2. Limited backend unit testing
3. Basic security testing

## 2. Kenapa metode campuran paling cocok

Geoportal ini bukan aplikasi CRUD biasa. Dari hasil kajian kode, sistem ini punya beberapa lapisan yang saling bergantung:

1. Auth dan role-based access
2. Catalog dan download dataset
3. Metadata form dan metadata export
4. Admin import package
5. WebMap interaktif
6. PostgreSQL/PostGIS untuk point, raster, grid, dan metadata XML

Karena itu, satu metode tunggal tidak cukup:

- Black-box saja tidak bisa membuktikan integritas tabel hasil impor.
- Unit testing saja tidak cukup membuktikan peta, clipping raster, dan filter spasial benar.
- UAT saja terlalu subjektif jika tidak dilengkapi bukti teknis.

Metode campuran memberi hasil yang paling seimbang untuk laporan dan sidang.

## 3. Opsi metode pengujian yang bisa Anda diskusikan

### Opsi A. Black-box functional testing

Tujuan:
Memastikan setiap fitur utama bekerja sesuai kebutuhan pengguna tanpa melihat detail implementasi internal.

Cocok untuk:

- login/logout,
- akses admin vs user,
- katalog dataset,
- download CSV/TIFF/XML,
- WebMap preview,
- upload AOI GeoJSON/KML,
- import package via admin.

Kelebihan:

- paling mudah dijelaskan di laporan,
- kuat untuk validasi requirement,
- cocok dijalankan manual dengan test case sheet.

Kekurangan:

- kurang kuat untuk membuktikan konsistensi data di database.

### Opsi B. White-box / unit testing

Tujuan:
Menguji fungsi, class, atau method backend secara terisolasi.

Cocok untuk:

- `DatasetImportService`,
- `GeoportalDatasetRegistry`,
- `FilteredMetadataExporter`,
- validasi auth helper/filter.

Kelebihan:

- menunjukkan kedalaman teknis,
- memudahkan regression testing.

Kekurangan:

- effort lebih tinggi,
- saat ini repo belum menyiapkan suite pengujian backend yang berarti,
- `vendor/bin/phpunit` belum tersedia di checkout ini sehingga perlu `composer install` lebih dulu.

### Opsi C. Integration testing

Tujuan:
Menguji hubungan antar komponen, terutama controller -> service -> database -> output.

Cocok untuk:

- import package ke PostgreSQL/PostGIS,
- sinkronisasi metadata XML ke tabel,
- WebMap membaca tabel hasil import,
- katalog dan WebMap memakai sumber aktif yang sama,
- clipping raster dan download per grid/provinsi.

Kelebihan:

- paling relevan dengan karakter geoportal ini,
- kuat untuk menunjukkan sistem benar-benar bekerja end-to-end,
- sangat bagus untuk sidang karena bisa didemokan.

Kekurangan:

- perlu data uji yang konsisten,
- perlu lingkungan DB siap.

### Opsi D. System / end-to-end testing

Tujuan:
Menguji alur lengkap dari sudut pandang pengguna.

Cocok untuk:

- admin login -> import package -> buka WebMap -> preview data -> download metadata,
- user login -> buka katalog -> lihat detail -> download dataset.

Kelebihan:

- sangat komunikatif untuk presentasi,
- mudah dipahami pembimbing.

Kekurangan:

- sulit dilokalisasi ketika gagal,
- tetap perlu test case detail agar tidak menjadi demo biasa.

### Opsi E. User Acceptance Testing (UAT)

Tujuan:
Membuktikan bahwa aplikasi diterima oleh pengguna sasaran.

Cocok untuk:

- dosen pembimbing,
- operator/admin data,
- calon pengguna non-teknis.

Kelebihan:

- sangat kuat untuk laporan capstone,
- menghubungkan hasil teknis dengan kebutuhan pengguna.

Kekurangan:

- sifatnya subjektif,
- harus dipasangkan dengan pengujian fungsional dan integrasi.

### Opsi F. Performance testing

Tujuan:
Mengukur respons dasar sistem pada skenario penting.

Cocok untuk:

- waktu preview WebMap,
- waktu import package,
- waktu download raster/vector,
- respons filter viewport/provinsi.

Kelebihan:

- memberi nilai tambah profesional,
- penting karena WebMap dan raster sensitif terhadap performa.

Kekurangan:

- jangan dibuat terlalu besar untuk capstone,
- cukup gunakan smoke performance, tidak perlu load testing skala besar kecuali diminta.

### Opsi G. Security testing

Tujuan:
Memeriksa kontrol akses dan validasi input.

Cocok untuk:

- bypass role admin,
- akses download tanpa login,
- input file upload AOI,
- validasi form metadata,
- request API dengan parameter tidak valid.

Kelebihan:

- bagus untuk menunjukkan kepedulian mutu sistem.

Kekurangan:

- tidak perlu dibawa terlalu dalam jika fokus capstone Anda adalah fungsi geoportal.

## 4. Rekomendasi akhir untuk capstone

Kombinasi yang paling baik untuk Anda:

1. Black-box functional testing sebagai fondasi utama
2. Integration testing sebagai pembuktian inti sistem geoportal
3. UAT sebagai pembuktian penerimaan pengguna
4. Smoke performance testing sebagai pelengkap

Jika pembimbing ingin nilai teknis lebih:

5. Tambahkan beberapa backend/API regression test

## 5. Alur pengujian yang disarankan

Gunakan urutan ini agar rapi di laporan:

### Tahap 1. Persiapan

Siapkan:

1. akun `admin`
2. akun `user`
3. paket impor uji di `writable/imports/<package>`
4. bukti struktur file paket
5. data AOI uji dalam format `GeoJSON` dan bila perlu `KML`
6. spreadsheet test case
7. form bug report
8. form UAT

### Tahap 2. Validasi lingkungan

Periksa:

1. aplikasi bisa dijalankan,
2. koneksi database `gravport` aktif,
3. command `php spark dataset:import` tersedia,
4. route WebMap dan katalog aktif.

### Tahap 3. Functional testing

Uji modul:

1. autentikasi,
2. otorisasi,
3. katalog,
4. metadata,
5. admin import,
6. WebMap,
7. download data.

### Tahap 4. Integration testing

Fokus pada alur:

1. package import -> tabel point/raster/XML,
2. registry dataset -> katalog,
3. WebMap -> endpoint layer/feature-meta/download,
4. filter spasial -> hasil data yang sesuai,
5. metadata export -> sesuai dataset dan filter aktif.

### Tahap 5. UAT

Minta 2 sampai 5 evaluator mencoba skenario kunci:

1. admin mengimpor data,
2. pengguna mencari dan melihat dataset,
3. pengguna mengunduh data,
4. pengguna memakai filter spasial di WebMap.

### Tahap 6. Rekap dan analisis

Rekap:

1. total test case,
2. pass/fail,
3. defect per modul,
4. hasil UAT,
5. waktu respons utama,
6. kesimpulan kesiapan sistem.

## 6. Area uji prioritas berdasarkan kode aktif

### Prioritas tinggi

1. Login, logout, dan role filter
2. Import package dari admin dan CLI
3. Konsistensi data Level 1, Level 2, dan metadata XML setelah import
4. WebMap layer preview, detail feature, dan unduhan
5. Download CSV, raster, dan metadata XML dari katalog/WebMap

### Prioritas menengah

1. Upload boundary `GeoJSON`/`KML`
2. Filtering per provinsi, viewport, dan geometry
3. Metadata form validation
4. Search dan paging katalog

### Prioritas diskusi / known limitation

Beberapa titik dari kode yang justru bagus dibawa ke pembimbing sebagai bahan diskusi:

1. `Metadata::store()` saat ini memvalidasi form, tetapi belum menyimpan metadata manual ke tabel.
2. `DatasetAdmin::delete()` masih `TODO`, jadi pengujian delete bisa dicatat sebagai gap sistem.
3. WebMap search memakai layanan eksternal OSM Nominatim, sehingga hasil uji pencarian lokasi bergantung konektivitas internet.

## 7. Data dan format file yang perlu disiapkan

### A. Paket impor utama

Struktur yang saat ini dipakai aplikasi:

```text
writable/imports/<nama-paket>/
  level1/
    Metadata_Gravimetri_Level_1.xml
    faa/
      *.csv
      *.rar
    cba/
      *.csv
      *.rar
  level2/
    Metadata_Gravimetri_Level_2.xml
    faa/
      FAA.tif
    cba/
      CBA.tif
```

### B. Format CSV Level 1

Minimal kolom yang dibaca importer:

1. `Lintang`
2. `Bujur`
3. `Tinggi Ortometrik` atau `Tinggi Ort`
4. `FAA` untuk grup FAA atau `CBA` untuk grup CBA

Catatan:

- File kosong atau baris dengan nilai tidak valid akan di-skip.
- Nama file dipakai untuk mendeteksi `survey_mode` seperti `airborne` atau `terestris`.

### C. Format metadata XML

Importer membaca metadata XML dengan namespace `gmd` dan `gco`, lalu mengambil bidang seperti:

1. `fileIdentifier`
2. `parentIdentifier`
3. `hierarchyLevelName`
4. `dateStamp`
5. `language`
6. `characterSet`
7. `title`
8. `abstract`
9. data kontak dan email
10. `CI_RoleCode`

### D. Format raster Level 2

Format aktif:

1. `TIFF`
2. diproses menjadi grid raster di PostGIS
3. diselaraskan ke grid `0.125` derajat

### E. Format boundary untuk WebMap

Saat ini frontend menerima:

1. `GeoJSON` / `.geojson`
2. `JSON`
3. `KML`

## 8. Artefak bukti yang perlu Anda kumpulkan

Untuk laporan dan sidang, kumpulkan bukti berikut:

1. screenshot login berhasil/gagal,
2. screenshot halaman katalog,
3. screenshot WebMap untuk Level 1 dan Level 2,
4. screenshot filter provinsi/draw/upload,
5. screenshot hasil unduhan,
6. screenshot admin import dan laporan hasil import,
7. log hasil `php spark dataset:import`,
8. rekap isi tabel hasil import,
9. file output metadata XML hasil filter,
10. spreadsheet hasil test case,
11. form UAT yang sudah diisi evaluator,
12. bug report untuk kasus gagal.

## 9. Tools yang disarankan

### Minimal

1. Browser
2. Spreadsheet Excel/Google Sheets
3. Postman atau Insomnia
4. Screenshot folder untuk evidence

### Nilai tambah

1. PHPUnit untuk backend regression test
2. JMeter atau k6 untuk smoke performance
3. OWASP ZAP untuk cek dasar keamanan

## 10. Struktur bab pengujian yang bisa dipakai di laporan

Anda bisa memakai struktur ini:

1. Tujuan pengujian
2. Skenario dan metode pengujian
3. Lingkungan pengujian
4. Data uji dan format file
5. Hasil pengujian fungsional
6. Hasil pengujian integrasi
7. Hasil UAT
8. Hasil pengujian performa dasar
9. Analisis temuan
10. Kesimpulan

## 11. Cara menjelaskan ke pembimbing

Narasi yang paling aman untuk dipresentasikan:

"Saya menggunakan strategi pengujian campuran. Black-box saya pakai untuk membuktikan semua fitur utama geoportal berjalan sesuai kebutuhan. Integration testing saya pakai karena sistem ini bergantung pada konsistensi data spasial dari proses impor sampai tampil di WebMap. UAT saya tambahkan agar ada validasi dari sisi calon pengguna. Sebagai pelengkap, saya lakukan smoke performance test pada fitur yang sensitif seperti preview peta, unduhan raster, dan impor paket."

## 12. Dokumen pendamping yang disiapkan

Dokumen pendamping pada folder ini:

1. `templates/test-case-matrix.csv`
2. `templates/test-execution-log.csv`
3. `templates/uat-form.md`
4. `templates/bug-report.md`
5. `templates/import-package-checklist.md`
6. `templates/test-data-inventory.md`
7. `samples/sample-aoi.geojson`
8. `samples/sample-aoi.kml`
