<?php

namespace App\Commands;

use App\Libraries\DatasetImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImportDatasetPackage extends BaseCommand
{
    protected $group = 'Dataset';
    protected $name = 'dataset:import';
    protected $description = 'Import paket dataset GravPort dari writable/imports/<folder>.';
    protected $usage = 'dataset:import [package]';
    protected $arguments = [
        'package' => 'Nama folder paket import. Jika kosong, otomatis memakai folder terbaru.',
    ];

    public function run(array $params)
    {
        $package = $params[0] ?? null;
        $service = new DatasetImportService();

        try {
            $report = $service->importPackage($package);

            CLI::write('Import berhasil dijalankan.', 'green');
            CLI::write('Paket: ' . $report['package']);
            CLI::write('FAA Level 1: ' . ($report['level1']['faa']['rows'] ?? 0) . ' titik'
                . ' (skip ' . ($report['level1']['faa']['skipped_rows'] ?? 0) . ')');
            CLI::write('CBA Level 1: ' . ($report['level1']['cba']['rows'] ?? 0) . ' titik'
                . ' (skip ' . ($report['level1']['cba']['skipped_rows'] ?? 0) . ')');
            CLI::write('FAA Level 2: ' . ($report['level2']['faa']['rows'] ?? 0) . ' grid'
                . ' @ ' . ($report['level2']['faa']['grid_step_deg'] ?? 0.125) . ' derajat');
            CLI::write('CBA Level 2: ' . ($report['level2']['cba']['rows'] ?? 0) . ' grid'
                . ' @ ' . ($report['level2']['cba']['grid_step_deg'] ?? 0.125) . ' derajat');
            CLI::write('Metadata Level 1: ' . ($report['metadata']['level1']['file_identifier'] ?? '-'));
            CLI::write('Metadata Level 2: ' . ($report['metadata']['level2']['file_identifier'] ?? '-'));
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }
}
