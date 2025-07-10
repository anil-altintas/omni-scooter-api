<?php
// MySQL bağlantısı
define('DB_HOST','127.0.0.1');
define('DB_PORT',3306);
define('DB_NAME','deezle');
define('DB_USER','root');
define('DB_PASS','sifre');

function getPDO(): \PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $opts = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new \PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}
