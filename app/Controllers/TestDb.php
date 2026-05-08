<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;
use CodeIgniter\Database\Exceptions\DatabaseException;

class TestDb extends Controller
{
    public function index()
    {
        try {
            // ini hanya membuat objek, koneksi belum benar-benar dibuka
            $db = Database::connect();

            // koneksi baru benar-benar dicoba di sini (saat query pertama)
            $query = $db->query("SELECT current_database() AS db, current_user AS usr");
            $row   = $query->getRow();

            echo "KONEKSI DB BERHASIL<br>";
            echo "DB: {$row->db}<br>";
            echo "USER: {$row->usr}<br>";
        } catch (DatabaseException $e) {
            // kalau memang gagal, di sini baru kita lihat pesan error aslinya
            echo "KONEKSI DB GAGAL<br>";
            echo nl2br($e->getMessage());
        }
    }
}
