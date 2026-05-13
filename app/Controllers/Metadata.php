<?php

namespace App\Controllers;

use App\Libraries\MetadataSubmissionService;

class Metadata extends BaseController
{
    private MetadataSubmissionService $submissionService;

    public function __construct()
    {
        $this->submissionService = new MetadataSubmissionService();
    }

    public function index()
    {
        return view('v_metadata', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function store()
    {
        $rules = [
            'metadata_file_identifier' => 'required',
            'jenis_data' => 'required',
            'provinsi' => 'required',
            'level_data' => 'required',
            'bahasa' => 'required',
            'character_set' => 'required',
            'hierarchy_level' => 'required',
            'metadata_date_stamp' => 'required',
            'individual_name' => 'required',
            'organisation_name' => 'required',
            'position_name' => 'required',
            'contact_role' => 'required',
            'voice' => 'required',
            'facsimilie' => 'required',
            'delivery_point' => 'required',
            'city' => 'required',
            'administrative_area' => 'required',
            'postal_code' => 'required',
            'country' => 'required',
            'electronic_mail_address' => 'required|regex_match[/^\s*[^,\s@]+@[^,\s@]+\.[^,\s@]+(\s*,\s*[^,\s@]+@[^,\s@]+\.[^,\s@]+)*\s*$/]',
        ];

        $messages = [
            'electronic_mail_address' => [
                'regex_match' => 'Electronic Mail Address harus berupa satu email valid atau beberapa email valid yang dipisahkan dengan koma.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $fileRules = [
            'shapefile_zip' => 'if_exist|max_size[shapefile_zip,512000]|ext_in[shapefile_zip,zip]',
            'tabular_file' => 'if_exist|max_size[tabular_file,512000]|ext_in[tabular_file,csv,xls,xlsx]',
            'raster_file' => 'if_exist|max_size[raster_file,1024000]|ext_in[raster_file,tif,tiff]',
        ];

        if (! $this->validate($fileRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $shapefileZip = $this->request->getFile('shapefile_zip');
        $tabularFile = $this->request->getFile('tabular_file');
        $rasterFile = $this->request->getFile('raster_file');

        $hasUpload = $this->hasUploadedFile($shapefileZip)
            || $this->hasUploadedFile($tabularFile)
            || $this->hasUploadedFile($rasterFile);

        if (! $hasUpload) {
            return redirect()->back()->withInput()->with('errors', [
                'Unggah minimal satu file ZIP SHP, Excel/CSV, atau TIFF.',
            ]);
        }

        $fileContentErrors = $this->submissionService->validateSubmissionFiles([
            'shapefile_zip' => $shapefileZip,
            'tabular_file' => $tabularFile,
            'raster_file' => $rasterFile,
        ]);

        if ($fileContentErrors !== []) {
            return redirect()->back()->withInput()->with('errors', $fileContentErrors);
        }

        $result = $this->submissionService->store(
            [
                'metadata_file_identifier' => (string) $this->request->getPost('metadata_file_identifier'),
                'jenis_data' => (string) $this->request->getPost('jenis_data'),
                'provinsi' => (string) $this->request->getPost('provinsi'),
                'level_data' => (string) $this->request->getPost('level_data'),
                'bahasa' => (string) $this->request->getPost('bahasa'),
                'character_set' => (string) $this->request->getPost('character_set'),
                'hierarchy_level' => (string) $this->request->getPost('hierarchy_level'),
                'metadata_date_stamp' => (string) $this->request->getPost('metadata_date_stamp'),
                'individual_name' => (string) $this->request->getPost('individual_name'),
                'organisation_name' => (string) $this->request->getPost('organisation_name'),
                'position_name' => (string) $this->request->getPost('position_name'),
                'contact_role' => (string) $this->request->getPost('contact_role'),
                'voice' => (string) $this->request->getPost('voice'),
                'facsimilie' => (string) $this->request->getPost('facsimilie'),
                'delivery_point' => (string) $this->request->getPost('delivery_point'),
                'city' => (string) $this->request->getPost('city'),
                'administrative_area' => (string) $this->request->getPost('administrative_area'),
                'postal_code' => (string) $this->request->getPost('postal_code'),
                'country' => (string) $this->request->getPost('country'),
                'electronic_mail_address' => (string) $this->request->getPost('electronic_mail_address'),
            ],
            [
                'shapefile_zip' => $shapefileZip,
                'tabular_file' => $tabularFile,
                'raster_file' => $rasterFile,
            ],
            auth_current_role()
        );

        return redirect()->to(site_url('metadata'))
            ->with('success', 'Metadata dan file berhasil disimpan ke staging submission #' . $result['id'] . '.');
    }

    private function hasUploadedFile($file): bool
    {
        return $file !== null
            && $file->isValid()
            && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }
}
