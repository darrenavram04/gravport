<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('landing', 'Home::landingPage');
$routes->get('landing2', 'Home::landingPage2');

// Auth
$routes->get('login', 'AuthController::loginForm');
$routes->post('login', 'AuthController::loginPost');
$routes->get('logout', 'AuthController::logout');

// Metadata / admin
$routes->get('metadata', 'Metadata::index', ['filter' => 'role:admin,user']);
$routes->post('metadata', 'Metadata::store', ['filter' => 'role:admin,user']);

$routes->group('dataset', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('manage', 'DatasetAdmin::index');
    $routes->post('upload', 'DatasetAdmin::upload');
    $routes->post('delete/(:segment)/(:num)', 'DatasetAdmin::delete/$1/$2');
});

// Catalog
$routes->get('catalog', 'Catalog::index');
$routes->get('catalog/view/(:num)', 'Catalog::view/$1');
$routes->get('catalog/download/(:num)', 'Catalog::download/$1');
$routes->get('catalog/download-metadata/(:num)', 'Catalog::downloadMetadata/$1');
$routes->get('catalog/geojson/(:num)', 'Catalog::geojson/$1');

// WebMap page
$routes->get('webmap', 'WebMap::index');

// WebMap API
$routes->get('webmap/bootstrap', 'WebMap::bootstrap');
$routes->get('webmap/provinces', 'WebMap::provinces');
$routes->get('webmap/aoi', 'WebMap::aoi');
$routes->get('webmap/faa', 'WebMap::faa');
$routes->get('webmap/layer', 'WebMap::layer');
$routes->post('webmap/layer', 'WebMap::layer');
$routes->get('webmap/feature-meta/(:segment)/(:segment)', 'WebMap::featureMeta/$1/$2');
$routes->post('webmap/download/vector', 'WebMap::downloadVector');
$routes->post('webmap/download/metadata', 'WebMap::downloadMetadata');
$routes->post('webmap/clip/raster', 'WebMap::clipRaster');
$routes->get('webmap/download/raster/grid/(:num)', 'WebMap::downloadRasterGrid/$1');
$routes->get('webmap/download/raster/province/(:num)', 'WebMap::downloadRasterProvince/$1');
$routes->get('webmap/download/raster/(:num)', 'WebMap::downloadRasterByAOI/$1');

// Test routes kept for local diagnostics
$routes->get('tes', static fn () => 'ROUTE WORKS');
$routes->get('tes-download/(:num)', static fn ($id) => 'TES ROUTE OK ID = ' . $id);

$routes->setAutoRoute(false);
