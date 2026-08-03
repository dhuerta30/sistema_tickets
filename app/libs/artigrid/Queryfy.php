<?php

class Queryfy
{
    protected static array $connections = [];
    protected static ?string $defaultConnection = null;
    protected PDO $pdo;
    protected string $driver;
    protected string $table = '';
    protected array $select = ['*'];
    protected array $where = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function addConnection(string $name, array $config, bool $default = false): void
    {
        self::$connections[$name] = ['config' => $config, 'pdo' => null];
        if ($default || self::$defaultConnection === null) {
            self::$defaultConnection = $name;
        }
    }

    public static function addPdoConnection(string $name, PDO $pdo, bool $default = false): void
    {
        self::$connections[$name] = ['config' => [], 'pdo' => $pdo];
        if ($default || self::$defaultConnection === null) {
            self::$defaultConnection = $name;
        }
    }

    public static function connection(?string $name = null): self
    {
        $name = $name ?? self::$defaultConnection;
        if (!$name || !isset(self::$connections[$name])) {
            throw new InvalidArgumentException(
                "La conexión '{$name}' no está registrada. Usa Queryfy::addConnection() primero."
            );
        }
        if (self::$connections[$name]['pdo'] === null) {
            self::$connections[$name]['pdo'] = self::createPdo(self::$connections[$name]['config']);
        }
        return new self(self::$connections[$name]['pdo']);
    }

    public static function on(?string $name = null): self
    {
        return self::connection($name);
    }

    protected static function createPdo(array $config): PDO
    {
        $driver   = strtolower($config['driver'] ?? '');
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $options  = $config['options'] ?? [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? 3306,
                    $config['database'] ?? '',
                    $config['charset'] ?? 'utf8mb4'
                );
                break;

            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? 5432,
                    $config['database'] ?? ''
                );
                break;

            case 'sqlite':
                $dsn = 'sqlite:' . ($config['database'] ?? ':memory:');
                $username = $password = null;
                break;

            case 'sqlsrv':
            case 'mssql':
            case 'sqlserver':
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%s;Database=%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 1433,
                    $config['database'] ?? ''
                );
                break;

            case 'oracle':
            case 'oci':
                $dsn = sprintf(
                    'oci:dbname=//%s:%s/%s;charset=%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 1521,
                    $config['service_name'] ?? $config['database'] ?? '',
                    $config['charset'] ?? 'AL32UTF8'
                );
                break;

            default:
                throw new InvalidArgumentException("Driver no soportado: {$driver}");
        }

        return new PDO($dsn, $username, $password, $options);
    }

    protected function quoteIdentifier(string $identifier): string
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

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $col, $val, string $op = '='): self
    {
        $this->where[] = [$col, $op, $val];
        return $this;
    }

    public function orderBy(string $col, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$col, strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'];
        return $this;
    }

    public function limit(int $n): self
    {
        $this->limit = $n;
        return $this;
    }

    public function offset(int $n): self
    {
        $this->offset = $n;
        return $this;
    }

    public function get(): array
    {
        $select = implode(', ', array_map([$this, 'quoteIdentifier'], $this->select));
        $sql    = "SELECT {$select} FROM " . $this->quoteIdentifier($this->table);
        $params = [];
        if ($this->where) {
            $w = [];
            foreach ($this->where as $i => [$c, $o, $v]) {
                if (strtoupper($o) === 'IN' && is_array($v)) {
                    $placeholders = [];
                    foreach ($v as $j => $value) {
                        $key = ":w{$i}_{$j}";
                        $placeholders[] = $key;
                        $params[$key] = $value;
                    }
                    $w[] = $this->quoteIdentifier($c)
                        . " IN (" . implode(',', $placeholders) . ")";
                } else {
                    $key = ":w{$i}";
                    $w[] = $this->quoteIdentifier($c) . " {$o} {$key}";
                    $params[$key] = $v;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $w);
        }

        $needsOrderByForPaging = ($this->limit !== null || $this->offset !== null)
            && $this->driver === 'sqlsrv'
            && empty($this->orderBy);
        if ($this->orderBy) {
            $o = array_map(
                fn(array $ob) => $this->quoteIdentifier($ob[0]) . ' ' . $ob[1],
                $this->orderBy
            );
            $sql .= " ORDER BY " . implode(', ', $o);
        } elseif ($needsOrderByForPaging) {
            $sql .= " ORDER BY (SELECT NULL)";
        }
        $sql .= $this->buildLimitOffset();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->reset();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function buildLimitOffset(): string
    {
        if ($this->limit === null && $this->offset === null) {
            return '';
        }
        switch ($this->driver) {
            case 'mysql':
            case 'pgsql':
            case 'sqlite':
                $sql = '';
                if ($this->limit !== null)  $sql .= " LIMIT {$this->limit}";
                if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
                return $sql;
            case 'sqlsrv':
            case 'oci':
                $offset = $this->offset ?? 0;
                $sql = " OFFSET {$offset} ROWS";
                if ($this->limit !== null) {
                    $sql .= " FETCH NEXT {$this->limit} ROWS ONLY";
                }
                return $sql;
            default:
                $sql = '';
                if ($this->limit !== null)  $sql .= " LIMIT {$this->limit}";
                if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
                return $sql;
        }
    }

    public function count(): int
    {
        $sql    = "SELECT COUNT(*) FROM " . $this->quoteIdentifier($this->table);
        $params = [];
        if ($this->where) {
            $w = [];
            foreach ($this->where as $i => [$c, $o, $v]) {
                if (strtoupper($o) === 'IN' && is_array($v)) {
                    $placeholders = [];
                    foreach ($v as $j => $value) {
                        $key = ":w{$i}_{$j}";
                        $placeholders[] = $key;
                        $params[$key] = $value;
                    }
                    $w[] = $this->quoteIdentifier($c)
                        . " IN (" . implode(',', $placeholders) . ")";
                } else {

                    $key = ":w{$i}";
                    $w[] = $this->quoteIdentifier($c) . " {$o} {$key}";
                    $params[$key] = $v;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $w);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->reset();
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data, ?string $sequenceName = null)
    {
        $columns   = array_keys($data);
        $fieldsSql = implode(', ', array_map([$this, 'quoteIdentifier'], $columns));
        $paramsSql = ':' . implode(', :', $columns);
        $sql = "INSERT INTO " . $this->quoteIdentifier($this->table)
             . " ({$fieldsSql}) VALUES ({$paramsSql})";
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_BOOL);
            } elseif (is_null($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":$key", (string) $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $this->reset();
        return $this->lastInsertId($sequenceName);
    }

    protected function lastInsertId(?string $sequenceName = null)
    {
        if ($this->driver === 'oci') {
            if (!$sequenceName) {
                return null;
            }
            return $this->pdo->lastInsertId($sequenceName);
        }
        if ($this->driver === 'pgsql' && $sequenceName) {
            return $this->pdo->lastInsertId($sequenceName);
        }
        return $this->pdo->lastInsertId();
    }

    protected function reset(): void
    {
        $this->select  = ['*'];
        $this->where   = [];
        $this->orderBy = [];
        $this->limit   = null;
        $this->offset  = null;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}