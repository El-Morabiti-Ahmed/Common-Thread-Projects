<?php
/**
 * Database connection (Singleton).
 * Every class asks for the same PDO object, so the site only
 * opens one connection per request.
 */
class Database
{
    private static ?PDO $connection = null;

    // private constructor: nobody can do "new Database()"
    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            try {
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                throw new RuntimeException(
                    DEBUG ? 'Database connection failed: ' . $e->getMessage()
                          : 'Database connection failed.'
                );
            }
        }

        return self::$connection;
    }
}
