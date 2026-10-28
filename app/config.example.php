<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Listo',
        'url' => 'https://tu-dominio.pe',
        'env' => 'production',
        'debug' => false,
        'timezone' => 'America/Lima',
        'key' => 'CAMBIA_ESTA_CLAVE_POR_UNA_CADENA_ALEATORIA_LARGA',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'usuario_listo',
        'user' => 'usuario_listo',
        'password' => 'CONTRASENA_DE_BASE_DE_DATOS',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'listo_session',
        'lifetime' => 7200,
    ],
];
