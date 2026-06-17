<?php

namespace App\Controllers;

use App\Libraries\MarketplaceService;
use App\Models\DownloadTransactionModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiController — GravPort Public REST API v1
 *
 * Base URL:  /api/v1/
 * Auth:      Authorization: Bearer gp_{key}   (requires Pro+ subscription)
 *            Applied by ApiKeyFilter — sets $this->request->apiUserId, apiKeyId, apiScopes
 *
 * All responses: JSON  {"status":"ok","data":{...}}  or  {"status":"error","message":"..."}
 * CRS: All GeoJSON outputs include EPSG:4326 declaration (WGS84 — native CRS of all data).
 */
class ApiController extends BaseController
{
    private MarketplaceService      $marketplace;
    private DownloadTransactionModel $transactions;

    public function __construct()
    {
        $this->marketplace  = new MarketplaceService();
        $this->transactions = new DownloadTransactionModel();
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/health  (public — no API key required)
    // ────────────────────────────────────────────────────────────────
    public function health(): ResponseInterface
    {
        return $this->json([
            'status'  => 'ok',
            'service' => 'GravPort REST API',
            'version' => '1.0',
            'ts'      => date('c'),
            'docs'    => site_url('api/docs'),
            'crs'     => 'EPSG:4326 (WGS84) — all spatial data',
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/public-stats  (public — no API key required)
    // ────────────────────────────────────────────────────────────────
    public function publicStats(): ResponseInterface
    {
        $db = \Config\Database::connect();

        $points = (int) $db->query(
            'SELECT COUNT(*) AS n FROM geoportal.point_grav_anom'
        )->getRowArray()['n'];

        $provinceCount = (int) $db->query(
            "SELECT COUNT(DISTINCT spatial_scope) AS n
             FROM geoportal.datasets
             WHERE spatial_scope IS NOT NULL AND spatial_scope <> ''"
        )->getRowArray()['n'];

        $lastUpdated = $db->query(
            'SELECT MAX(created_at) AS ts FROM geoportal.datasets'
        )->getRowArray()['ts'] ?? null;

        // Actual gravity value statistics per anomaly type (mean, min, max, std dev in mGal)
        $anomStats = $db->query("
            SELECT d.dataset_anom_type AS anom_type,
                   ROUND(AVG(p.point_value)::numeric, 2) AS mean_val,
                   ROUND(MIN(p.point_value)::numeric, 2) AS min_val,
                   ROUND(MAX(p.point_value)::numeric, 2) AS max_val,
                   ROUND(STDDEV(p.point_value)::numeric, 2) AS std_val,
                   COUNT(*) AS cnt
            FROM geoportal.point_grav_anom p
            JOIN geoportal.datasets_grav_anom d ON d.dataset_id = p.dataset_id
            GROUP BY d.dataset_anom_type
            ORDER BY d.dataset_anom_type
        ")->getResultArray();

        return $this->json([
            'status' => 'ok',
            'data'   => [
                'total_points'      => $points,
                'provinces_covered' => $provinceCount,
                'last_updated'      => $lastUpdated,
                'anom_stats'        => $anomStats,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/datasets   — list all published datasets
    // ────────────────────────────────────────────────────────────────
    public function datasets(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $rows    = $db->query(
            "SELECT dataset_code, title, spatial_scope, anomaly_type, data_level,
                    is_downloadable, is_viewable, items_count, description
             FROM geoportal.datasets
             WHERE is_downloadable = true OR is_viewable = true
             ORDER BY dataset_code"
        )->getResultArray();

        $userId = $this->request->apiUserId ?? 0;
        $quota  = $this->marketplace->checkQuota($userId);
        $tier   = $quota['tier'] ?? 'none';

        // Mark level-2 datasets inaccessible for Lite users
        foreach ($rows as &$row) {
            $row['accessible'] = $this->canAccessLevel((int)$row['data_level'], $tier);
            $row['endpoint']   = site_url('api/v1/datasets/' . $row['dataset_code']);
        }

        return $this->json(['datasets' => $rows, 'count' => count($rows)]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/datasets/{code}   — single dataset detail
    // ────────────────────────────────────────────────────────────────
    public function datasetDetail(string $code): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $row = $db->query(
            "SELECT d.*, m.abstract, m.keywords, m.lineage, m.use_constraints, m.spatial_resolution,
                    m.temporal_extent_begin, m.temporal_extent_end
             FROM geoportal.datasets d
             LEFT JOIN geoportal.dataset_metadata_xml m ON m.metadata_level = CONCAT(LOWER(d.anom_type), '_l', d.data_level)
             WHERE d.dataset_code = ?",
            [strtoupper($code)]
        )->getRowArray();

        if (!$row) {
            return $this->error('Dataset not found.', 404);
        }

        $userId = $this->request->apiUserId ?? 0;
        $quota  = $this->marketplace->checkQuota($userId);
        $tier   = $quota['tier'] ?? 'none';

        $row['accessible']     = $this->canAccessLevel((int)($row['data_level'] ?? 1), $tier);
        $row['crs']            = 'EPSG:4326';
        $row['points_endpoint'] = site_url("api/v1/datasets/{$code}/points");

        return $this->json(['dataset' => $row]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/catalog?q=&level=&scope=&anom_type=
    // ────────────────────────────────────────────────────────────────
    public function catalog(): ResponseInterface
    {
        $q       = $this->request->getGet('q');
        $level   = $this->request->getGet('level');
        $scope   = $this->request->getGet('scope');
        $anom    = strtoupper((string)$this->request->getGet('anom_type'));

        $db  = \Config\Database::connect();
        $sql = "SELECT dataset_code, title, spatial_scope, anomaly_type, data_level,
                       is_downloadable, is_viewable, items_count
                FROM geoportal.datasets
                WHERE (is_downloadable = true OR is_viewable = true)";
        $binds = [];

        if ($q) {
            $sql .= " AND (title ILIKE ? OR dataset_code ILIKE ?)";
            $binds[] = "%{$q}%";
            $binds[] = "%{$q}%";
        }
        if ($level && in_array((int)$level, [1, 2])) {
            $sql .= " AND data_level = ?";
            $binds[] = (int)$level;
        }
        if ($scope) {
            $sql .= " AND spatial_scope ILIKE ?";
            $binds[] = "%{$scope}%";
        }
        if ($anom && in_array($anom, ['FAA', 'CBA', 'SBA', 'BA'])) {
            $sql .= " AND anomaly_type ILIKE ?";
            $binds[] = "%{$anom}%";
        }

        $sql .= " ORDER BY dataset_code LIMIT 200";

        $rows = $db->query($sql, $binds)->getResultArray();

        return $this->json(['datasets' => $rows, 'count' => count($rows)]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/user/quota   — caller's current quota & tier
    // ────────────────────────────────────────────────────────────────
    public function quota(): ResponseInterface
    {
        $userId = $this->request->apiUserId ?? 0;
        $quota  = $this->marketplace->checkQuota($userId);

        return $this->json([
            'tier'    => $quota['tier'] ?? 'none',
            'allowed' => $quota['allowed'] ?? false,
            'used'    => $quota['used']    ?? 0,
            'limit'   => $quota['limit']   ?? null,
            'unit'    => 'bytes',
            'reason'  => $quota['reason']  ?? null,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/user/downloads   — caller's download history
    // ────────────────────────────────────────────────────────────────
    public function downloads(): ResponseInterface
    {
        $userId = $this->request->apiUserId ?? 0;
        $limit  = min((int)($this->request->getGet('limit') ?? 50), 100);
        $rows   = $this->transactions->recentForUser($userId, $limit);

        return $this->json([
            'downloads' => $rows,
            'count'     => count($rows),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/v1/datasets/{code}/points
    //     ?bbox=minx,miny,maxx,maxy  (required, max 0.5°×0.5°)
    //     &anom_type=FAA|CBA|SBA|BA  (default: FAA)
    //     &level=1|2                  (default: 1; level 2 requires Pro+)
    //     &limit=N                    (max 5000)
    //
    // Returns GeoJSON FeatureCollection (EPSG:4326)
    // ────────────────────────────────────────────────────────────────
    public function points(string $code): ResponseInterface
    {
        $userId = $this->request->apiUserId ?? 0;
        $quota  = $this->marketplace->checkQuota($userId);
        $tier   = $quota['tier'] ?? 'none';

        // ── Parse & validate BBOX ─────────────────────────────────
        $bboxStr = $this->request->getGet('bbox');
        if (!$bboxStr) {
            return $this->error('Parameter bbox=minx,miny,maxx,maxy is required.', 400);
        }

        $parts = explode(',', $bboxStr);
        if (count($parts) !== 4) {
            return $this->error('bbox must be 4 values: minx,miny,maxx,maxy', 400);
        }

        [$minx, $miny, $maxx, $maxy] = array_map('floatval', $parts);

        // Validate extent (max 1° × 1° to prevent abuse)
        if (($maxx - $minx) > 1.0 || ($maxy - $miny) > 1.0) {
            return $this->error('BBOX extent too large. Maximum 1°×1° per request.', 400);
        }
        if ($minx >= $maxx || $miny >= $maxy) {
            return $this->error('Invalid BBOX: minx must be < maxx, miny must be < maxy.', 400);
        }

        // ── Level access check ────────────────────────────────────
        $level = (int)($this->request->getGet('level') ?? 1);
        if (!in_array($level, [1, 2])) {
            $level = 1;
        }
        if ($level === 2 && !$this->canAccessLevel(2, $tier)) {
            return $this->error('Level 2 data requires Pro or higher subscription.', 403);
        }

        // ── Anomaly type ──────────────────────────────────────────
        $anomType = strtoupper((string)($this->request->getGet('anom_type') ?? 'FAA'));
        if (!in_array($anomType, ['FAA', 'CBA', 'SBA', 'BA', 'RAW'])) {
            $anomType = 'FAA';
        }

        $limit = min((int)($this->request->getGet('limit') ?? 5000), 5000);

        // ── Query PostGIS ─────────────────────────────────────────
        $db  = \Config\Database::connect();
        $sql = "SELECT point_id,
                       ST_X(geom)        AS longitude,
                       ST_Y(geom)        AS latitude,
                       point_value,
                       d.dataset_anom_type AS point_anom_type,
                       p.point_obs_type,
                       d.dataset_level    AS data_level
                FROM geoportal.point_grav_anom p
                JOIN geoportal.datasets_grav_anom d ON d.dataset_id = p.dataset_id
                WHERE p.geom && ST_MakeEnvelope(?,?,?,?,4326)
                  AND d.dataset_anom_type = ?
                  AND d.dataset_level = ?
                LIMIT ?";

        $rows = $db->query($sql, [$minx, $miny, $maxx, $maxy, $anomType, $level, $limit])
                   ->getResultArray();

        // ── Build GeoJSON ─────────────────────────────────────────
        $features = [];
        foreach ($rows as $r) {
            $features[] = [
                'type'       => 'Feature',
                'geometry'   => [
                    'type'        => 'Point',
                    'coordinates' => [(float)$r['longitude'], (float)$r['latitude']],
                ],
                'properties' => [
                    'point_id'      => (int)$r['point_id'],
                    'point_value'   => (float)$r['point_value'],
                    'anom_type'     => $r['point_anom_type'],
                    'obs_type'      => $r['point_obs_type'],
                    'data_level'    => (int)$r['data_level'],
                ],
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'crs'  => [
                'type'       => 'name',
                'properties' => ['name' => 'EPSG:4326'],
            ],
            'bbox'     => [$minx, $miny, $maxx, $maxy],
            'features' => $features,
            '_meta'    => [
                'count'      => count($features),
                'limit'      => $limit,
                'anom_type'  => $anomType,
                'data_level' => $level,
                'dataset'    => strtoupper($code),
                'generated'  => date('c'),
            ],
        ];

        $response = service('response');
        $response->setStatusCode(200);
        $response->setContentType('application/geo+json; charset=utf-8');
        $response->setHeader('X-GravPort-CRS', 'EPSG:4326');
        $response->setHeader('X-GravPort-Count', (string)count($features));
        $response->setBody(json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response;
    }

    // ════════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════════

    private function json(array $data, int $status = 200): ResponseInterface
    {
        $response = service('response');
        $response->setStatusCode($status);
        $response->setContentType('application/json; charset=utf-8');
        $response->setHeader('X-GravPort-API', '1.0');
        $response->setHeader('X-GravPort-CRS', 'EPSG:4326');
        $response->setBody(json_encode(
            ['status' => $status < 400 ? 'ok' : 'error'] + $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ));
        return $response;
    }

    private function error(string $message, int $status = 400): ResponseInterface
    {
        return $this->json(['message' => $message], $status);
    }

    /**
     * Check if the given tier can access the specified data level.
     * Level 1 & 2: semua tier berbayar (lite, solo, pro, team, enterprise, government).
     * Perbedaan lite vs pro hanya di kuota download, bukan akses data.
     */
    private function canAccessLevel(int $level, string $tier): bool
    {
        $paidTiers = ['lite', 'solo', 'pro', 'team', 'enterprise', 'government'];
        return in_array($tier, $paidTiers);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /api/docs  (public)  — API Documentation page
    // ────────────────────────────────────────────────────────────────
    public function docs(): string
    {
        return view('v_api_docs', [
            'baseUrl'    => site_url('api/v1'),
            'activePage' => 'api',
        ]);
    }
}
