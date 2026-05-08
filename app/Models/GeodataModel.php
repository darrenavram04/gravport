<?php

namespace App\Models;
use CodeIgniter\Model;

class GeodataModel extends Model {

    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    // =============================================
    // PROVINSI LIST
    // =============================================
    public function getProvinsi() {
        $sql = "SELECT id, nama_prov AS name FROM adm_provinsi ORDER BY nama_prov";
        return $this->db->query($sql)->getResult();
    }

    // =============================================
    // DOWNLOAD FAA LEVEL 1 & 2 PER PROVINSI
    // =============================================
    public function getFAAProvinsi($provinsi_id) {
        $sql = "
        SELECT f.*
        FROM faa_lvl2_grid f
        JOIN adm_provinsi p
        ON ST_Intersects(f.geom, p.geom)
        WHERE p.id = $provinsi_id
        ";
        return $this->db->query($sql)->getResult();
    }

    // =============================================
    // DOWNLOAD PER GRID
    // =============================================
    public function getFAAGrid($grid_id) {
        $sql = "SELECT * FROM faa_lvl2_grid WHERE id = $grid_id";
        return $this->db->query($sql)->getResult();
    }

    // =============================================
    // SEARCH BOX
    // =============================================
    public function searchPoint($keyword) {
        $sql = "
        SELECT id, anomaly, ST_AsGeoJSON(geom) AS geom
        FROM faa_lvl1_scatter
        WHERE anomaly::text ILIKE '%$keyword%'
        LIMIT 50
        ";
        return $this->db->query($sql)->getResult();
    }
}
