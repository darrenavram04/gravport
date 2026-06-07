<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('v_template');
    }

    public function vectorMap(): string
    {
        $data = [
            'judul' => 'Vector Map',
            'page'  => 'v_2d'
        ];
        return view('v_template', $data);
    }

    public function baseMap(): string
    {
        $data = [
            'judul' => 'Base Map',
            'page'  => 'v_base_map'
        ];
        return view('v_template', $data);
    }

   
    public function Report(): string
    {
        $data = [
            'judul' => 'Report',
            'page'  => 'v_report'
        ];
        return view('v_template', $data);
    }

        public function landingPage()
    {
        return view('v_landing');
    }

    public function landingFree()
    {
        return view('v_landing_free');
    }

    public function landingEnterprise()
    {
        return view('v_landing_enterprise');
    }

    public function landingPage2()
    {
        return view('v_landing2', ['show_header' => true]); // v_landing.php adalah halaman baru
    }

    public function VectorPage()
    {
        return view('v_2d', ['show_header' => true]); // v_landing.php adalah halaman baru
    }

    //     public function webmap()
    // {
    //     $data = [
    //         'title'   => 'WebMap Gravport',
    //         'active'  => 'webmap',   // kalau kamu pakai highlight menu
    //     ];

    //     return view('v_webmap', $data);
    //     // ATAU kalau v_template-mu pakai include:
    //     // $data['content'] = 'webmap';
    //     // return view('layouts/v_template', $data);
    // }
}

