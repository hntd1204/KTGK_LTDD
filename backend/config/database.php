<?php
/**
 * Database Connection Configuration
 * Kết nối CSDL MySQL / MariaDB (XAMPP phpMyAdmin)
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'db_khoahoc_tienganh');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                die("Lỗi kết nối cơ sở dữ liệu: " . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
