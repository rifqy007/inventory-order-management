<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    /** Membuat satu koneksi PDO. ERRMODE_EXCEPTION memastikan kegagalan SQL dapat di-rollback. */
    public static function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'inventory';
        $user = getenv('DB_USER') ?: 'inventory';
        $pass = getenv('DB_PASS') ?: 'inventory_secret';
        return new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
