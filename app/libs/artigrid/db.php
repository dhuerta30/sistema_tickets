<?php

require_once __DIR__ . '/Queryfy.php';

class DB {
    protected static $pdo = null;
    protected static $config = [];
    public static function setConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$pdo = null;
    }

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $cfg = self::$config;
        $dsn = '';
        switch ($cfg['driver']) {
            case 'mysql':
                $port = $cfg['port'] ?? 3306;
                $dsn = "mysql:host={$cfg['host']};port={$port};dbname={$cfg['dbname']};charset={$cfg['charset']}";
                break;
            case 'sqlsrv': // SQL Server
                $port = $cfg['port'] ?? 1433;
                $dsn = "sqlsrv:Server={$cfg['host']},{$port};Database={$cfg['dbname']}";
                break;
            case 'pgsql': // PostgreSQL
                $port = $cfg['port'] ?? 5432;
                $dsn = "pgsql:host={$cfg['host']};port={$port};dbname={$cfg['dbname']};options='--client_encoding={$cfg['charset']}'";
                break;
            case 'oci': // Oracle
                $port = $cfg['port'] ?? 1521;
                $dsn = "oci:dbname=//{$cfg['host']}:{$port}/{$cfg['dbname']};charset={$cfg['charset']}";
                break;
            default:
                throw new Exception("Driver {$cfg['driver']} not supported");
        }
        self::$pdo = new PDO(
            $dsn,
            $cfg['user'],
            $cfg['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return self::$pdo;
    }
}


