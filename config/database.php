<?php

$dbDir = dirname(__DIR__) . '/api/db';

return [
    'default' => 'users',
    'connections' => [
        'users' => [
            'driver' => 'sqlite',
            'database' => $dbDir . '/studiolivecode_users.db',
        ],
        'clients' => [
            'driver' => 'sqlite',
            'database' => $dbDir . '/studiolivecode_clientes.db',
        ],
        'radios' => [
            'driver' => 'sqlite',
            'database' => $dbDir . '/studiolivecode_radios.db',
        ],
        'snoop' => [
            'driver' => 'sqlite',
            'database' => $dbDir . '/studiolivecode_snoop.db',
        ],
    ]
];
