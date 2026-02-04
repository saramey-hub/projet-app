<?php
$config = require __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    global $config;

    if ($pdo === null) {
        $pdo = new PDO(
            $config['db']['dsn'],
            $config['db']['user'],
            $config['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}