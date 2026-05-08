<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\DatasetImportService;

class DatasetAdmin extends BaseController
{
    private DatasetImportService $importer;

    public function __construct()
    {
        $this->importer = new DatasetImportService();
    }

    public function index()
    {
        return view('v_admin_manage', [
            'packageSummary' => $this->importer->summarizeLatestPackage(),
            'importReport' => session()->getFlashdata('importReport'),
        ]);
    }

    public function upload()
    {
        try {
            $package = trim((string) $this->request->getPost('package'));
            $report = $this->importer->importPackage($package !== '' ? $package : null);

            return redirect()->to(site_url('dataset/manage'))
                ->with('success', "Import paket {$report['package']} berhasil dijalankan.")
                ->with('importReport', $report);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('dataset/manage'))
                ->with('error', $e->getMessage());
        }
    }

    public function delete(string $source, int $id)
    {
        // TODO: implement delete logic
        return redirect()->back()->with('success', "Delete endpoint is ready (source={$source}, id={$id}).");
    }
}
