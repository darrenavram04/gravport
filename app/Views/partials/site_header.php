<?php
$homeUrl = site_url('/');
$isLoggedIn = auth_is_logged_in();
$role = auth_current_role();
$activePage = (string) ($activePage ?? '');
$headerClass = trim((string) ($headerClass ?? ''));
$headerId = (string) ($headerId ?? 'siteHeader');
$shareButtonId = (string) ($shareButtonId ?? '');
$showShareButton = isset($showShareButton) ? (bool) $showShareButton : true;

$navItems = [
    ['page' => 'home',     'label' => 'Home',      'href' => $homeUrl,                     'data_section' => 'home',     'i18n' => 'nav.home'],
    ['page' => 'about',    'label' => 'About',     'href' => $homeUrl . '#overview',        'data_section' => 'overview', 'home_only' => true, 'i18n' => 'nav.about'],
    ['page' => 'statistik', 'label' => 'Statistik', 'href' => $homeUrl . '#About',          'data_section' => 'About',    'home_only' => true, 'i18n' => 'nav.statistik'],
    ['page' => 'team',     'label' => 'Team',      'href' => $homeUrl . '#team',            'data_section' => 'team',     'home_only' => true, 'i18n' => 'nav.team'],
    ['page' => 'catalog',  'label' => 'Dataset',   'href' => site_url('catalog'),                                                              'i18n' => 'nav.dataset'],
    ['page' => 'webmap',   'label' => 'WebMap',    'href' => site_url('webmap'),                                                               'i18n' => 'nav.webmap'],
    ['page' => 'ogc',      'label' => 'OGC',       'href' => site_url('ogc'),                                                                  'i18n' => 'nav.ogc'],
    ['page' => 'metadata', 'label' => 'Metadata',  'href' => site_url('metadata'),          'min_role' => 'admin',                             'i18n' => 'nav.metadata'],
    ['page' => 'admin',    'label' => 'Admin Hub', 'href' => site_url('dataset/manage'),    'min_role' => 'admin',                             'i18n' => 'nav.admin'],
    ['page' => 'contacts', 'label' => 'Contacts',  'href' => $homeUrl . '#contacts',        'data_section' => 'contacts', 'home_only' => true, 'i18n' => 'nav.contacts'],
];
?>
<?php
// Role level helper (local scope)
$roleLevelLocal = function(string $r): int {
    return match($r) { 'guest' => 0, 'user' => 1, 'admin' => 2, 'superadmin' => 3, default => -1 };
};
$userLevel = $roleLevelLocal($role);
?>
<style>
  .nav-hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 42px; height: 42px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 12px;
    cursor: pointer;
    z-index: 1001;
    flex-shrink: 0;
  }
  .nav-hamburger span {
    display: block; width: 20px; height: 2px;
    background: rgba(255,255,255,.85);
    border-radius: 2px;
    transition: transform .25s, opacity .25s;
  }
  .nav-hamburger.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
  .nav-hamburger.is-open span:nth-child(2) { opacity: 0; }
  .nav-hamburger.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

  /* Mobile drawer */
  .nav-drawer {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
  }
  .nav-drawer__backdrop {
    position: absolute; inset: 0;
    background: rgba(4,10,20,.7);
    backdrop-filter: blur(4px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .3s;
  }
  .nav-drawer.is-open .nav-drawer__backdrop {
    opacity: 1;
    pointer-events: auto;
  }
  .nav-drawer__panel {
    position: absolute;
    top: 0; right: 0;
    width: min(300px, 88vw);
    height: 100%;
    background: #0b1b34;
    border-left: 1px solid rgba(255,255,255,.1);
    padding: 72px 24px 32px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    transform: translateX(100%);
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    overflow-y: auto;
  }
  .nav-drawer.is-open .nav-drawer__panel { transform: translateX(0); }
  .nav-drawer__link {
    display: block;
    padding: 13px 16px;
    border-radius: 14px;
    color: rgba(255,255,255,.78);
    text-decoration: none;
    font-weight: 700;
    font-size: 15px;
    transition: background .18s, color .18s;
  }
  .nav-drawer__link:hover,
  .nav-drawer__link.active { background: rgba(255,255,255,.07); color: #fff; }
  .nav-drawer__divider { height: 1px; background: rgba(255,255,255,.08); margin: 8px 0; }
  .nav-drawer__auth { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.08); }
  .nav-drawer__role { font-size: 11px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: 8px; }

  /* ── Logged-in user chip ─────────────────────────────────────── */
  .nav-user-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 999px;
    padding: 4px 14px 4px 4px;
    flex-shrink: 0;
    white-space: nowrap;
  }
  .nav-role-badge {
    display: inline-flex;
    align-items: center;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .1em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 999px;
    line-height: 1;
    white-space: nowrap;
  }
  .nav-role-guest      { background: rgba(107,122,143,.22); color: #9eafc2; }
  .nav-role-user       { background: rgba(74,154,245,.2);   color: #80bbf5; }
  .nav-role-admin      { background: rgba(167,96,37,.28);   color: #ffbf74; }
  .nav-role-superadmin { background: rgba(255,215,145,.18); color: #ffd891; border: 1px solid rgba(255,215,145,.3); }
  .nav-chip-link {
    font-size: 12px;
    font-weight: 700;
    color: rgba(255,255,255,.78);
    text-decoration: none;
    line-height: 1;
    padding: 0 2px;
  }
  .nav-chip-link:hover { color: #fff; }
  .nav-chip-sep { color: rgba(255,255,255,.2); font-size: 10px; line-height: 1; }

  /* Prevent nav wrapping to a second row at any viewport above mobile breakpoint */
  .site-nav { flex-wrap: nowrap !important; }

  /* Prevent individual nav link text from wrapping (e.g. "Admin Hub") */
  .site-nav__link { white-space: nowrap; }

  /* Tighten nav gap on home (many items) so lang-toggle stays in frame */
  .gravport-landing .site-nav { gap: 12px !important; }
  .gravport-landing .site-nav__link { font-size: 13px !important; }

  /* Ensure lang-toggle never gets pushed out */
  .nav-container { flex-wrap: nowrap; min-width: 0; }
  .lang-toggle { flex-shrink: 0; }
  .nav-auth { flex-shrink: 0; min-width: 0; }

  @media (max-width: 900px) {
    .nav-hamburger { display: flex; }
    .site-nav { display: none !important; }
    .nav-auth  { display: none !important; }
    .nav-share { display: none !important; }
    .nav-drawer { display: block; }
  }
  /* ── Fix: tighten nav gap to make room for lang toggle ── */
  .gravport-landing .site-nav { gap: 18px !important; }

  /* ── Language toggle ── */
  .lang-toggle {
    display: inline-flex;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.18);
    overflow: hidden;
    background: rgba(255,255,255,.06);
    flex-shrink: 0;
  }
  .lang-toggle__btn {
    padding: 5px 11px;
    font-family: 'Poppins', sans-serif;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .08em;
    color: rgba(255,255,255,.46);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: color .15s, background .15s;
    line-height: 1;
  }
  .lang-toggle__btn.is-active { background: rgba(255,255,255,.16); color: #fff; }
  .lang-toggle__btn:hover:not(.is-active) { color: rgba(255,255,255,.8); }
  .lang-toggle--drawer {
    display: flex;
    margin: 4px 0 10px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.03);
  }
  .lang-toggle--drawer .lang-toggle__btn {
    flex: 1; padding: 10px 0; font-size: 11px;
    border-radius: 0; text-align: center;
  }
</style>

<header class="site-header <?= esc($headerClass) ?>" id="<?= esc($headerId) ?>">
  <div class="nav-container">
    <a href="<?= $homeUrl ?>" class="site-logo">
      <img src="<?= base_url('images/gravport_logo_color.png') ?>" alt="GravPort">
      <span class="site-title">GravPort</span>
    </a>

    <nav class="site-nav" aria-label="Primary">
      <?php foreach ($navItems as $item): ?>
        <?php
        $minRole = $item['min_role'] ?? null;
        if ($minRole !== null && $userLevel < $roleLevelLocal($minRole)) continue;
        if (!empty($item['home_only']) && $activePage !== 'home') continue;
        $isActive = $activePage === (string) ($item['page'] ?? '');
        $classes = 'site-nav__link' . ($isActive ? ' active is-active' : '');
        $dataSection = $item['data_section'] ?? null;
        ?>
        <a class="<?= esc($classes) ?>"
           href="<?= $item['href'] ?>"
           <?= $dataSection ? 'data-nav-section="' . esc($dataSection, 'attr') . '"' : '' ?>
           <?= !empty($item['i18n']) ? 'data-i18n="' . esc($item['i18n'], 'attr') . '"' : '' ?>
           <?= $isActive ? 'aria-current="page"' : '' ?>>
          <?= esc($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-auth">
      <?php if (!$isLoggedIn): ?>
        <a class="nav-login nav-login--primary" href="<?= site_url('signup') ?>" data-i18n="auth.signup">Sign Up</a>
        <a class="nav-login nav-login--secondary" href="<?= site_url('login') ?>" data-i18n="auth.login">Login</a>
      <?php else: ?>
        <?php $roleLabel = match($role) {
          'superadmin' => 'Superadmin',
          'admin'      => 'Admin',
          'user'       => 'User',
          default      => 'Tamu',
        }; ?>
        <div class="nav-user-chip">
          <span class="nav-role-badge nav-role-<?= esc($role) ?>"><?= esc($roleLabel) ?></span>
          <?php if (in_array($role, ['user', 'admin', 'superadmin'], true)): ?>
            <a class="nav-chip-link" href="<?= site_url('account') ?>" data-i18n="auth.account">Akun</a>
            <span class="nav-chip-sep">|</span>
          <?php endif; ?>
          <a class="nav-chip-link" href="<?= site_url('logout') ?>" data-i18n="auth.logout">Keluar</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($showShareButton): ?>
      <button class="nav-share" type="button" aria-label="Share page"
        <?= $shareButtonId !== '' ? 'id="' . esc($shareButtonId, 'attr') . '"' : '' ?>>
        <i class="bi bi-share"></i>
      </button>
    <?php endif; ?>

    <div class="lang-toggle" id="langToggleNav" aria-label="Language switcher">
      <button class="lang-toggle__btn" data-lang="id" onclick="gpSetLang('id')">ID</button>
      <button class="lang-toggle__btn" data-lang="en" onclick="gpSetLang('en')">EN</button>
    </div>

    <button class="nav-hamburger" id="navHamburger" aria-label="Buka menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Mobile Drawer -->
<div class="nav-drawer" id="navDrawer" aria-hidden="true">
  <div class="nav-drawer__backdrop" id="navBackdrop"></div>
  <div class="nav-drawer__panel">
    <?php foreach ($navItems as $item): ?>
      <?php
      $minRole = $item['min_role'] ?? null;
      if ($minRole !== null && $userLevel < $roleLevelLocal($minRole)) continue;
      if (!empty($item['home_only']) && $activePage !== 'home') continue;
      $isActive = $activePage === (string) ($item['page'] ?? '');
      ?>
      <a class="nav-drawer__link<?= $isActive ? ' active' : '' ?>"
         href="<?= $item['href'] ?>"
         <?= !empty($item['i18n']) ? 'data-i18n="' . esc($item['i18n'], 'attr') . '"' : '' ?>>
        <?= esc($item['label']) ?>
      </a>
    <?php endforeach; ?>

    <div class="nav-drawer__divider"></div>
    <div class="lang-toggle lang-toggle--drawer" id="langToggleDrawer">
      <button class="lang-toggle__btn" data-lang="id" onclick="gpSetLang('id')">ID</button>
      <button class="lang-toggle__btn" data-lang="en" onclick="gpSetLang('en')">EN</button>
    </div>
    <div class="nav-drawer__auth">
      <?php if (!$isLoggedIn): ?>
        <a class="nav-drawer__link" href="<?= site_url('signup') ?>" data-i18n="auth.signup">Sign Up</a>
        <a class="nav-drawer__link" href="<?= site_url('login') ?>" data-i18n="auth.login">Login</a>
      <?php else: ?>
        <div class="nav-drawer__role"><?= esc(strtoupper($role)) ?></div>
        <?php if (in_array($role, ['user', 'admin', 'superadmin'], true)): ?>
          <a class="nav-drawer__link" href="<?= site_url('account') ?>" data-i18n="auth.account">Akun</a>
        <?php endif; ?>
        <a class="nav-drawer__link" href="<?= site_url('logout') ?>" data-i18n="auth.logout">Keluar</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  const btn     = document.getElementById('navHamburger');
  const drawer  = document.getElementById('navDrawer');
  const backdrop = document.getElementById('navBackdrop');
  if (!btn || !drawer) return;

  function open() {
    btn.classList.add('is-open');
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    btn.classList.remove('is-open');
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    btn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  btn.addEventListener('click', () => btn.classList.contains('is-open') ? close() : open());
  backdrop.addEventListener('click', close);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
})();
</script>

<script>
/* ── GravPort i18n ── */
(function(){
  var T = {
    id: {
      'nav.home':      'Home',
      'nav.about':     'About',
      'nav.statistik': 'Statistik',
      'nav.team':      'Tim',
      'nav.dataset':   'Dataset',
      'nav.webmap':    'WebMap',
      'nav.ogc':       'OGC',
      'nav.metadata':  'Metadata',
      'nav.admin':     'Admin Hub',
      'nav.contacts':  'Kontak',
      'auth.signup':   'Daftar',
      'auth.login':    'Masuk',
      'auth.account':  'Akun',
      'auth.logout':   'Keluar',
      'ds.title':      'Katalog Data',
      'ds.subtitle':   'FAA dan CBA level 1-2 siap dipreview dan diunduh.',
      'ds.opencatalog':'Buka Katalog',
      'st.tp.label':   'Total Titik',
      'st.tp.sub':     'titik gravitasi terukur',
      'st.faa.label':  'FAA Rata-rata',
      'st.faa.sub':    'mGal · Free Air Anomaly',
      'st.cba.label':  'CBA Rata-rata',
      'st.cba.sub':    'mGal · Complete Bouguer Anomaly',
      'st.std.label':  'Std Deviasi FAA',
      'st.std.sub':    'mGal · variabilitas anomali',
      'st.chart.title':'Rentang Nilai Anomali Gravitasi (mGal)',
      'st.chart.sub':  'Min · Rata-rata · Maks per tipe anomali — FAA vs CBA',
      'st.faa.panel':  'Free Air Anomaly',
      'st.cba.panel':  'Complete Bouguer',
      'st.row.mean':   'Rata-rata',
      'st.row.std':    'Std Dev',
      'st.row.min':    'Minimum',
      'st.row.max':    'Maksimum',
      'st.row.points': 'titik',
      'st.chart.min':  'Min',
      'st.chart.avg':  'Rata-rata',
      'st.chart.max':  'Maks',
      'ov.heading':    'Overview',
      'ov.tab.faa':    'Free Air Anomaly',
      'ov.tab.cba':    'Complete Bouguer Anomaly',
      'ov.tab.int':    'Integrasi',
      'ov.faa.1':      'Data Free Air Anomaly (FAA) menggambarkan perbedaan antara nilai gravitasi observasi dan nilai gravitasi normal setelah dikoreksi terhadap ketinggian permukaan pengukuran.',
      'ov.faa.2':      'FAA digunakan untuk pemodelan struktur kerak bumi dangkal, identifikasi cekungan sedimen, dan eksplorasi sumber daya alam di wilayah Jawa-Bali.',
      'ov.faa.3':      'GravPort menyediakan data FAA level 1 (point observasi terfilter) dan level 2 (gridded & diinterpolasi) dari berbagai survei darat dan udara.',
      'ov.cba.1':      'Complete Bouguer Anomaly (CBA) adalah anomali gayaberat yang telah dikoreksi secara lengkap: udara bebas, Bouguer plat, dan terrain correction untuk massa topografi di sekitar titik ukur.',
      'ov.cba.2':      'CBA sangat sensitif terhadap variasi densitas batuan bawah permukaan, menjadikannya alat utama untuk pemetaan geologi regional dan identifikasi intrusi magmatik.',
      'ov.cba.3':      'Data CBA di GravPort tersedia dalam format grid reguler dengan resolusi spasial tinggi, hasil integrasi survei multisumber yang telah divalidasi secara kualitas.',
      'ov.int.1':      'GravPort mengintegrasikan data gayaberat dari berbagai lembaga survei nasional dan internasional, termasuk BIG, BMKG, dan mitra riset universitas di Indonesia.',
      'ov.int.2':      'Proses integrasi meliputi harmonisasi datum referensi, penanganan sistematik antar survei, dan quality control berbasis statistik untuk setiap titik data.',
      'ov.int.3':      'Hasil integrasi menghasilkan coverage gayaberat yang lebih komprehensif dan konsisten untuk seluruh wilayah Jawa-Bali dibandingkan dataset tunggal manapun.',
      'tm.heading':    'Tim Kami',
      'tm.lead':       'Tim pengolahan data, visualisasi, dan pengembangan GravPort.',
      /* catalog */
      'cat.filters':   'Filter',
      'cat.show':      'Tampilkan',
      'cat.search.lbl':'Pencarian',
      'cat.search.ph': 'Cari dataset...',
      'cat.per_page':      'Item per halaman',
      'cat.cards':         'Kartu',
      'cat.table':         'Tabel',
      'cat.reset':         'Reset',
      'cat.view':          'Lihat',
      'cat.dl':            'Unduh',
      'cat.metadata.btn':  'Metadata',
      'cat.no_actions':    'Tidak ada aksi',
      /* webmap sidebar */
      'wm.copy':       'Pilih data, tentukan area, lalu unduh hasilnya.',
      'wm.sec.data':   'Data',
      'wm.sec.level':  'Level',
      /* login */
      'lg.h1':         'Masuk ke workspace GravPort.',
      'lg.intro':      'Masuk untuk membuka katalog, WebMap, dan fitur sesuai role akun.',
      'lg.h2':         'Masuk ke akun Anda',
      'lg.sub':        'Gunakan akun yang sesuai.',
      'lg.lbl.pw':     'Password',
      'lg.pw.ph':      'Masukkan password',
      'lg.forgot':     'Lupa password?',
      'lg.submit':     'Login ke GravPort',
      'lg.divider':    'atau',
      'lg.guest':      'Lanjutkan sebagai Tamu',
      /* signup */
      'sg.h2.0':       'Buat Akun GravPort',
      'sg.p.0':        'Pilih jenis akun yang ingin Anda buat.',
      'sg.path.user.t':'Pengguna',
      'sg.path.user.s':'Akses & unduh data gravitasi',
      'sg.path.adm.t': 'Admin Platform',
      'sg.path.adm.s': 'Kelola & kontribusi data',
      'sg.switch':     'Sudah punya akun?',
      'sg.switch.lnk': 'Login di sini',
      'sg.back':       'Kembali',
      'sg.h2.admin':   'Permintaan Akses Admin',
      'sg.p.admin':    'Isi data Anda; tim kami akan menghubungi untuk verifikasi dan pengaturan akses.',
      'sg.lbl.name':   'Nama Lengkap',
      'sg.lbl.email':  'Email',
      'sg.lbl.msg':    'Pesan (opsional)',
      'sg.submit.adm': 'Kirim Permintaan',
      'sg.h2.pkg':     'Pilih Paket',
      'sg.p.pkg':      'Bayar bulanan atau hemat 2 bulan dengan paket tahunan.',
      'sg.h2.user':    'Buat Akun Pengguna',
      'sg.lbl.fn':     'Nama Depan',
      'sg.lbl.ln':     'Nama Belakang',
      'sg.lbl.pw':     'Password',
      'sg.lbl.pw2':    'Konfirmasi Password',
    },
    en: {
      'nav.home':      'Home',
      'nav.about':     'About',
      'nav.statistik': 'Statistics',
      'nav.team':      'Team',
      'nav.dataset':   'Dataset',
      'nav.webmap':    'WebMap',
      'nav.ogc':       'OGC',
      'nav.metadata':  'Metadata',
      'nav.admin':     'Admin Hub',
      'nav.contacts':  'Contacts',
      'auth.signup':   'Sign Up',
      'auth.login':    'Login',
      'auth.account':  'Account',
      'auth.logout':   'Log Out',
      'ds.title':      'Data Catalog',
      'ds.subtitle':   'FAA and CBA level 1–2 data, ready to preview and download.',
      'ds.opencatalog':'Open Catalog',
      'st.tp.label':   'Total Points',
      'st.tp.sub':     'measured gravity points',
      'st.faa.label':  'FAA Mean',
      'st.faa.sub':    'mGal · Free Air Anomaly',
      'st.cba.label':  'CBA Mean',
      'st.cba.sub':    'mGal · Complete Bouguer Anomaly',
      'st.std.label':  'FAA Std Dev',
      'st.std.sub':    'mGal · anomaly variability',
      'st.chart.title':'Gravity Anomaly Value Range (mGal)',
      'st.chart.sub':  'Min · Mean · Max per anomaly type — FAA vs CBA',
      'st.faa.panel':  'Free Air Anomaly',
      'st.cba.panel':  'Complete Bouguer',
      'st.row.mean':   'Mean',
      'st.row.std':    'Std Dev',
      'st.row.min':    'Minimum',
      'st.row.max':    'Maximum',
      'st.row.points': 'points',
      'st.chart.min':  'Min',
      'st.chart.avg':  'Mean',
      'st.chart.max':  'Max',
      'ov.heading':    'Overview',
      'ov.tab.faa':    'Free Air Anomaly',
      'ov.tab.cba':    'Complete Bouguer Anomaly',
      'ov.tab.int':    'Integration',
      'ov.faa.1':      'Free Air Anomaly (FAA) data represents the difference between observed gravity and theoretical normal gravity, corrected for elevation of the measurement surface.',
      'ov.faa.2':      'FAA is used for modeling shallow crustal structure, identifying sedimentary basins, and natural resource exploration across the Java-Bali region.',
      'ov.faa.3':      'GravPort provides FAA data at level 1 (filtered observation points) and level 2 (gridded & interpolated) from various land and airborne surveys.',
      'ov.cba.1':      'Complete Bouguer Anomaly (CBA) is gravity anomaly fully corrected for free-air, Bouguer plate, and terrain effects of topographic mass surrounding the measurement point.',
      'ov.cba.2':      'CBA is highly sensitive to subsurface rock density variations, making it the primary tool for regional geological mapping and identification of magmatic intrusions.',
      'ov.cba.3':      'CBA data in GravPort is available as a regular grid at high spatial resolution, the result of multi-source survey integration validated for quality.',
      'ov.int.1':      'GravPort integrates gravity data from various national and international survey agencies, including BIG, BMKG, and university research partners across Indonesia.',
      'ov.int.2':      'The integration process includes reference datum harmonization, inter-survey systematic correction, and statistics-based quality control for every data point.',
      'ov.int.3':      'The integrated result yields more comprehensive and consistent gravity coverage for the entire Java-Bali region than any single dataset alone.',
      'tm.heading':    'Meet Our Team',
      'tm.lead':       'The data processing, visualization, and development team behind GravPort.',
      /* catalog */
      'cat.filters':   'Filters',
      'cat.show':      'Show',
      'cat.search.lbl':'Search',
      'cat.search.ph': 'Search datasets...',
      'cat.per_page':      'Items per page',
      'cat.cards':         'Cards',
      'cat.table':         'Table',
      'cat.reset':         'Reset',
      'cat.view':          'View',
      'cat.dl':            'Download',
      'cat.metadata.btn':  'Metadata',
      'cat.no_actions':    'No actions available',
      /* webmap sidebar */
      'wm.copy':       'Select data, define area, then download the result.',
      'wm.sec.data':   'Data',
      'wm.sec.level':  'Level',
      /* login */
      'lg.h1':         'Sign In to GravPort.',
      'lg.intro':      'Sign in to access the catalog, WebMap, and account features.',
      'lg.h2':         'Sign in to your account',
      'lg.sub':        'Use your registered account.',
      'lg.lbl.pw':     'Password',
      'lg.pw.ph':      'Enter password',
      'lg.forgot':     'Forgot password?',
      'lg.submit':     'Login to GravPort',
      'lg.divider':    'or',
      'lg.guest':      'Continue as Guest',
      /* signup */
      'sg.h2.0':       'Create GravPort Account',
      'sg.p.0':        'Choose the account type you want to create.',
      'sg.path.user.t':'User',
      'sg.path.user.s':'Access & download gravity data',
      'sg.path.adm.t': 'Platform Admin',
      'sg.path.adm.s': 'Manage & contribute data',
      'sg.switch':     'Already have an account?',
      'sg.switch.lnk': 'Login here',
      'sg.back':       'Back',
      'sg.h2.admin':   'Admin Access Request',
      'sg.p.admin':    'Fill in your details; our team will contact you for verification and access setup.',
      'sg.lbl.name':   'Full Name',
      'sg.lbl.email':  'Email',
      'sg.lbl.msg':    'Message (optional)',
      'sg.submit.adm': 'Send Request',
      'sg.h2.pkg':     'Choose a Plan',
      'sg.p.pkg':      'Pay monthly or save 2 months with an annual plan.',
      'sg.h2.user':    'Create User Account',
      'sg.lbl.fn':     'First Name',
      'sg.lbl.ln':     'Last Name',
      'sg.lbl.pw':     'Password',
      'sg.lbl.pw2':    'Confirm Password',
    }
  };

  window.GP_TRANSLATIONS = T;

  window.GP_T = function(key, lang) {
    lang = lang || localStorage.getItem('gp_lang') || 'id';
    return (T[lang] && T[lang][key]) || (T['id'] && T['id'][key]) || key;
  };

  window.gpApplyLang = function(lang) {
    document.querySelectorAll('[data-i18n]').forEach(function(el){
      var key = el.getAttribute('data-i18n');
      var val = (T[lang] && T[lang][key]) || (T['id'] && T['id'][key]) || key;
      el.textContent = val;
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(function(el){
      var key = el.getAttribute('data-i18n-placeholder');
      var val = (T[lang] && T[lang][key]) || (T['id'] && T['id'][key]) || key;
      el.placeholder = val;
    });
    document.querySelectorAll('.lang-toggle__btn').forEach(function(b){
      b.classList.toggle('is-active', b.getAttribute('data-lang') === lang);
    });
    if (typeof window.GP_REBUILD_STATS === 'function') window.GP_REBUILD_STATS(lang);
    if (typeof window.GP_REBUILD_CHART === 'function') window.GP_REBUILD_CHART(lang);
  };

  window.gpSetLang = function(lang) {
    if (lang !== 'id' && lang !== 'en') return;
    localStorage.setItem('gp_lang', lang);
    window.gpApplyLang(lang);
  };

  document.addEventListener('DOMContentLoaded', function(){
    var lang = localStorage.getItem('gp_lang') || 'id';
    window.gpApplyLang(lang);
  });
})();
</script>

<script>
/* ── Same-page anchor smooth-scroll ── */
(function () {
  function scrollToSection(id) {
    var target = document.getElementById(id);
    if (!target) return false;
    var header = document.querySelector('.site-header');
    var offset = header ? header.getBoundingClientRect().height + 16 : 80;
    var top = target.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top: top, behavior: 'smooth' });
    return true;
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[data-nav-section]');
    if (!link) return;
    if (link.href.indexOf('#') === -1) return; // bare URL links (e.g. Home) always navigate normally
    var section = link.getAttribute('data-nav-section');
    if (scrollToSection(section)) {
      e.preventDefault();
      // Close mobile drawer if open
      var btn = document.querySelector('.nav-hamburger');
      if (btn && btn.classList.contains('is-open')) btn.click();
    }
  });
})();
</script>

<script>
/* ── Share button (works on all pages) ── */
(function () {
  function showShareToast(msg) {
    var toast = document.getElementById('siteToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'siteToast';
      toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1a1a2e;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;z-index:9999;transition:opacity .3s;pointer-events:none;';
      document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () { toast.style.opacity = '0'; }, 2400);
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.nav-share');
    if (!btn) return;
    var shareData = { title: document.title, text: 'Explore GravPort Jawa-Bali gravity anomaly portal', url: window.location.href };
    if (navigator.share) {
      navigator.share(shareData).then(function () { showShareToast('Link halaman siap dibagikan.'); }).catch(function () { showShareToast('Aksi berbagi dibatalkan.'); });
      return;
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(window.location.href).then(function () { showShareToast('Link halaman berhasil disalin.'); }).catch(function () { showShareToast('Gagal menyalin link.'); });
      return;
    }
    var helper = document.createElement('input');
    helper.value = window.location.href;
    document.body.appendChild(helper);
    helper.select();
    document.execCommand('copy');
    document.body.removeChild(helper);
    showShareToast('Link halaman berhasil disalin.');
  });
})();
</script>

