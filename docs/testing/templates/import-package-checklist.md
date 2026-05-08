# Checklist Uji Import Package

Gunakan checklist ini setiap kali menguji impor paket dataset.

## Sebelum import

- [ ] Folder paket tersedia di `writable/imports/<package>`
- [ ] Terdapat `level1/Metadata_Gravimetri_Level_1.xml`
- [ ] Terdapat `level2/Metadata_Gravimetri_Level_2.xml`
- [ ] Folder `level1/faa` berisi file `*.csv`
- [ ] Folder `level1/cba` berisi file `*.csv`
- [ ] Folder `level2/faa` berisi `FAA.tif`
- [ ] Folder `level2/cba` berisi `CBA.tif`
- [ ] Koneksi PostgreSQL/PostGIS aktif
- [ ] Akun admin aktif
- [ ] Backup atau catatan kondisi tabel sebelum import sudah dibuat bila diperlukan

## Saat import via UI

- [ ] Login sebagai admin
- [ ] Buka `/dataset/manage`
- [ ] Pastikan paket yang tampil sesuai
- [ ] Klik tombol import
- [ ] Pastikan muncul pesan sukses atau pesan error yang jelas
- [ ] Simpan screenshot hasil import

## Saat import via CLI

- [ ] Jalankan `php spark dataset:import <package>`
- [ ] Simpan output command
- [ ] Pastikan jumlah row Level 1 dan Level 2 tercetak
- [ ] Pastikan metadata Level 1 dan Level 2 tercetak

## Verifikasi database

- [ ] `testing.faa_l1_points` terisi
- [ ] `testing.cba_l1_points` terisi
- [ ] `testing.faa_l2_raster` terisi
- [ ] `testing.cba_l2_raster` terisi
- [ ] `testing.dataset_metadata_xml` memiliki baris `level1`
- [ ] `testing.dataset_metadata_xml` memiliki baris `level2`
- [ ] `grid_geom` terisi pada tabel raster
- [ ] Lebar dan tinggi `grid_geom` sesuai `0.125`

## Verifikasi aplikasi

- [ ] Dataset hasil import muncul di katalog
- [ ] Level 1 dapat dipreview di WebMap
- [ ] Level 2 dapat dipreview di WebMap
- [ ] Download vector berjalan
- [ ] Download raster berjalan
- [ ] Download metadata berjalan

## Hasil akhir

- Status:
- Tanggal:
- Penguji:
- Catatan:

