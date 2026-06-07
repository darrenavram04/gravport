# CI4 Refactor Checklist: Migrasi ke geoportal.* Schema

## PETA CONNECTION GROUP (Database.php)

| Group    | DB       | Schema    | Dipakai oleh                                      |
|----------|----------|-----------|---------------------------------------------------|
| mockup   | geoportal| public    | (legacy — tidak aktif dipakai)                    |
| gravport | geoportal| gravport  | PendingRegistrationModel, WebMap, Catalog, dll     |
| geoportal| geoportal| gravport  | Model-model baru (alias gravport)                  |
| auth     | geoportal| auth      | RegistrationController, InvoiceModel, auth-api     |

## STATUS MODEL

### Model SUDAH BENAR (gravport.* dalam geoportal DB)
- [x] PendingRegistrationModel   → gravport.pending_registrations/organizations ✓
- [x] ApiKeyModel                → gravport.api_keys ✓
- [x] DataProviderModel          → gravport.data_providers ✓
- [x] DownloadTransactionModel   → gravport.download_transactions ✓
- [x] InvoiceModel               → gravport.invoices ✓
- [x] RevenueShareModel          → gravport.revenue_shares ✓

### Model PERLU PERIKSA / UPDATE

#### OrganizationModel (sudah difix nama kolom)
- [x] gravport.organizations  (organization_id, organization_name, organization_email)
- [ ] Jika ingin pakai geoportal.organizations: ganti ke org_id, org_name, org_email

#### SubscriptionModel
- [x] gravport.subscriptions + gravport.subscription_tiers (sudah benar)
- [ ] Jika migrasi ke geoportal.subscriptions:
      - tier_id berubah (8→1, 9→2, 10→3)
      - kolom: subs_id, payment_status (P/S/E), payment_cycle, remaining_download_byte

## CHECKLIST LENGKAP MVC

### DATABASE CONFIG
- [ ] Database.php: semua group sudah mengarah ke database 'geoportal' ✓

### MODELS
- [ ] Cek setiap model: apakah schema prefix sudah 'gravport.' atau 'geoportal.'
- [ ] TIDAK ada lagi referensi ke 'testing.' atau 'public.' ✓
- [ ] TIDAK ada lagi referensi ke MockUp atau gravport (database lama) ✓

### CONTROLLERS
- [ ] Tidak ada hardcoded database name (MockUp, gravport database lama)
- [ ] connect('auth') → auth.users dalam geoportal DB ✓
- [ ] connect('gravport') → gravport.* dalam geoportal DB ✓
- [ ] connect('geoportal') → gravport.* dalam geoportal DB ✓

### LIBRARIES
- [ ] GeoportalDatasetRegistry: gravport.faa_l1_points, gravport.land_administrative_areas ✓
- [ ] DatasetImportService: gravport.dataset_metadata_xml ✓
- [ ] FilteredMetadataExporter: gravport.dataset_metadata_xml ✓
- [ ] MetadataSubmissionService: gravport.dataset_user_submissions ✓

### AUTH API (.env)
- [ ] PGDATABASE=geoportal ✓
- [ ] PGSCHEMA=auth ✓

### QUERY YANG MASIH PERLU DIVERIFIKASI
Jalankan ini di browser untuk konfirmasi tidak ada error:
- /catalog
- /webmap
- /account
- /login (termasuk OTP)
- /signup
- /webmap + Unduh CSV
- /webmap + Unduh Metadata
