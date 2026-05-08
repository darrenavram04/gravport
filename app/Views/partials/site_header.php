<?php
$homeUrl = site_url('/');
$isLoggedIn = auth_is_logged_in();
$role = auth_current_role();
$activePage = (string) ($activePage ?? '');
$headerClass = trim((string) ($headerClass ?? ''));
$headerId = (string) ($headerId ?? 'siteHeader');
$shareButtonId = (string) ($shareButtonId ?? '');
$showShareButton = array_key_exists('showShareButton', get_defined_vars()) ? (bool) $showShareButton : true;

$navItems = [
    ['page' => 'home', 'label' => 'Home', 'href' => $homeUrl, 'data_section' => 'home'],
    ['page' => 'about', 'label' => 'About', 'href' => $homeUrl . '#About', 'data_section' => 'About'],
    ['page' => 'tools', 'label' => 'Tools', 'href' => $homeUrl . '#tools', 'data_section' => 'tools'],
    ['page' => 'team', 'label' => 'Team', 'href' => $homeUrl . '#team', 'data_section' => 'team'],
    ['page' => 'catalog', 'label' => 'Dataset', 'href' => site_url('catalog')],
    ['page' => 'webmap', 'label' => 'WebMap', 'href' => site_url('webmap')],
    ['page' => 'metadata', 'label' => 'Metadata', 'href' => site_url('metadata'), 'auth_only' => true],
    ['page' => 'contacts', 'label' => 'Contacts', 'href' => $homeUrl . '#contacts', 'data_section' => 'contacts'],
];
?>
<header class="site-header <?= esc($headerClass) ?>" id="<?= esc($headerId) ?>">
  <div class="nav-container">
    <a href="<?= $homeUrl ?>" class="site-logo">
      <img src="<?= base_url('images/itb.png') ?>" alt="Logo ITB">
      <span class="site-title">GravPort</span>
    </a>

    <nav class="site-nav" aria-label="Primary">
      <?php foreach ($navItems as $item): ?>
        <?php if (!empty($item['admin_only']) && !($isLoggedIn && $role === 'admin')): ?>
          <?php continue; ?>
        <?php endif; ?>
        <?php if (!empty($item['auth_only']) && !$isLoggedIn): ?>
          <?php continue; ?>
        <?php endif; ?>
        <?php
        $isActive = $activePage === (string) ($item['page'] ?? '');
        $classes = 'site-nav__link' . ($isActive ? ' active is-active' : '');
        $dataSection = $item['data_section'] ?? null;
        ?>
        <a
          class="<?= esc($classes) ?>"
          href="<?= $item['href'] ?>"
          <?= $dataSection ? 'data-nav-section="' . esc($dataSection, 'attr') . '"' : '' ?>
          <?= $isActive ? 'aria-current="page"' : '' ?>
        >
          <?= esc($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-auth">
      <?php if (!$isLoggedIn): ?>
        <a class="nav-login" href="<?= site_url('login') ?>">Login</a>
      <?php else: ?>
        <span class="nav-role"><?= esc(strtoupper($role)) ?></span>
        <a class="nav-logout" href="<?= site_url('logout') ?>">Logout</a>
      <?php endif; ?>
    </div>

    <?php if ($showShareButton): ?>
      <button
        class="nav-share"
        type="button"
        aria-label="Share page"
        <?= $shareButtonId !== '' ? 'id="' . esc($shareButtonId, 'attr') . '"' : '' ?>
      >
        <i class="bi bi-share"></i>
      </button>
    <?php endif; ?>
  </div>
</header>
