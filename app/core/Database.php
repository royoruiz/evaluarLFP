<?php

class Database
{
    private static ?\PDO $connection = null;

    public static function getConnection(): \PDO
    {
        if (self::$connection === null) {
            $dsn = 'duckdb:' . DB_PATH;
            try {
                self::$connection = new \PDO($dsn);
                self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::migrate();
            } catch (\PDOException $exception) {
                throw new RuntimeException('No se pudo conectar a DuckDB: ' . $exception->getMessage());
            }
        }

        return self::$connection;
    }

    private static function migrate(): void
    {
        $schema = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            name VARCHAR NOT NULL,
            email VARCHAR NOT NULL UNIQUE,
            password VARCHAR NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        SQL;

        self::$connection->exec($schema);
    }
}
