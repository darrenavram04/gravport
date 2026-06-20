<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('landing',            'Home::landingPage');
$routes->get('landing/free',       'Home::landingFree');
$routes->get('landing/enterprise', 'Home::landingEnterprise');

// -------------------------------------------------------------------------
// Auth — public
// -------------------------------------------------------------------------
$routes->get('login',            'AuthController::loginForm');
$routes->post('login',           'AuthController::loginPost');
$routes->get('login/guest',      'AuthController::loginAsGuest');
$routes->get('signup',                   'RegistrationController::showForm');
$routes->post('register/individual',     'RegistrationController::submitIndividual');
$routes->post('register/renew',          'RegistrationController::submitRenew');
$routes->post('register/team',           'RegistrationController::submitTeam');
$routes->post('register/admin-inquiry',  'RegistrationController::submitAdminInquiry');
$routes->get('pending-payment',          'RegistrationController::pendingPayment');

// Payment (Midtrans Snap)
$routes->get('payment/pay/(individual|team)/(:num)', 'PaymentController::pay/$1/$2');
$routes->post('payment/webhook',                     'PaymentController::webhook');  // CSRF-exempt (Security.php)
$routes->get('payment/finish',                       'PaymentController::finish');
$routes->get('logout',           'AuthController::logout');

// Forgot / reset password
$routes->get('forgot-password',  'AuthController::forgotPasswordForm');
$routes->post('forgot-password', 'AuthController::forgotPasswordPost');
$routes->get('reset-password',   'AuthController::resetPasswordForm');
$routes->post('reset-password',  'AuthController::resetPasswordPost');

// Two-factor authentication
$routes->get('verify-2fa',       'AuthController::verifyTwoFactorForm');
$routes->post('verify-2fa',      'AuthController::verifyTwoFactorPost');
$routes->get('resend-otp',       'AuthController::resendOtp');

// -------------------------------------------------------------------------
// Catalog — requires at least guest session
// -------------------------------------------------------------------------
$routes->get('catalog',                          'Catalog::index',            ['filter' => 'role:guest']);
$routes->get('catalog/view/(:num)',              'Catalog::view/$1',          ['filter' => 'role:guest']);
$routes->get('catalog/download/(:num)',          'Catalog::download/$1',      ['filter' => 'role:user']);
$routes->get('catalog/download-metadata/(:num)', 'Catalog::downloadMetadata/$1', ['filter' => 'role:user']);
$routes->get('catalog/geojson/(:num)',           'Catalog::geojson/$1',       ['filter' => 'role:guest']);

// -------------------------------------------------------------------------
// WebMap — requires at least guest session
// -------------------------------------------------------------------------
$routes->get('webmap',                                  'WebMap::index',                 ['filter' => 'role:guest']);
$routes->get('webmap/bootstrap',                        'WebMap::bootstrap',             ['filter' => 'role:guest']);
$routes->get('webmap/provinces',                        'WebMap::provinces',             ['filter' => 'role:guest']);
$routes->get('webmap/aoi',                              'WebMap::aoi',                   ['filter' => 'role:guest']);
$routes->get('webmap/faa',                              'WebMap::faa',                   ['filter' => 'role:guest']);
$routes->get('webmap/layer',                            'WebMap::layer',                 ['filter' => 'role:guest']);
$routes->post('webmap/layer',                           'WebMap::layer',                 ['filter' => 'role:guest']);
$routes->get('webmap/feature-meta/(:segment)/(:segment)', 'WebMap::featureMeta/$1/$2',  ['filter' => 'role:guest']);
$routes->post('webmap/download/vector',                 'WebMap::downloadVector',        ['filter' => 'role:user']);
$routes->post('webmap/download/metadata',               'WebMap::downloadMetadata',      ['filter' => 'role:user']);
$routes->post('webmap/clip/raster',                     'WebMap::clipRaster',            ['filter' => 'role:user']);
$routes->get('webmap/download/raster/grid/(:num)',       'WebMap::downloadRasterGrid/$1',     ['filter' => 'role:user']);
$routes->get('webmap/download/raster/province/(:num)',   'WebMap::downloadRasterProvince/$1', ['filter' => 'role:user']);
$routes->get('webmap/download/raster/(:num)',            'WebMap::downloadRasterByAOI/$1',    ['filter' => 'role:user']);

// -------------------------------------------------------------------------
// Metadata — admin and above
// -------------------------------------------------------------------------
$routes->get('metadata',  'Metadata::index', ['filter' => 'role:admin']);
$routes->post('metadata', 'Metadata::store', ['filter' => 'role:admin']);

// -------------------------------------------------------------------------
// Admin Hub — superadmin only
// -------------------------------------------------------------------------
$routes->get('admin', 'DatasetAdmin::index', ['filter' => 'role:superadmin']);

// Admin dapat upload dataset → masuk staging (pending review)
$routes->group('dataset', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('manage',         'DatasetAdmin::index');
    $routes->post('upload',        'DatasetAdmin::upload');
    $routes->get('my-submissions', 'DatasetAdmin::mySubmissions');
});

// Superadmin: quality control staging + hapus
$routes->group('dataset', ['filter' => 'role:superadmin'], static function ($routes) {
    $routes->post('delete/(:segment)/(:num)', 'DatasetAdmin::delete/$1/$2');
    $routes->get('staging', 'DatasetAdmin::stagingIndex');
    $routes->post('staging/(:segment)/(:num)/approve', 'DatasetAdmin::stagingApprove/$1/$2');
    $routes->post('staging/(:segment)/(:num)/reject',  'DatasetAdmin::stagingReject/$1/$2');
});

// ── Marketplace admin (superadmin only) ──────────────────────────────────
$routes->group('admin', ['filter' => 'role:superadmin'], static function ($routes) {
    // Stats dashboard
    $routes->get('stats',                              'AdminHub::stats');
    $routes->get('metadata-submissions',               'AdminHub::metadataSubmissions');
    $routes->get('metadata-submissions/(:num)',                        'AdminHub::metadataSubmissionDetail/$1');
    $routes->get('metadata-submissions/(:num)/download/(:segment)',    'AdminHub::downloadSubmissionFile/$1/$2');
    $routes->post('metadata-submissions/(:num)/approve', 'AdminHub::metadataApprove/$1');
    $routes->post('metadata-submissions/(:num)/reject',  'AdminHub::metadataReject/$1');
    $routes->post('upload-metadata-xml',               'AdminHub::uploadMetadataXml');

    // Data Providers
    $routes->get('providers',                          'AdminHub::providers');
    $routes->post('providers/store',                   'AdminHub::providerStore');
    $routes->post('providers/(:num)/update',           'AdminHub::providerUpdate/$1');
    $routes->post('providers/(:num)/toggle',           'AdminHub::providerToggle/$1');

    // Subscriptions
    $routes->get('subscriptions',                      'AdminHub::subscriptions');
    $routes->post('subscriptions/assign',              'AdminHub::subscriptionAssign');
    $routes->post('subscriptions/(:num)/cancel',       'AdminHub::subscriptionCancel/$1');

    // Revenue
    $routes->get('revenue',                            'AdminHub::revenue');
    $routes->post('revenue/generate',                  'AdminHub::revenueGenerate');
    $routes->post('revenue/(:num)/paid',               'AdminHub::revenueMarkPaid/$1');

    // Account management
    $routes->get('accounts',                           'AdminHub::accounts');
    $routes->post('accounts/create',                   'AdminHub::accountCreate');
    $routes->post('accounts/(:num)/set-role',          'AdminHub::accountSetRole/$1');
    $routes->post('accounts/(:num)/toggle-active',     'AdminHub::accountToggleActive/$1');
});

// ── Pending registrations (superadmin) ───────────────────────────────────
$routes->group('admin', ['filter' => 'role:superadmin'], static function ($routes) {
    $routes->get('pending',                                    'AdminHub::pendingRegistrations');
    $routes->post('pending/individual/(:num)/approve',         'AdminHub::approvePending/individual/$1');
    $routes->post('pending/individual/(:num)/reject',          'AdminHub::rejectPending/individual/$1');
    $routes->post('pending/team/(:num)/approve',               'AdminHub::approvePending/team/$1');
    $routes->post('pending/team/(:num)/reject',                'AdminHub::rejectPending/team/$1');
});

// ── User account ─────────────────────────────────────────────────────────
$routes->get('account', 'AccountController::index', ['filter' => 'role:user']);

// ── Account sub-pages (user+) ─────────────────────────────────────────────
$routes->group('account', ['filter' => 'role:user'], static function ($routes) {
    $routes->post('api-keys/generate',       'AccountController::generateApiKey');
    $routes->post('api-keys/(:num)/revoke',  'AccountController::revokeApiKey/$1');
    $routes->get('upgrade',                  'AccountController::upgradePrompt');
    $routes->get('invoice',                  'InvoiceController::listForUser');
    $routes->get('invoice/(:num)',           'InvoiceController::show/$1');
});

// ── Team Management (user+, team/enterprise/government tier) ──────────────
$routes->group('team', ['filter' => 'role:user'], static function ($routes) {
    $routes->get('/',                                   'TeamController::index');
    $routes->post('invite',                             'TeamController::invite');
    $routes->post('member/(:num)/remove',               'TeamController::removeMember/$1');
    $routes->post('member/(:num)/toggle-admin',         'TeamController::toggleAdmin/$1');
    $routes->post('invite/(:num)/cancel',               'TeamController::cancelInvite/$1');
});
$routes->get('join-team/(:segment)', 'TeamController::acceptInvite/$1');

// ── OGC Services (public) ─────────────────────────────────────────────────
$routes->get('ogc',     'OgcController::landing');
$routes->get('ogc/wms', 'OgcController::wms');
$routes->get('ogc/wfs', 'OgcController::wfs');
$routes->get('ogc/csw', 'OgcController::csw');

// ── REST API v1 ───────────────────────────────────────────────────────────
// Health is public (no auth)
$routes->get('api/v1/health',        'ApiController::health');
$routes->get('api/v1/public-stats',  'ApiController::publicStats');

// All other /api/v1/* endpoints require API key authentication
$routes->group('api/v1', ['filter' => 'apikey'], static function ($routes) {
    $routes->get('datasets',                        'ApiController::datasets');
    $routes->get('datasets/(:segment)/points',      'ApiController::points/$1');
    $routes->get('datasets/(:segment)',             'ApiController::datasetDetail/$1');
    $routes->get('catalog',                         'ApiController::catalog');
    $routes->get('user/quota',                      'ApiController::quota');
    $routes->get('user/downloads',                  'ApiController::downloads');
});

// ── API Documentation (public) ────────────────────────────────────────────
$routes->get('api/docs', 'ApiController::docs');

// ── Admin invoice management (superadmin) ────────────────────────────────
$routes->group('admin', ['filter' => 'role:superadmin'], static function ($routes) {
    $routes->post('invoice/generate',       'InvoiceController::generate');
    $routes->post('invoice/(:num)/pay',     'InvoiceController::markPaid/$1');
});

$routes->setAutoRoute(false);
