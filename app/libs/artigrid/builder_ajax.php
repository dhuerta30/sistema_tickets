<?php

require_once 'ArtiGrid.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = DB::connect();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'list_tables') {
        $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($rows);
        exit;
    }

    if ($action === 'describe') {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['table'] ?? $_GET['table'] ?? '');
        if ($table === '') {
            echo json_encode(['error' => 'Tabla no especificada']);
            exit;
        }

        $stmt = $pdo->query("DESCRIBE `$table`");
        $cols = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sqlType = strtolower(preg_replace('/\(.*/', '', $row['Type']));
            $map = 'text';
            if ($sqlType === 'tinyint') {
                $map = 'checkbox';
            } elseif (in_array($sqlType, ['int', 'integer', 'bigint', 'smallint', 'decimal', 'float', 'double'], true)) {
                $map = 'number';
            } elseif (in_array($sqlType, ['datetime', 'timestamp'], true)) {
                $map = 'datetime';
            } elseif ($sqlType === 'date') {
                $map = 'date';
            } elseif ($sqlType === 'time') {
                $map = 'time';
            } elseif ($sqlType === 'year') {
                $map = 'year';
            } elseif (in_array($sqlType, ['text', 'longtext', 'mediumtext'], true)) {
                $map = 'textarea';
            }

            $name = strtolower($row['Field']);
            if (strpos($name, 'email') !== false)    $map = 'email';
            if (strpos($name, 'password') !== false) $map = 'password';
            if (strpos($name, 'image') !== false || strpos($name, 'imagen') !== false) $map = 'image';

            $cols[] = [
                'name' => $row['Field'],
                'type' => $map,
                'pk'   => $row['Key'] === 'PRI',
            ];
        }
        echo json_encode($cols);
        exit;
    }

    echo json_encode(['error' => 'Acción no válida']);
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
