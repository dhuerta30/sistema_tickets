<?php

class SqlDialect
{
    protected PDO $pdo;
    protected string $driver;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function quote(string $identifier): string
    {
        $parts = array_map(function (string $part): string {
            $part = trim($part);
            if ($part === '*') {
                return $part;
            }
            switch ($this->driver) {
                case 'mysql':
                    return '`' . str_replace('`', '``', $part) . '`';
                case 'sqlsrv':
                    return '[' . str_replace(']', ']]', $part) . ']';
                case 'pgsql':
                case 'sqlite':
                case 'oci':
                default:
                    return '"' . str_replace('"', '""', $part) . '"';
            }
        }, explode('.', $identifier));

        return implode('.', $parts);
    }

    public function describeTable(string $table, ?string $schema = null): array
    {
        switch ($this->driver) {
            case 'mysql':
                return $this->describeMysql($table);
            case 'pgsql':
                return $this->describePgsql($table, $schema ?? 'public');
            case 'sqlite':
                return $this->describeSqlite($table);
            case 'sqlsrv':
                return $this->describeSqlsrv($table, $schema ?? 'dbo');
            case 'oci':
                return $this->describeOracle($table);
            default:
                throw new Exception("SqlDialect: driver no soportado ({$this->driver})");
        }
    }

    protected function describeMysql(string $table): array
    {
        $stmt = $this->pdo->query("DESCRIBE " . $this->quote($table));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function describePgsql(string $table, string $schema): array
    {
        $sql = "
            SELECT c.column_name AS \"Field\",
                   c.data_type   AS \"Type\",
                   c.is_nullable AS \"Null\",
                   CASE WHEN pk.column_name IS NOT NULL THEN 'PRI' ELSE '' END AS \"Key\",
                   CASE WHEN c.column_default LIKE 'nextval%' THEN 'auto_increment' ELSE '' END AS \"Extra\"
            FROM information_schema.columns c
            LEFT JOIN (
                SELECT kcu.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                  ON tc.constraint_name = kcu.constraint_name
                 AND tc.table_schema   = kcu.table_schema
                WHERE tc.constraint_type = 'PRIMARY KEY'
                  AND tc.table_name = :t1 AND tc.table_schema = :s1
            ) pk ON pk.column_name = c.column_name
            WHERE c.table_name = :t2 AND c.table_schema = :s2
            ORDER BY c.ordinal_position
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':t1' => $table, ':s1' => $schema, ':t2' => $table, ':s2' => $schema]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['Null'] = strtoupper($r['Null']) === 'YES' ? 'YES' : 'NO';
        }
        return $rows;
    }

    protected function describeSqlite(string $table): array
    {
        $safe = str_replace('"', '""', $table);
        $stmt = $this->pdo->query('PRAGMA table_info("' . $safe . '")');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($cols as $c) {
            $isPk = ((int) $c['pk']) > 0;
            $result[] = [
                'Field' => $c['name'],
                'Type'  => $c['type'],
                'Null'  => ((int) $c['notnull']) === 0 ? 'YES' : 'NO',
                'Key'   => $isPk ? 'PRI' : '',
                'Extra' => ($isPk && stripos($c['type'], 'INTEGER') !== false) ? 'auto_increment' : '',
            ];
        }
        return $result;
    }

    protected function describeSqlsrv(string $table, string $schema): array
    {
        $sql = "
            SELECT c.COLUMN_NAME AS [Field],
                   c.DATA_TYPE   AS [Type],
                   c.IS_NULLABLE AS [Null],
                   CASE WHEN pk.COLUMN_NAME IS NOT NULL THEN 'PRI' ELSE '' END AS [Key],
                   CASE WHEN COLUMNPROPERTY(OBJECT_ID(:qualified), c.COLUMN_NAME, 'IsIdentity') = 1
                        THEN 'auto_increment' ELSE '' END AS [Extra]
            FROM INFORMATION_SCHEMA.COLUMNS c
            LEFT JOIN (
                SELECT ku.COLUMN_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE ku
                  ON tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME
                WHERE tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
                  AND tc.TABLE_NAME = :t1 AND tc.TABLE_SCHEMA = :s1
            ) pk ON pk.COLUMN_NAME = c.COLUMN_NAME
            WHERE c.TABLE_NAME = :t2 AND c.TABLE_SCHEMA = :s2
            ORDER BY c.ORDINAL_POSITION
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':qualified' => "{$schema}.{$table}",
            ':t1' => $table, ':s1' => $schema,
            ':t2' => $table, ':s2' => $schema,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['Null'] = strtoupper($r['Null']) === 'YES' ? 'YES' : 'NO';
        }
        return $rows;
    }

    protected function describeOracle(string $table): array
    {
        $tableUpper = strtoupper($table);
        $sql = "
            SELECT c.COLUMN_NAME AS \"Field\",
                   c.DATA_TYPE   AS \"Type\",
                   c.NULLABLE    AS \"Null\",
                   CASE WHEN pk.COLUMN_NAME IS NOT NULL THEN 'PRI' ELSE '' END AS \"Key\",
                   CASE WHEN c.IDENTITY_COLUMN = 'YES' THEN 'auto_increment' ELSE '' END AS \"Extra\"
            FROM ALL_TAB_COLUMNS c
            LEFT JOIN (
                SELECT acc.COLUMN_NAME
                FROM ALL_CONSTRAINTS ac
                JOIN ALL_CONS_COLUMNS acc ON ac.CONSTRAINT_NAME = acc.CONSTRAINT_NAME
                WHERE ac.CONSTRAINT_TYPE = 'P' AND ac.TABLE_NAME = :t1
            ) pk ON pk.COLUMN_NAME = c.COLUMN_NAME
            WHERE c.TABLE_NAME = :t2
            ORDER BY c.COLUMN_ID
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':t1' => $tableUpper, ':t2' => $tableUpper]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['Field'] = strtolower($r['Field']);
            $r['Null']  = $r['Null'] === 'Y' ? 'YES' : 'NO';
        }
        return $rows;
    }

    public function primaryKeyOf(string $table): ?string
    {
        foreach ($this->describeTable($table) as $col) {
            if ($col['Key'] === 'PRI') {
                return $col['Field'];
            }
        }
        return null;
    }

    public function buildLimitOffset(?int $limit, ?int $offset, bool $hasOrderBy = true): string
    {
        if ($limit === null && $offset === null) {
            return '';
        }

        switch ($this->driver) {
            case 'mysql':
            case 'pgsql':
            case 'sqlite':
                $sql = '';
                if ($limit !== null)  $sql .= " LIMIT {$limit}";
                if ($offset !== null) $sql .= " OFFSET {$offset}";
                return $sql;

            case 'sqlsrv':
            case 'oci':
                $off = $offset ?? 0;
                $sql = $hasOrderBy ? '' : ' ORDER BY (SELECT NULL)';
                $sql .= " OFFSET {$off} ROWS";
                if ($limit !== null) {
                    $sql .= " FETCH NEXT {$limit} ROWS ONLY";
                }
                return $sql;

            default:
                return '';
        }
    }

    public function lastInsertId(?string $sequence = null)
    {
        if ($this->driver === 'oci') {
            return $sequence ? $this->pdo->lastInsertId($sequence) : null;
        }
        if ($this->driver === 'pgsql' && $sequence) {
            return $this->pdo->lastInsertId($sequence);
        }
        return $this->pdo->lastInsertId();
    }

    public function relaxGroupByMode(): void
    {
        if ($this->driver === 'mysql') {
            $this->pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        }
    }
}