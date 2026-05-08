<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use RuntimeException;

class MetadataSubmissionService
{
    private const UPLOAD_ROOT = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'metadata_submissions';

    public function store(array $payload, array $files, string $submittedByRole = 'user'): array
    {
        $db = Database::connect('gravport');
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

        $db->table('dataset_user_submissions')->insert($row);
        $id = $db->insertID();

        return [
            'id' => $id,
            'stored_files' => $storedFiles,
            'submission_key' => $submissionKey,
        ];
    }

    private function ensureTable($db): void
    {
        $db->query(<<<SQL
CREATE TABLE IF NOT EXISTS testing.dataset_user_submissions (
    id bigserial PRIMARY KEY,
    metadata_file_identifier text NOT NULL,
    jenis_data text NOT NULL,
    provinsi text NOT NULL,
    level_data text NOT NULL,
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
}
