<?php

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $defaultGroup = 'default';

    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'postgres',
        'password'     => 'yayaya123',
        'database'     => 'geoportal',
        'schema'       => 'geoportal,public',
        'DBDriver'     => 'Postgre',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => (ENVIRONMENT !== 'production'),
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 5433,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    // Alias groups — semua mengarah ke schema geoportal
    public array $mockup   = [];
    public array $gravport = [];
    public array $geoportal = [];
    public array $auth     = [];

    public function __construct()
    {
        parent::__construct();
        // Semua alias pakai konfigurasi yang sama
        $this->mockup    = $this->default;
        $this->gravport  = $this->default;
        $this->geoportal = $this->default;
        $this->auth      = $this->default;
    }
}
