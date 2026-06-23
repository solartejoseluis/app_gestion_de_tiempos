<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $host    = $_ENV['DB_HOST']     ?? 'db';
            $port    = $_ENV['DB_PORT']     ?? '3306';
            $dbname  = $_ENV['DB_NAME']     ?? 'gtd_db';
            $user    = $_ENV['DB_USER']     ?? 'gtd_user';
            $pass    = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }
}
