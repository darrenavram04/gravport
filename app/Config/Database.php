<?php

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    /**
     * The default connection group.
     */
    public string $defaultGroup = 'mockup';

    /**
     * MockUp DB: public.geo_datasets
     */
    public array $mockup = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'postgres',
        'password'     => 'yayaya123',
        'database'     => 'MockUp',
        'schema'       => 'public',
        'DBDriver'     => 'Postgre',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
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

    /**
     * gravport DB: testing.datasets
     */
    public array $gravport = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'postgres',
        'password'     => 'yayaya123',
        'database'     => 'gravport',
        'schema'       => 'testing',
        'DBDriver'     => 'Postgre',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
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

    /**
     * Auth DB group (we’ll store auth tables in MockUp, schema auth).
     * If you want auth in gravport instead, change database/schema here.
     */
    public array $auth = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'postgres',
        'password'     => 'yayaya123',
        'database'     => 'MockUp',
        'schema'       => 'auth',
        'DBDriver'     => 'Postgre',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
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
}
