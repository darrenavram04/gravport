<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use RuntimeException;

class MetadataSubmissionService
{
    private const UPLOAD_ROOT = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'metadata_submissions';
    private const ZIP_EOCD_SIGNATURE = "PK\x05\x06";
    private const ZIP_CENTRAL_SIGNATURE = "PK\x01\x02";
    private const ZIP_MAX_COMMENT_BYTES = 65535;

    public function validateSubmissionFiles(array $files): array
    {
        $errors = [];

        $shapefileError = $this->validateShapefileZip($files['shapefile_zip'] ?? null);
        if ($shapefileError !== null) {
            $errors['shapefile_zip'] = $shapefileError;
        }

        $tabularError = $this->validateTabularFile($files['tabular_file'] ?? null);
        if ($tabularError !== null) {
            $errors['tabular_file'] = $tabularError;
        }

        $rasterError = $this->validateRasterFile($files['raster_file'] ?? null);
        if ($rasterError !== null) {
            $errors['raster_file'] = $rasterError;
        }

        return $errors;
    }

    public function store(array $payload, array $files, string $submittedByRole = 'user', int $accId = 0): array
    {
        $db = Database::connect();
        $this->ensureTable($db);

        $submissionKey = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $targetDir = self::UPLOAD_ROOT . DIRECTORY_SEPARATOR . $submissionKey;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Folder penyimpanan upload metadata tidak dapat dibuat.');
        }

        $storedFiles = [
            'shapefile_zip_path' => $this->storeUploadedFile($files['shapefile_zip'] ?? null, $targetDir, 'shapefile'),
            'tabular_file_path' => $this->storeUploadedFile($files['tabular_file'] ?? null, $targetDir, 'tabular'),
            'raster_file_path' => $this->storeUploadedFile($files['raster_file'] ?? null, $targetDir, 'raster'),
        ];

        $row = array_merge($payload, $storedFiles, [
            'submitted_by_role' => $submittedByRole,
        ]);

        $ok = $db->table('dataset_user_submissions')->insert($row);
        if (! $ok) {
            $err = $db->error();
            throw new \RuntimeException(
                'DB insert gagal [' . ($err['code'] ?? '?') . ']: ' . ($err['message'] ?? 'unknown error') .
                ' | row keys: ' . implode(', ', array_keys($row))
            );
        }
        $id = $db->insertID();

        // Stage CSV dan TIFF ke staging tables untuk review superadmin
        $obsType     = $this->resolveObsType((string) ($payload['jenis_data'] ?? ''));
        $datasetCode = (string) ($payload['dataset_code'] ?? '');
        if ($accId > 0 && $id > 0) {
            if (!empty($storedFiles['tabular_file_path'])) {
                $this->stageTabularCsv(
                    ROOTPATH . $storedFiles['tabular_file_path'],
                    $accId,
                    $obsType,
                    basename($storedFiles['tabular_file_path']),
                    $id,
                    $datasetCode
                );
            }
            if (!empty($storedFiles['raster_file_path'])) {
                $this->stageRasterFile(
                    $storedFiles['raster_file_path'],
                    $accId,
                    basename($storedFiles['raster_file_path']),
                    $id,
                    $datasetCode
                );
            }
        }

        return [
            'id' => $id,
            'stored_files' => $storedFiles,
            'submission_key' => $submissionKey,
        ];
    }

    private function resolveObsType(string $jenisData): string
    {
        return match (strtolower(trim($jenisData))) {
            'airborne' => 'airborne',
            'marine', 'laut' => 'marine',
            default => 'terrestrial',
        };
    }

    private function stageTabularCsv(string $filePath, int $accId, string $obsType, string $sourceName, int $submissionId = 0, string $datasetCode = ''): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }

        $fh = fopen($filePath, 'r');
        if ($fh === false) {
            return;
        }

        $header = fgetcsv($fh);
        if (!is_array($header)) {
            fclose($fh);
            return;
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);

        $idxLat   = $this->colIndex($header, ['latitude', 'lat', 'y']);
        $idxLon   = $this->colIndex($header, ['longitude', 'lon', 'long', 'x']);
        $idxVal   = $this->colIndex($header, ['anomaly_value', 'anomaly', 'faa', 'ba', 'value', 'gravity']);

        if ($idxLat === null || $idxLon === null || $idxVal === null) {
            fclose($fh);
            return;
        }

        $db   = Database::connect();
        $conn = $db->connID;

        // Lookup dataset_id from datasets_grav_anom based on dataset_code
        $datasetId = null;
        if ($datasetCode !== '') {
            $anomType = str_starts_with($datasetCode, 'faa') ? 'FAA' : 'CBA';
            $level    = str_ends_with($datasetCode, '_l1') ? 1 : 2;
            $dsRow    = $db->query(
                "SELECT dataset_id FROM geoportal.datasets_grav_anom WHERE dataset_anom_type = ? AND dataset_level = ? LIMIT 1",
                [$anomType, $level]
            )->getRowArray();
            $datasetId = $dsRow['dataset_id'] ?? null;
        }

        $batch = [];
        while (($row = fgetcsv($fh)) !== false) {
            $lat = isset($row[$idxLat]) ? (float) $row[$idxLat] : null;
            $lon = isset($row[$idxLon]) ? (float) $row[$idxLon] : null;
            $val = isset($row[$idxVal]) ? (float) $row[$idxVal] : null;

            if ($lat === null || $lon === null || $val === null) {
                continue;
            }
            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                continue;
            }

            $batch[] = [$lat, $lon, $val];

            if (count($batch) >= 500) {
                $this->insertStagingPointBatch($conn, $batch, $accId, $obsType, $sourceName, $submissionId, $datasetId);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->insertStagingPointBatch($conn, $batch, $accId, $obsType, $sourceName, $submissionId, $datasetId);
        }

        fclose($fh);
    }

    private function colIndex(array $header, array $candidates): ?int
    {
        foreach ($candidates as $name) {
            $idx = array_search($name, $header, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }
        return null;
    }

    private function insertStagingPointBatch($conn, array $batch, int $accId, string $obsType, string $source, int $submissionId = 0, $datasetId = null): void
    {
        $values = [];
        $subE    = $submissionId > 0 ? pg_escape_literal($conn, (string) $submissionId) : 'NULL';
        $dsE     = $datasetId !== null ? pg_escape_literal($conn, (string) $datasetId) : 'NULL';
        foreach ($batch as [$lat, $lon, $val]) {
            $latE  = pg_escape_literal($conn, (string) $lat);
            $lonE  = pg_escape_literal($conn, (string) $lon);
            $valE  = pg_escape_literal($conn, (string) $val);
            $accE  = pg_escape_literal($conn, (string) $accId);
            $obsE  = pg_escape_literal($conn, $obsType);
            $srcE  = pg_escape_literal($conn, $source);
            $values[] = "($accE, $valE, $obsE, $srcE, $latE, $lonE, ST_SetSRID(ST_MakePoint($lonE, $latE), 4326), $subE, $dsE)";
        }

        $sql = 'INSERT INTO geoportal.staging_gravity_points
                    (acc_id, point_value, point_obs_type, source_file, lat, lon, geom, submission_id, dataset_id)
                VALUES ' . implode(',', $values);
        pg_query($conn, $sql);
    }

    private function stageRasterFile(string $relativePath, int $accId, string $source, int $submissionId = 0, string $datasetCode = ''): void
    {
        $db   = Database::connect();
        $conn = $db->connID;

        // Lookup dataset_id for the raster
        $datasetId = null;
        if ($datasetCode !== '') {
            $anomType = str_starts_with($datasetCode, 'faa') ? 'FAA' : 'CBA';
            $level    = str_ends_with($datasetCode, '_l1') ? 1 : 2;
            $dsRow    = $db->query(
                "SELECT dataset_id FROM geoportal.datasets_grav_anom WHERE dataset_anom_type = ? AND dataset_level = ? LIMIT 1",
                [$anomType, $level]
            )->getRowArray();
            $datasetId = $dsRow['dataset_id'] ?? null;
        }

        $accE  = pg_escape_literal($conn, (string) $accId);
        $srcE  = pg_escape_literal($conn, $source);
        $pathE = pg_escape_literal($conn, $relativePath);
        $subE  = $submissionId > 0 ? pg_escape_literal($conn, (string) $submissionId) : 'NULL';
        $dsE   = $datasetId !== null ? pg_escape_literal($conn, (string) $datasetId) : 'NULL';

        pg_query($conn, "INSERT INTO geoportal.staging_gravity_rasters
                             (acc_id, source_file, review_status, submission_id, dataset_id)
                         VALUES ($accE, $pathE, 'pending', $subE, $dsE)");
    }

    private function ensureTable($db): void
    {
        $db->query(<<<SQL
CREATE TABLE IF NOT EXISTS geoportal.dataset_user_submissions (
    id bigserial PRIMARY KEY,
    metadata_file_identifier text NOT NULL,
    jenis_data text NOT NULL,
    provinsi text NOT NULL,
    level_data text NOT NULL,
    dataset_code text,
    acc_id bigint,
    bahasa text NOT NULL,
    character_set text NOT NULL,
    hierarchy_level text NOT NULL,
    metadata_date_stamp date NOT NULL,
    individual_name text NOT NULL,
    organisation_name text NOT NULL,
    position_name text NOT NULL,
    contact_role text NOT NULL,
    voice text NOT NULL,
    facsimilie text NOT NULL,
    delivery_point text NOT NULL,
    city text NOT NULL,
    administrative_area text NOT NULL,
    postal_code text NOT NULL,
    country text NOT NULL,
    electronic_mail_address text NOT NULL,
    shapefile_zip_path text,
    tabular_file_path text,
    raster_file_path text,
    submitted_by_role text NOT NULL DEFAULT 'user',
    submitted_at timestamptz NOT NULL DEFAULT now()
)
SQL);
        // Add columns that may be missing from tables created before this version
        $db->query('ALTER TABLE geoportal.dataset_user_submissions ADD COLUMN IF NOT EXISTS dataset_code text');
        $db->query('ALTER TABLE geoportal.dataset_user_submissions ADD COLUMN IF NOT EXISTS acc_id bigint');
    }

    private function validateShapefileZip(?UploadedFile $file): ?string
    {
        if (! $this->shouldInspectFile($file)) {
            return null;
        }

        if (! $file->isValid()) {
            return 'Upload ZIP SHP tidak valid: ' . $file->getErrorString();
        }

        $path = $file->getTempName();
        if (! is_file($path) || ! is_readable($path)) {
            return 'File ZIP SHP tidak dapat dibaca.';
        }

        if (! $this->hasZipSignature($path)) {
            return 'File ZIP SHP bukan arsip ZIP yang valid.';
        }

        try {
            $entries = $this->zipEntryNames($path);
        } catch (RuntimeException) {
            return 'File ZIP SHP tidak dapat dibaca sebagai arsip ZIP yang valid.';
        }

        if ($entries === []) {
            return 'File ZIP SHP kosong atau tidak memiliki entri file.';
        }

        if (! $this->containsShapefileBundle($entries)) {
            return 'File ZIP SHP harus berisi minimal .shp, .shx, dan .dbf dengan nama layer yang sama.';
        }

        return null;
    }

    private function validateTabularFile(?UploadedFile $file): ?string
    {
        if (! $this->shouldInspectFile($file)) {
            return null;
        }

        if (! $file->isValid()) {
            return 'Upload file tabular tidak valid: ' . $file->getErrorString();
        }

        $path = $file->getTempName();
        if (! is_file($path) || ! is_readable($path)) {
            return 'File tabular tidak dapat dibaca.';
        }

        $extension = strtolower($file->getClientExtension() ?: $file->getExtension() ?: '');

        return match ($extension) {
            'csv' => $this->looksLikeCsv($path)
                ? null
                : 'File CSV tidak valid atau tidak memiliki struktur tabel yang terbaca.',
            'xlsx' => $this->looksLikeXlsx($path)
                ? null
                : 'File XLSX tidak valid atau struktur workbook tidak lengkap.',
            'xls' => $this->hasOleSignature($path)
                ? null
                : 'File XLS tidak valid atau bukan workbook Excel biner yang dikenali.',
            default => 'Format file tabular tidak didukung.',
        };
    }

    private function validateRasterFile(?UploadedFile $file): ?string
    {
        if (! $this->shouldInspectFile($file)) {
            return null;
        }

        if (! $file->isValid()) {
            return 'Upload raster tidak valid: ' . $file->getErrorString();
        }

        $path = $file->getTempName();
        if (! is_file($path) || ! is_readable($path)) {
            return 'File raster tidak dapat dibaca.';
        }

        if (! $this->hasTiffSignature($path)) {
            return 'File TIFF tidak valid atau header raster tidak dikenali.';
        }

        return null;
    }

    private function storeUploadedFile(?UploadedFile $file, string $targetDir, string $prefix): ?string
    {
        if ($file === null || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $extension = strtolower($file->getClientExtension() ?: $file->getExtension() ?: 'bin');
        $safeName = $prefix . '_' . date('His') . '_' . bin2hex(random_bytes(3)) . '.' . $extension;

        $file->move($targetDir, $safeName);

        return $this->relativePath($targetDir . DIRECTORY_SEPARATOR . $safeName);
    }

    private function relativePath(string $path): string
    {
        $real = realpath($path) ?: $path;
        $root = realpath(ROOTPATH);

        if ($root && strpos($real, $root) === 0) {
            return ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
        }

        return str_replace('\\', '/', $real);
    }

    private function shouldInspectFile(?UploadedFile $file): bool
    {
        return $file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }

    private function hasZipSignature(string $path): bool
    {
        $bytes = $this->readBytes($path, 4);

        return str_starts_with($bytes, "PK");
    }

    private function hasOleSignature(string $path): bool
    {
        return $this->readBytes($path, 8) === hex2bin('D0CF11E0A1B11AE1');
    }

    private function hasTiffSignature(string $path): bool
    {
        $header = $this->readBytes($path, 4);

        return in_array($header, ["II*\x00", "MM\x00*", "II+\x00", "MM\x00+"], true);
    }

    private function looksLikeCsv(string $path): bool
    {
        $sample = $this->readBytes($path, 8192);
        if ($sample === '') {
            return false;
        }

        $sample = preg_replace('/^\xEF\xBB\xBF/', '', $sample) ?? $sample;
        $lines = preg_split('/\R/u', $sample) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));

        if ($lines === []) {
            return false;
        }

        $header = $lines[0];

        return str_contains($header, ',') || str_contains($header, ';') || str_contains($header, "\t");
    }

    private function looksLikeXlsx(string $path): bool
    {
        if (! $this->hasZipSignature($path)) {
            return false;
        }

        try {
            $entries = $this->zipEntryNames($path);
        } catch (RuntimeException) {
            return false;
        }

        if ($entries === []) {
            return false;
        }

        $normalized = array_map(
            static fn (string $entry): string => ltrim(str_replace('\\', '/', strtolower($entry)), '/'),
            $entries
        );

        return in_array('[content_types].xml', $normalized, true)
            && in_array('xl/workbook.xml', $normalized, true);
    }

    private function containsShapefileBundle(array $entries): bool
    {
        $groups = [];

        foreach ($entries as $entry) {
            $normalized = ltrim(str_replace('\\', '/', strtolower($entry)), '/');
            if ($normalized === '' || str_ends_with($normalized, '/')) {
                continue;
            }

            $basename = pathinfo($normalized, PATHINFO_FILENAME);
            $extension = pathinfo($normalized, PATHINFO_EXTENSION);

            if ($basename === '' || $extension === '') {
                continue;
            }

            $groups[$basename][$extension] = true;
        }

        foreach ($groups as $extensions) {
            if (isset($extensions['shp'], $extensions['shx'], $extensions['dbf'])) {
                return true;
            }
        }

        return false;
    }

    private function zipEntryNames(string $path): array
    {
        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('ZIP tidak dapat dibaca.');
        }

        $eocdOffset = $this->findZipEndOfCentralDirectory($binary);
        $eocd = unpack(
            'vdisk/vcentralDisk/ventriesDisk/ventriesTotal/VcentralSize/VcentralOffset/vcommentLength',
            substr($binary, $eocdOffset + 4, 18)
        );

        if (! is_array($eocd)) {
            throw new RuntimeException('EOCD ZIP tidak valid.');
        }

        $entriesTotal = (int) ($eocd['entriesTotal'] ?? 0);
        $centralOffset = (int) ($eocd['centralOffset'] ?? 0);
        $length = strlen($binary);
        $offset = $centralOffset;
        $entries = [];

        for ($index = 0; $index < $entriesTotal; $index++) {
            if ($offset + 46 > $length || substr($binary, $offset, 4) !== self::ZIP_CENTRAL_SIGNATURE) {
                throw new RuntimeException('Central directory ZIP tidak valid.');
            }

            $header = unpack(
                'vversionMade/vversionNeeded/vflags/vcompression/vmodTime/vmodDate/Vcrc/VcompressedSize/VuncompressedSize/vnameLength/vextraLength/vcommentLength/vdiskStart/vinternalAttributes/VexternalAttributes/VlocalOffset',
                substr($binary, $offset + 4, 42)
            );

            if (! is_array($header)) {
                throw new RuntimeException('Header entri ZIP tidak valid.');
            }

            $nameLength = (int) ($header['nameLength'] ?? 0);
            $extraLength = (int) ($header['extraLength'] ?? 0);
            $commentLength = (int) ($header['commentLength'] ?? 0);
            $name = substr($binary, $offset + 46, $nameLength);

            if ($name !== false && $name !== '') {
                $entries[] = $name;
            }

            $offset += 46 + $nameLength + $extraLength + $commentLength;
        }

        return $entries;
    }

    private function findZipEndOfCentralDirectory(string $binary): int
    {
        $length = strlen($binary);
        $start = max(0, $length - (22 + self::ZIP_MAX_COMMENT_BYTES));

        for ($offset = $length - 22; $offset >= $start; $offset--) {
            if (substr($binary, $offset, 4) === self::ZIP_EOCD_SIGNATURE) {
                return $offset;
            }
        }

        throw new RuntimeException('EOCD ZIP tidak ditemukan.');
    }

    private function readBytes(string $path, int $length): string
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        try {
            $bytes = fread($handle, $length);
            return $bytes === false ? '' : $bytes;
        } finally {
            fclose($handle);
        }
    }
}
