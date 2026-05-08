<?php
$provinces = [
    'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi',
    'Sumatera Selatan', 'Kepulauan Bangka Belitung', 'Bengkulu', 'Lampung',
    'DKI Jakarta', 'Jawa Barat', 'Banten', 'Jawa Tengah', 'DI Yogyakarta',
    'Jawa Timur', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
    'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur',
    'Kalimantan Utara', 'Sulawesi Utara', 'Gorontalo', 'Papua', 'Papua Barat',
    'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan', 'Papua Barat Daya',
];

$characterSets = ['JIS', 'shiftJIS', 'UCS2', 'UCS4', 'USASCII', 'UTF16', 'UTF7', 'UTF8 (default)'];
$hierarchyLevels = [
    'Attribute', 'Attribute Type', 'Collection Hardware', 'Collection Session', 'Dataset (default)',
    'Dimension Group', 'Feature', 'Feature Type', 'Field Session', 'Model',
    'Non Geographic Dataset', 'Property Type', 'Series', 'Service', 'Software', 'Tile',
];
$contactRoles = ['Author', 'Custodian', 'Distributor', 'Originator', 'Owner', 'Publisher', 'Resource Provider', 'User'];
$formIntro = 'Formulir ini digunakan untuk mengisi metadata Geoportal sesuai standar ISO 19115. Lengkapi setiap bagian menggunakan informasi resmi dari produsen atau walidata data, dan gunakan guidebook metadata sebagai rujukan saat diperlukan.';
$sectionDescriptions = [
    'basic' => 'Bagian ini membentuk identitas utama metadata. Gunakan kombinasi jenis data, provinsi, dan level data untuk menjaga format penamaan tetap konsisten.',
    'general' => 'Bagian ini bertujuan untuk mengumpulkan identitas dan informasi dasar terkait dokumen metadata yang sedang disusun. Data yang Anda masukkan di sini mencakup detail penanggung jawab, kaitan hierarki data, serta standar metadata yang menjadi acuan pembuatan.',
    'data_contact' => 'Bagian ini bertujuan untuk mendata secara rinci pihak-pihak yang bertanggung jawab atas pembuatan, pengelolaan, atau publikasi data geospasial ini. Kontak ini dapat berupa unit produsen data maupun walidata.',
    'contact' => 'Lengkapi informasi telepon aktif dan nomor faksimili pihak penanggung jawab agar jalur komunikasi data tetap jelas.',
    'address' => 'Bagian ini bertujuan untuk mendata informasi lokasi fisik, alamat surat-menyurat, serta kontak elektronik dari pihak atau instansi yang bertanggung jawab atas data geospasial tersebut.',
];
$fieldDescriptions = [
    'metadata_file_identifier' => 'Gunakan identifier sebagai identitas unik file metadata. Format yang direkomendasikan mengikuti kombinasi jenis data, provinsi, dan level data.',
    'jenis_data' => 'Pilih metode pengukuran atau jenis data yang Anda miliki, antara Airborne atau Gravimetri.',
    'provinsi' => 'Pilih nama wilayah tingkat provinsi yang menjadi cakupan area data Anda.',
    'level_data' => 'Pilih level 1 jika data berupa SHP atau CSV, dan level 2 jika data yang dimiliki berupa TIFF.',
    'bahasa' => 'Pilih bahasa metadata. Anda dapat menggunakan English sebagai default, atau Bahasa Indonesia jika seluruh isi metadata ditulis dalam bahasa Indonesia.',
    'character_set' => 'Pilih standar karakter yang digunakan. UTF-8 sangat disarankan karena merupakan standar pengkodean teks yang paling umum dipakai.',
    'hierarchy_level' => 'Pilih tingkatan yang paling merepresentasikan produk data Anda. Untuk kebanyakan hasil pengukuran, opsi dataset adalah pilihan yang paling sesuai.',
    'metadata_date_stamp' => 'Isi tanggal pembuatan metadata atau tanggal saat formulir ini diisi menggunakan pemilih tanggal yang tersedia.',
    'individual_name' => 'Tulis nama individu, tim, atau pimpinan organisasi yang menjadi penanggung jawab utama data. Jika lebih dari satu, pisahkan dengan tanda koma.',
    'organisation_name' => 'Tulis nama institusi, pusat, direktorat, atau dinas resmi yang menaungi penanggung jawab data secara lengkap.',
    'position_name' => 'Isi jabatan, posisi operasional, atau nama kelompok kerja dari pihak yang telah disebutkan sebelumnya.',
    'contact_role' => 'Pilih peran utama pihak atau organisasi tersebut terhadap pengelolaan, kepemilikan, atau publikasi data geospasial.',
    'voice' => 'Masukkan nomor telepon aktif dari pihak penanggung jawab yang dapat dihubungi secara langsung.',
    'facsimilie' => 'Masukkan nomor faksimili instansi atau pihak penanggung jawab secara lengkap.',
    'delivery_point' => 'Isi alamat fisik spesifik untuk pengiriman dokumen atau lokasi detail instansi atau individu penanggung jawab.',
    'city' => 'Tulis nama kota atau kabupaten tempat instansi atau individu tersebut berada.',
    'administrative_area' => 'Isi wilayah administratif yang menaungi lokasi tersebut, misalnya kecamatan, kelurahan, atau provinsi.',
    'postal_code' => 'Masukkan kode pos wilayah alamat instansi atau individu yang sesuai.',
    'country' => 'Tulis negara tempat instansi atau individu tersebut berdomisili.',
    'electronic_mail_address' => 'Masukkan email resmi penanggung jawab. Jika lebih dari satu alamat email, pisahkan dengan tanda koma.',
];
$roleGuidance = [
    [
        'label' => 'Author',
        'description' => 'Pihak yang menyusun, merancang, atau menulis dokumen metadata.',
        'example' => 'Staf administrasi atau penulis teknis metadata.',
    ],
    [
        'label' => 'Custodian',
        'description' => 'Pihak yang bertindak sebagai pusat data atau walidata yang menjaga dan mengelola data geospasial.',
        'example' => 'Diskominfo atau lembaga pusat data.',
    ],
    [
        'label' => 'Distributor',
        'description' => 'Pihak yang bertanggung jawab terhadap penyebarluasan data geospasial kepada pengguna.',
        'example' => 'Unit pusat data yang mendistribusikan data melalui layanan web atau API.',
    ],
    [
        'label' => 'Originator',
        'description' => 'Pihak utama yang pertama kali mengumpulkan, menciptakan, atau menginisiasi dataset.',
        'example' => 'Tim ahli atau instansi inisiator program pengukuran.',
    ],
    [
        'label' => 'Owner',
        'description' => 'Pihak yang memiliki hak penuh atas data atau bertindak langsung sebagai unit produsen data.',
        'example' => 'Direktorat teknis, produsen data, atau BIG.',
    ],
    [
        'label' => 'Publisher',
        'description' => 'Pihak yang mempublikasikan dan menyajikan data secara resmi agar dapat diakses publik.',
        'example' => 'Pengelola situs resmi institusi.',
    ],
    [
        'label' => 'Resource Provider',
        'description' => 'Pihak yang menyediakan infrastruktur, platform, perangkat keras, atau fasilitas hosting data.',
        'example' => 'Penyedia server, vendor IT, atau pusat infrastruktur jaringan.',
    ],
    [
        'label' => 'User',
        'description' => 'Pihak pengguna akhir yang memanfaatkan data geospasial yang sudah dipublikasikan.',
        'example' => 'Masyarakat umum, mahasiswa, peneliti, atau analis tata ruang.',
    ],
];
$errors = session()->getFlashdata('errors') ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GravPort | Metadata</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url('site/css/bootstrap.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/style.css?v=26') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
  <link rel="stylesheet" href="<?= base_url('site/css/metadata.css?v=3') ?>">
</head>
<body class="metadata-page">

<div class="metadata-backdrop" aria-hidden="true"></div>

<?= view('partials/site_header', [
    'activePage' => 'metadata',
]) ?>

<main class="metadata-shell">
  <section class="metadata-topbar">
    <div>
      <span class="metadata-kicker">Authorized User</span>
      <h1>Metadata & Upload Form</h1>
      <p class="metadata-intro">
        <?= esc($formIntro) ?>
        <a href="<?= base_url('guidebooks/metadata-guidebook-dummy.pdf') ?>" target="_blank" rel="noopener">Buka guidebook</a>
      </p>
    </div>
    <div class="meta-chip-row">
      <span class="meta-chip">All fields required</span>
      <span class="meta-chip">ISO 19115</span>
      <span class="meta-chip">ZIP SHP, Excel, TIFF</span>
    </div>
  </section>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success metadata-alert"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger metadata-alert">
      <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
          <li><?= esc($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <section class="metadata-layout">
    <aside class="metadata-sidebar">
      <article class="metadata-card metadata-sidebar__card">
        <div class="metadata-sidebar__nav">
          <a class="metadata-sidebar__link is-active" href="#meta-basic" data-meta-nav="meta-basic">
            <span class="metadata-sidebar__number">01</span>
            <span class="metadata-sidebar__title">Basic</span>
          </a>
          <a class="metadata-sidebar__link" href="#meta-general" data-meta-nav="meta-general">
            <span class="metadata-sidebar__number">02</span>
            <span class="metadata-sidebar__title">General Information Metadata</span>
          </a>
          <a class="metadata-sidebar__link" href="#meta-data-contact" data-meta-nav="meta-data-contact">
            <span class="metadata-sidebar__number">03</span>
            <span class="metadata-sidebar__title">Data Contact Information</span>
          </a>
          <a class="metadata-sidebar__link" href="#meta-contact" data-meta-nav="meta-contact">
            <span class="metadata-sidebar__number">04</span>
            <span class="metadata-sidebar__title">Contact Information</span>
          </a>
          <a class="metadata-sidebar__link" href="#meta-address" data-meta-nav="meta-address">
            <span class="metadata-sidebar__number">05</span>
            <span class="metadata-sidebar__title">Address</span>
          </a>
        </div>
      </article>
    </aside>

    <form class="metadata-form" method="post" action="<?= site_url('metadata') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <article class="metadata-card meta-section" id="meta-basic">
        <div class="meta-section__header">
          <span class="meta-section__eyebrow">Basic</span>
          <h2>Metadata File Identifier</h2>
          <p class="meta-section__description"><?= esc($sectionDescriptions['basic']) ?></p>
        </div>

        <div class="meta-grid">
          <label class="meta-field">
            <span>Metadata File Identifier</span>
            <p class="meta-field__help">
              <?= esc($fieldDescriptions['metadata_file_identifier']) ?>
              <code>Metadata_Gravimetri_Banten_Level_1</code>
            </p>
            <input
              name="metadata_file_identifier"
              type="text"
              value="<?= esc(old('metadata_file_identifier')) ?>"
              placeholder="Metadata_Gravimetri_Banten_Level_1"
              required
            >
          </label>

          <div class="meta-field">
            <legend>Jenis Data</legend>
            <p class="meta-field__help"><?= esc($fieldDescriptions['jenis_data']) ?></p>
            <div class="meta-choice-group">
              <label class="meta-choice">
                <input type="radio" name="jenis_data" value="Airborne" <?= old('jenis_data') === 'Airborne' ? 'checked' : '' ?> required>
                <span>Airborne</span>
              </label>
              <label class="meta-choice">
                <input type="radio" name="jenis_data" value="Gravimetri" <?= old('jenis_data', 'Gravimetri') === 'Gravimetri' ? 'checked' : '' ?>>
                <span>Gravimetri</span>
              </label>
            </div>
          </div>

          <label class="meta-field">
            <span>Provinsi</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['provinsi']) ?></p>
            <select name="provinsi" required>
              <option value="">Pilih provinsi</option>
              <?php foreach ($provinces as $province): ?>
                <option value="<?= esc($province) ?>" <?= old('provinsi') === $province ? 'selected' : '' ?>><?= esc($province) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <div class="meta-field">
            <legend>Level Data</legend>
            <p class="meta-field__help"><?= esc($fieldDescriptions['level_data']) ?></p>
            <div class="meta-choice-group">
              <label class="meta-choice">
                <input type="radio" name="level_data" value="Level 1" <?= old('level_data', 'Level 1') === 'Level 1' ? 'checked' : '' ?> required>
                <span>Level 1</span>
              </label>
              <label class="meta-choice">
                <input type="radio" name="level_data" value="Level 2" <?= old('level_data') === 'Level 2' ? 'checked' : '' ?>>
                <span>Level 2</span>
              </label>
            </div>
          </div>
        </div>
      </article>

      <article class="metadata-card meta-section" id="meta-general">
        <div class="meta-section__header">
          <span class="meta-section__eyebrow">General Information Metadata</span>
          <h2>General Information Metadata</h2>
          <p class="meta-section__description"><?= esc($sectionDescriptions['general']) ?></p>
        </div>

        <div class="meta-grid">
          <label class="meta-field">
            <span>Bahasa</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['bahasa']) ?></p>
            <select name="bahasa" required>
              <option value="">Pilih bahasa</option>
              <option value="English" <?= old('bahasa', 'English') === 'English' ? 'selected' : '' ?>>English</option>
              <option value="Bahasa Indonesia" <?= old('bahasa') === 'Bahasa Indonesia' ? 'selected' : '' ?>>Bahasa Indonesia</option>
            </select>
          </label>

          <label class="meta-field">
            <span>Character Set</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['character_set']) ?></p>
            <select name="character_set" required>
              <option value="">Pilih character set</option>
              <?php foreach ($characterSets as $item): ?>
                <option value="<?= esc($item) ?>" <?= old('character_set', 'UTF8 (default)') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="meta-field">
            <span>Hierarchy Level</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['hierarchy_level']) ?></p>
            <select name="hierarchy_level" required>
              <option value="">Pilih hierarchy level</option>
              <?php foreach ($hierarchyLevels as $item): ?>
                <option value="<?= esc($item) ?>" <?= old('hierarchy_level', 'Dataset (default)') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="meta-field">
            <span>Metadata Date Stamp</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['metadata_date_stamp']) ?></p>
            <input name="metadata_date_stamp" type="date" value="<?= esc(old('metadata_date_stamp', $today)) ?>" required>
          </label>
        </div>
      </article>

      <article class="metadata-card meta-section" id="meta-data-contact">
        <div class="meta-section__header">
          <span class="meta-section__eyebrow">Data Contact Information</span>
          <h2>Data Contact Information</h2>
          <p class="meta-section__description"><?= esc($sectionDescriptions['data_contact']) ?></p>
        </div>

        <div class="meta-grid">
          <label class="meta-field">
            <span>Individual Name</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['individual_name']) ?></p>
            <input name="individual_name" type="text" value="<?= esc(old('individual_name')) ?>" placeholder="John Doe, Jane Doe" required>
          </label>

          <label class="meta-field">
            <span>Organisation Name</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['organisation_name']) ?></p>
            <input name="organisation_name" type="text" value="<?= esc(old('organisation_name')) ?>" placeholder="Badan Informasi Geospasial" required>
          </label>

          <label class="meta-field">
            <span>Position Name</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['position_name']) ?></p>
            <input name="position_name" type="text" value="<?= esc(old('position_name')) ?>" placeholder="Physical Geodesy Working Group" required>
          </label>

          <label class="meta-field meta-field--full">
            <span>Role</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['contact_role']) ?></p>
            <select name="contact_role" required>
              <option value="">Pilih role</option>
              <?php foreach ($contactRoles as $item): ?>
                <option value="<?= esc($item) ?>" <?= old('contact_role', 'Owner') === $item ? 'selected' : '' ?>>
                  <?= esc($item === 'Owner' ? 'Owner (default)' : $item) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <details class="meta-details">
              <summary>Lihat deskripsi tiap role</summary>
              <div class="meta-role-guide">
                <?php foreach ($roleGuidance as $guide): ?>
                  <article class="meta-role-guide__item">
                    <strong><?= esc($guide['label']) ?></strong>
                    <p><?= esc($guide['description']) ?></p>
                    <span>Contoh: <?= esc($guide['example']) ?></span>
                  </article>
                <?php endforeach; ?>
              </div>
            </details>
          </label>
        </div>
      </article>

      <article class="metadata-card meta-section" id="meta-contact">
        <div class="meta-section__header">
          <span class="meta-section__eyebrow">Contact Information</span>
          <h2>Contact Information</h2>
          <p class="meta-section__description"><?= esc($sectionDescriptions['contact']) ?></p>
        </div>

        <div class="meta-grid">
          <label class="meta-field">
            <span>Voice</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['voice']) ?></p>
            <input name="voice" type="text" value="<?= esc(old('voice')) ?>" placeholder="+62896xxxxxxx" required>
          </label>

          <label class="meta-field">
            <span>Faximilie</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['facsimilie']) ?></p>
            <input name="facsimilie" type="text" value="<?= esc(old('facsimilie')) ?>" placeholder="(021) 5525789" required>
          </label>
        </div>
      </article>

      <article class="metadata-card meta-section" id="meta-address">
        <div class="meta-section__header">
          <span class="meta-section__eyebrow">Address</span>
          <h2>Address</h2>
          <p class="meta-section__description"><?= esc($sectionDescriptions['address']) ?></p>
        </div>

        <div class="meta-grid">
          <label class="meta-field">
            <span>Delivery Point</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['delivery_point']) ?></p>
            <input name="delivery_point" type="text" value="<?= esc(old('delivery_point')) ?>" placeholder="Jalan Ganesha 10" required>
          </label>

          <label class="meta-field">
            <span>City</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['city']) ?></p>
            <input name="city" type="text" value="<?= esc(old('city')) ?>" placeholder="Bandung" required>
          </label>

          <label class="meta-field">
            <span>Administrative Area</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['administrative_area']) ?></p>
            <input name="administrative_area" type="text" value="<?= esc(old('administrative_area')) ?>" placeholder="Jawa Barat" required>
          </label>

          <label class="meta-field">
            <span>Postal Code</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['postal_code']) ?></p>
            <input name="postal_code" type="text" value="<?= esc(old('postal_code')) ?>" placeholder="40132" required>
          </label>

          <label class="meta-field">
            <span>Country</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['country']) ?></p>
            <input name="country" type="text" value="<?= esc(old('country', 'Indonesia')) ?>" placeholder="Indonesia" required>
          </label>

          <label class="meta-field">
            <span>Electronic Mail Address</span>
            <p class="meta-field__help"><?= esc($fieldDescriptions['electronic_mail_address']) ?></p>
            <input
              name="electronic_mail_address"
              type="text"
              value="<?= esc(old('electronic_mail_address')) ?>"
              placeholder="info@gd.itb.ac.id, 15122063@mahasiswa.itb.ac.id"
              required
            >
          </label>
        </div>

        <div class="meta-section__header" style="margin-top: 28px;">
          <span class="meta-section__eyebrow">Dataset Upload</span>
          <h2>Source Files</h2>
          <p class="meta-section__description">
            Unggah minimal satu berkas sumber. ZIP digunakan untuk paket SHP lengkap beserta file pendampingnya,
            file tabular menerima Excel atau CSV, dan raster menerima TIFF.
          </p>
        </div>

        <div class="meta-grid">
          <label class="meta-field">
            <span>ZIP SHP + pelengkap</span>
            <p class="meta-field__help">
              Simpan <code>.shp</code>, <code>.shx</code>, <code>.dbf</code>, <code>.prj</code>, dan pelengkap lain
              ke dalam satu file <code>.zip</code> sebelum diunggah.
            </p>
            <input name="shapefile_zip" type="file" accept=".zip">
          </label>

          <label class="meta-field">
            <span>Excel / CSV</span>
            <p class="meta-field__help">
              Menerima <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> untuk data tabular level 1.
            </p>
            <input name="tabular_file" type="file" accept=".xlsx,.xls,.csv">
          </label>

          <label class="meta-field">
            <span>TIFF</span>
            <p class="meta-field__help">
              Gunakan <code>.tif</code> atau <code>.tiff</code> untuk raster level 2 yang ingin disiapkan ke tahap staging.
            </p>
            <input name="raster_file" type="file" accept=".tif,.tiff">
          </label>
        </div>

        <div class="meta-footer-actions">
          <button class="metadata-submit" type="submit">
            <i class="bi bi-send"></i>
            Submit Metadata & Files
          </button>
        </div>
      </article>
    </form>
  </section>
</main>

<script src="<?= base_url('site/js/metadata.js') ?>"></script>
</body>
</html>
