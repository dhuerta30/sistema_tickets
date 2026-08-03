<?php
session_name('ARTIGRID_ADMIN');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'ArtiGrid.php';
require_once 'db.php';
$raw = file_get_contents("php://input");
$json = json_decode($raw, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
    $_POST = $json;
}
$artigrid_array_skip = [
    'where', 'joins', 'groupBy', 'columnFilters', 'subselects',
    'calculatedFields', 'advancedFilters', 'jsonColumns', 'jsonRows',
    'ids', 'bulk_ids', 'calendar', 'config', 'form_values',
    'nestedGrids', 'requiredFields'
];
foreach ($_POST as $k => $v) {
    if (is_array($v) && !in_array($k, $artigrid_array_skip, true)) {
        $_POST[$k] = implode(',', array_map('strval', array_values($v)));
    }
}
header('Content-Type: application/json');
$db = DB::connect();
function artigrid_load_grid_config(string $gridId): array
{
    $gridId = trim($gridId);
    if ($gridId === '') return [];
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $gridId)) return [];
    $candidates = [$gridId];
    if (preg_match('/^(.+)_[a-f0-9]{8}$/', $gridId, $m)) {
        $candidates[] = $m[1];
    }
    foreach ($candidates as $candidate) {
        $path = __DIR__ . '/callbacks/' . $candidate . '.php';
        if (is_file($path)) {
            $cfg = require $path;
            return is_array($cfg) ? $cfg : [];
        }
    }
    return [];
}
function artigrid_update_handler($db, $table, $gridId, $postData) {
    try {
        $config = $_SESSION['artigrid'][$gridId]['config'] ?? [];
        $data = $postData;
        artigrid_handle_uploads($data);
        unset(
            $data['action'],
            $data['table'],
            $data['mode'],
            $data['query'],
            $data['grid_id'],
            $data['callbacks'],
            $data['csrf_token']
        );
        if (!$data) {
            return ['success' => false, 'error' => 'Datos vacíos'];
        }
        $colsStmt = $db->query("DESCRIBE `$table`");
        $columnsInfo = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
        $tableFields = [];
        $primaryKey = null;
        foreach ($columnsInfo as $col) {
            $tableFields[] = $col['Field'];

            if ($col['Key'] === 'PRI') {
                $primaryKey = $col['Field'];
            }
        }
        if (!$primaryKey) {
            $primaryKey = $config['primaryKey'] ?? 'id';
        }
        $id = $data['id'] ?? $data[$primaryKey] ?? null;
        unset($data[$primaryKey], $data['id']);
        if (!$id) {
            return ['success' => false, 'error' => 'Invalid ID'];
        }
        $requiredFields = $config['requiredFields'] ?? [];
        if (is_string($requiredFields)) {
            $requiredFields = array_filter(array_map('trim', explode(',', $requiredFields)));
        }
        foreach ($requiredFields as $field) {
            if (array_key_exists($field, $data)) {
                if (trim((string)$data[$field]) === '') {
                    echo json_encode([
                        'success' => false,
                        'error'   => "The field '$field' It is mandatory",
                        'field'   => $field
                    ]);
                    exit;
                }
            }
        }
        foreach ($data as $k => $v) {
            if ($v === '' && !in_array($k, $requiredFields)) {
                $data[$k] = null;
            }
        }
        $data = artigrid_run_callbacks(
            'beforeUpdate',
            $config,
            $data,
            [
                'table' => $table,
                'id'    => $id,
                'pk'    => $primaryKey
            ]
        );
        $set = implode(', ', array_map(function ($k) {
            $col = str_replace('`', '', $k);
            return "`{$col}` = :{$col}";
        }, array_keys($data)));
        $sql = "UPDATE `$table`
                SET $set
                WHERE `$primaryKey` = :__pk";
        $stmt = $db->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->bindValue(':__pk', $id);
        $stmt->execute();
        $updated = $stmt->rowCount() > 0;
        artigrid_run_callbacks(
            'afterUpdate',
            $config,
            [
                'updated' => $updated,
                'id'      => $id
            ],
            $data
        );
        return [
            'success' => true,
            'updated' => $updated,
            'message' => $updated ? 'Updated' : 'No changes'
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'error'   => $e->getMessage()
        ];
    }
}
function artigrid_run_callbacks(string $hook, array $config, $payload, array $dataForAfter = [])
{
    if (empty($config[$hook]) || !is_array($config[$hook])) return $payload;
    foreach ($config[$hook] as $cb) {
        if (!is_array($cb)) {
            continue;
        }
        $fn   = $cb['callback'] ?? $cb['fn'] ?? '';
        $file = $cb['file'] ?? '';
        if (!is_string($fn) || trim($fn) === '') {
            continue;
        }
        if (is_string($file) && trim($file) !== '') {
            $filePath = $file;
            $isAbs = preg_match('/^([a-zA-Z]:\\\\|\\/)/', $filePath) === 1;
            if (!$isAbs) $filePath = __DIR__ . '/' . ltrim($filePath, '/');

            if (is_file($filePath)) {
                require_once $filePath;
            } else {
                throw new Exception("file to callback not found: {$file}");
            }
        }
        if (!is_callable($fn)) {
            throw new Exception("Callback {$hook} invalid: {$fn}");
        }
        $result = $fn($payload, $dataForAfter);
        if (is_array($result) && isset($result['success']) && $result['success'] === false) {
            return $result;
        }
        if ($hook !== 'afterInsert' && !is_array($result)) {
            throw new Exception("Callback {$hook} debe retornar array");
        }
        if (is_array($result)) {
            $payload = $result['data'] ?? $result;
        }
    }
    return $payload;
}
function artigrid_handle_uploads(array &$data, bool $move = false): array
{
    if (empty($_FILES)) return [];
    $uploadDir = __DIR__ . '/uploads/';
    $prepared = [];
    if ($move && !is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES as $field => $file) {
        if (is_array($file['name'])) {
            $filesSaved = [];
            foreach ($file['name'] as $i => $name) {
                if ($file['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newName = uniqid('file_', true) . '.' . $ext;
                $prepared[] = [
                    'tmp'  => $file['tmp_name'][$i],
                    'name' => $newName
                ];
                if ($move) {
                    move_uploaded_file(
                        $file['tmp_name'][$i],
                        $uploadDir . $newName
                    );
                }
                $filesSaved[] = $newName;
            }
            if ($filesSaved) {
                $data[$field] = json_encode($filesSaved);
            }
        } else {
            if ($file['error'] !== UPLOAD_ERR_OK) continue;
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = uniqid('file_', true) . '.' . $ext;
            $prepared[] = [
                'tmp'  => $file['tmp_name'],
                'name' => $newName
            ];
            if ($move) {
                move_uploaded_file(
                    $file['tmp_name'],
                    $uploadDir . $newName
                );
            }
            $data[$field] = $newName;
        }
    }
    return $prepared;
}
$table          = $_POST['table'] ?? '';
$mode           = $_POST['mode'] ?? 'table';
$query          = $_POST['query'] ?? '';
$action         = $_POST['action'] ?? 'list';
$page           = max(1, (int)($_POST['page'] ?? 1));
$perPageRaw     = $_POST['perPage'] ?? 10;
$limit          = $perPageRaw === 'all' ? 0 : (int)$perPageRaw;
$offset         = $limit ? (($page - 1) * $limit) : 0;
$search         = $_POST['search'] ?? '';
$searchCol      = $_POST['searchCol'] ?? '';
$sortColumn     = $_POST['sortColumn'] ?? '';
$sortOrder      = $_POST['sortOrder'] ?? '';
$groupBy        = json_decode($_POST['groupBy'] ?? '{}', true);
$columnFilters  = $_POST['columnFilters'] ?? [];
$whereConditions = json_decode($_POST['where'] ?? '[]', true) ?: [];
$gridId         = $_POST['grid_id'] ?? null;
$requiredFields = [];
if ($searchCol === 'all' || $searchCol === '*' || $searchCol === '') {
    $searchCol = '';
}
if ($gridId && !isset($_SESSION['artigrid'][$gridId])) {
    $_SESSION['artigrid'][$gridId] = [
        'created_at' => time(),
        'last_used'  => time(),
        'config'     => []
    ];
}
$primaryKey = null;
if ($table) {
    $colsStmt = $db->query("DESCRIBE `$table`");
    $columnsInfo = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columnsInfo as $col) {
        if (strpos($col['Extra'], 'auto_increment') !== false) {
            $primaryKey = $col['Field'];
            break;
        }
    }
    if (!$primaryKey && !empty($columnsInfo)) {
        $primaryKey = $columnsInfo[0]['Field'];
    }
    if ($gridId) {
        if (!isset($_SESSION['artigrid'][$gridId]['config']) || !is_array($_SESSION['artigrid'][$gridId]['config'])) {
            $_SESSION['artigrid'][$gridId]['config'] = [];
        }
        $_SESSION['artigrid'][$gridId]['config'] = array_merge(
            $_SESSION['artigrid'][$gridId]['config'],
            [
                'primaryKey' => $primaryKey
            ]
        );
    }
}
$needsGrid = in_array($action, ['insert_form', 'edit_form'], true);
if ($needsGrid) {
    if (!$gridId || empty($_SESSION['artigrid'][$gridId])) {
        echo json_encode([
            'success' => false,
            'error'   => 'Grid not found',
            'debug'   => [
                'grid_id' => $gridId,
                'session_keys' => array_keys($_SESSION['artigrid'])
            ]
        ]);
        exit;
    }
    $_SESSION['artigrid'][$gridId]['last_used'] = time();
    $config = $_SESSION['artigrid'][$gridId]['config'];
    $grid = new ArtiGrid($db);
    $grid->importConfig($config);
}
$protectedActions = ['insert', 'update', 'delete', 'delete-multiple', 'ckeditor_upload'];
if (in_array($action, $protectedActions, true)) {
        $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (!$clientToken || $clientToken !== ($_SESSION['artigrid_csrf'] ?? '')) {
        $_SESSION['artigrid_csrf'] = bin2hex(random_bytes(32));
        echo json_encode([
            'success'    => false,
            'error'      => 'token_expired',
            'message'    => 'Session expired, trying again...',
            'new_token'  => $_SESSION['artigrid_csrf']
        ]);
        exit;
    }
}
switch ($action) {
    case 'insert_form':
    if (!$table) {
        echo json_encode([
            'success' => false,
            'message' => 'Table not provided'
        ]);
        exit;
    }
    $config = json_decode($_POST['config'] ?? '{}', true);
    if (!is_array($config) || empty($config)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid configuration'
        ]);
        exit;
    }
    if (!empty($config['where'])) {
        foreach ($config['where'] as $condition) {
            $field = $condition['field'] ?? null;
            $value = $condition['value'] ?? null;
            if ($field && $value !== null && !is_array($value)) {
                $config['formFieldValues'][$field] = $value;
            }
        }
    }
    if ($gridId) {
        $_SESSION['artigrid'][$gridId]['config'] = array_merge(
            $_SESSION['artigrid'][$gridId]['config'] ?? [],
            $config
        );
    }
    try {
        $grid->table($table);
        $grid->importConfig($config);
        $html = $grid->render('insert');
        echo json_encode([
            'success' => true,
            'html'    => $html
        ]);
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;

    case 'refresh_token':
    $_SESSION['artigrid_csrf'] = bin2hex(random_bytes(32));
    echo json_encode([
        'success' => true,
        'token'   => $_SESSION['artigrid_csrf']
    ]);
    exit;

    case 'view_form':
        $id = $_POST['id'] ?? '';
        if (!$table || !$id || !$primaryKey) {
            echo json_encode(null);
            exit;
        }
        $config = json_decode($_POST['config'] ?? '{}', true);
        $grid = new ArtiGrid();
        $grid->table($table);
        $grid->importConfig($config);
        if (!empty($config['nestedGrids'])) {
            $grid->reconstructNestedTablesFromConfig($config['nestedGrids']);
        }
        $html = $grid->render('view', $id);
        $stmt = $db->prepare("SELECT * FROM `$table` WHERE `$primaryKey` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'html' => $html,
            'data' => $row ?: []
        ]);
    exit;

    case 'edit_form':
        $id = $_POST['id'] ?? '';
        if (!$table || !$id || !$primaryKey) {
            echo json_encode(null);
            exit;
        }
        $config = json_decode($_POST['config'] ?? '{}', true);
        $grid->table($table);
        $grid->importConfig($config);
        if (!empty($config['nestedGrids'])) {
            $grid->reconstructNestedTablesFromConfig($config['nestedGrids']);
        }
        $html = $grid->render('edit', $id);
        $stmt = $db->prepare("SELECT * FROM `$table` WHERE `$primaryKey` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'html' => $html,
            'data' => $row ?: []
        ]);
        exit;

    case 'bulk_edit_form':
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (!$table || empty($ids) || !$primaryKey) {
            echo json_encode(null);
            exit;
        }
        $config = json_decode($_POST['config'] ?? '{}', true);
        $grid = new ArtiGrid();
        $grid->table($table);
        $grid->importConfig($config);
        $id = $ids[0];
        $html = $grid->render('edit', $id);
        $idsJson = htmlspecialchars(json_encode($ids), ENT_QUOTES);
        $hiddenInput = "<input type='hidden' name='bulk_ids' value='{$idsJson}'>";
        $html = str_replace('</form>', $hiddenInput . '</form>', $html);
        $stmt = $db->prepare("SELECT * FROM `$table` WHERE `$primaryKey` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'html' => $html,
            'data' => $row ?: [],
            'bulk' => true
        ]);
    exit;

    case 'clone_form':
        $grid = new ArtiGrid($db);
        $id = $_POST['id'] ?? '';
        if (!$table || !$id || !$primaryKey) {
            echo json_encode(null);
            exit;
        }
        $config = json_decode($_POST['config'] ?? '{}', true);
        $grid->table($table);
        $grid->importConfig($config);
        $html = $grid->render('insert');
        $stmt = $db->prepare("SELECT * FROM `$table` WHERE `$primaryKey` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            unset($row[$primaryKey]);
        }
        echo json_encode([
            'html' => $html,
            'data' => $row ?: []
        ]);
    exit;

    case 'select_cascade':
    $dep = htmlspecialchars($f['depends_on']);
    echo '<select class="form-select afg-input afg-cascade" 
            name="' . $name . '" 
            data-operator="="
            data-depends-on="' . $dep . '"
            data-cascade-table="' . htmlspecialchars($f['table'] ?? '') . '"
            data-cascade-value="' . htmlspecialchars($f['value'] ?? 'id') . '"
            data-cascade-label="' . htmlspecialchars($f['label_col'] ?? 'name') . '"
            data-cascade-field="' . htmlspecialchars($f['depends_field'] ?? '') . '"
            disabled>
        <option value="">-- Select ' . $label . ' --</option>
    </select>';
    break;

    case 'ckeditor_upload':
        if (empty($_FILES['upload'])) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => 'No file received']]);
            exit;
        }

        $file = $_FILES['upload'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => 'Upload error']]);
            exit;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => 'Invalid file type']]);
            exit;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => 'File too large']]);
            exit;
        }

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? 'bin';

        $uploadDir = __DIR__ . '/uploads/ckeditor/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName    = uniqid('cke_', true) . '.' . $ext;
        $destination = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => ['message' => 'Could not save file']]);
            exit;
        }

        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $url = $scriptDir . '/uploads/ckeditor/' . $safeName;

        echo json_encode(['url' => $url]);
    exit;

    case 'list':
        $advFilters = json_decode($_POST['advancedFilters'] ?? '[]', true) ?: [];
        $jsonColumns = json_decode($_POST['jsonColumns'] ?? '[]', true);
        $jsonRows    = json_decode($_POST['jsonRows'] ?? '[]', true);
        $pageRaw    = $_POST['page'] ?? 1;
        $perPageRaw = $_POST['perPage'] ?? 5;
        $page       = max(1, (int)$pageRaw);
        if (!empty($jsonRows)) {
            $columns = $jsonColumns ?: array_keys($jsonRows[0] ?? []);
            $total   = count($jsonRows);
            if ($search !== '') {
                $jsonRows = array_filter($jsonRows, function ($row) use ($search, $searchCol, $columns) {
                    if ($searchCol && isset($row[$searchCol])) {
                        return stripos((string)$row[$searchCol], $search) !== false;
                    }
                    foreach ($columns as $c) {
                        if (
                            isset($row[$c]) &&
                            stripos((string)$row[$c], $search) !== false
                        ) {
                            return true;
                        }
                    }
                    return false;
                });
                $jsonRows = array_values($jsonRows);
            }
            if (!empty($columnFilters)) {
                foreach ($columnFilters as $c => $v) {
                    if ($v === '' || !in_array($c, $columns, true)) {
                        continue;
                    }
                    $jsonRows = array_filter($jsonRows, function ($row) use ($c, $v) {
                        if (!isset($row[$c])) {
                            return false;
                        }
                        return strpos(
                            strtolower((string)$row[$c]),
                            strtolower($v)
                        ) !== false;
                    });
                    $jsonRows = array_values($jsonRows);
                }
            }
            $total = count($jsonRows);
            if ($sortColumn && in_array($sortColumn, $columns, true)) {
                usort($jsonRows, function($a, $b) use ($sortColumn, $sortOrder) {
                    $valA = $a[$sortColumn] ?? '';
                    $valB = $b[$sortColumn] ?? '';
                    if (is_numeric($valA) && is_numeric($valB)) {
                        $cmp = $valA - $valB;
                    } else {
                        $cmp = strnatcmp($valA, $valB);
                    }
                    return strtoupper($sortOrder) === 'DESC' ? -$cmp : $cmp;
                });
            }

            $summaryResult = [];
            $summaryRowCfg = json_decode($_POST['summaryRow'] ?? '{}', true) ?: [];
            if (!empty($summaryRowCfg) && is_array($summaryRowCfg)) {
                foreach ($summaryRowCfg as $col => $ops) {
                    if (!in_array($col, $columns, true)) continue;
                    $nums = array_map('floatval', array_filter(
                        array_column($jsonRows, $col),
                        fn($v) => is_numeric($v)
                    ));
                    foreach ((array)$ops as $op) {
                        $op = strtolower(trim($op));
                        $alias = "sum_{$op}_{$col}";
                        if (empty($nums) && $op !== 'count') { $summaryResult[$alias] = null; continue; }
                        switch ($op) {
                            case 'sum':   $summaryResult[$alias] = array_sum($nums); break;
                            case 'avg':   $summaryResult[$alias] = count($nums) ? array_sum($nums) / count($nums) : null; break;
                            case 'min':   $summaryResult[$alias] = count($nums) ? min($nums) : null; break;
                            case 'max':   $summaryResult[$alias] = count($nums) ? max($nums) : null; break;
                            case 'count': $summaryResult[$alias] = count($nums); break;
                        }
                    }
                }
            }

            if ($limit) {
                $rows       = array_slice($jsonRows, $offset, $limit);
                $totalPages = ceil($total / $limit);
            } else {
                $rows       = $jsonRows;
                $totalPages = 1;
                $limit      = 'all';
            }
            $cbConfig = artigrid_load_grid_config((string)$gridId);
            if (!is_array($rows)) {
                $rows = [];
            }
            foreach ($rows as &$row) {
                $row = artigrid_run_callbacks('beforeRenderRow', $cbConfig, $row);
            }
            unset($row);
            echo json_encode([
                'data'       => $rows,
                'columns'    => $columns,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $limit,
                'totalPages' => $totalPages,
                'summary'    => $summaryResult
            ]);
            exit;
        }
        if ($mode === 'query') {
            $baseTable  = $_POST['baseTable'] ?? null;
            $primaryKey = $_POST['primaryKey'] ?? $primaryKey;
            if (!$query) {
                echo json_encode(['data'=>[], 'total'=>0, 'page'=>1, 'perPage'=>0, 'totalPages'=>1]);
                exit;
            }
            $metaStmt = $db->query("SELECT * FROM ($query) AS t LIMIT 1");
            $columns  = [];
            for ($i = 0; $i < $metaStmt->columnCount(); $i++) {
                $meta      = $metaStmt->getColumnMeta($i);
                $columns[] = $meta['name'];
            }
            $whereParts = [];
            $params     = [];
            if ($search !== '') {
                if ($searchCol && in_array($searchCol, $columns, true)) {
                    $whereParts[]    = "`$searchCol` LIKE :search";
                    $params[':search'] = "%$search%";
                } else {
                    $orParts = [];
                    foreach ($columns as $c) {
                        $key           = ":s_$c";
                        $orParts[]     = "`$c` LIKE $key";
                        $params[$key]  = "%$search%";
                    }
                    if ($orParts) {
                        $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
                    }
                }
            }
            foreach ($columnFilters as $c => $v) {
                if ($v !== '' && in_array($c, $columns, true)) {
                    $param          = ":f_$c";
                    $safeCol        = str_replace('`', '', $c);
                    $whereParts[]   = "`$safeCol` LIKE $param";
                    $params[$param] = "%$v%";
                }
            }
            $where   = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
            $orderBy = '';
            if ($sortColumn && in_array($sortColumn, $columns, true)) {
                $dir     = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
                $orderBy = "ORDER BY `$sortColumn` $dir";
            }
            $countStmt = $db->prepare("SELECT COUNT(*) FROM ($query) AS t $where");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $summaryResult = [];
            $summaryRowCfg = json_decode($_POST['summaryRow'] ?? '{}', true) ?: [];
            if (!empty($summaryRowCfg) && is_array($summaryRowCfg)) {
                $aggMap = ['sum' => 'SUM', 'avg' => 'AVG', 'min' => 'MIN', 'max' => 'MAX', 'count' => 'COUNT'];
                $aggSelects = [];
                foreach ($summaryRowCfg as $col => $ops) {
                    if (!in_array($col, $columns, true)) continue;
                    $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                    foreach ((array)$ops as $op) {
                        $op = strtolower(trim($op));
                        if (!isset($aggMap[$op])) continue;
                        $aggSelects[] = "{$aggMap[$op]}(`$cleanCol`) AS `sum_{$op}_{$cleanCol}`";
                    }
                }
                if ($aggSelects) {
                    $sumSql = "SELECT " . implode(', ', $aggSelects) . " FROM ($query) AS t $where";
                    $sumStmt = $db->prepare($sumSql);
                    foreach ($params as $k => $v) $sumStmt->bindValue($k, $v);
                    $sumStmt->execute();
                    $summaryResult = $sumStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                }
            }

            $sql   = "SELECT * FROM ($query) AS t $where $orderBy";
            if ($limit) $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            if ($limit) {
                $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $cbConfig = artigrid_load_grid_config((string)$gridId);
            if (!is_array($rows)) {
                $rows = [];
            }
            foreach ($rows as &$row) {
                $row = artigrid_run_callbacks('beforeRenderRow', $cbConfig, $row);
            }
            echo json_encode([
                'data'       => $rows,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $limit ?: 'all',
                'totalPages' => $limit ? ceil($total / $limit) : 1,
                'summary'    => $summaryResult
            ]);
            exit;
        }
        if (!$table) {
            echo json_encode(['data'=>[], 'total'=>0, 'page'=>1, 'perPage'=>0, 'totalPages'=>1]);
            exit;
        }
        $db->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        $joins         = json_decode($_POST['joins'] ?? '[]', true) ?: [];
        if (!is_array($joins)) {
            $joins = [];
        }
        $selectColumns = [];
        $subselects       = json_decode($_POST['subselects'] ?? '{}', true) ?: [];
        $calculatedFields = json_decode($_POST['calculatedFields'] ?? '{}', true) ?: [];
        $virtualKeys      = array_merge(array_keys($subselects), array_keys($calculatedFields));
        $phpFilters = [];
        $sqlFilters = [];
        foreach ($columnFilters as $c => $v) {
            if ($v === '') continue;
            if (in_array($c, $virtualKeys, true)) {
                $phpFilters[$c] = $v;
            } else {
                $sqlFilters[$c] = $v;
            }
        }
        if ($selectColumns) {
            $select = implode(', ', $selectColumns);
        } else {
            $select = "`$table`.*";
            if (!empty($joins)) {
                foreach ($joins as $j) {
                    $joinTable = $j['table'];
                    $colsStmt  = $db->query("DESCRIBE `$joinTable`");
                    $cols      = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($cols as $col) {
                        $colName = $col['Field'];
                        $alias   = $joinTable . '_' . $colName;
                        $select .= ", `$joinTable`.`$colName` AS `$alias`";
                    }
                }
            }
        }
        $joinSql = '';
        foreach ($joins as $j) {
            $type    = strtoupper($j['type'] ?? 'INNER');
            $joinSql .= " $type JOIN {$j['table']} ON {$j['on']} ";
        }
        if ($selectColumns) {
            $columns = array_map(function ($c) {
                if (!is_string($c)) return $c;
                $alias = preg_replace('/^.*\s+AS\s+/i', '', $c);
                return trim($alias, " `\"");
            }, $selectColumns);
        } else {
            $colsStmt = $db->query("DESCRIBE `$table`");
            $columns  = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $whereParts      = [];
        $params          = [];
        $whereConditions = json_decode($_POST['where'] ?? '[]', true) ?: [];
        if (is_array($whereConditions)) {
            foreach ($whereConditions as $i => $w) {
                if (isset($w['field'])) {
                    $field = $w['field'];
                    $op    = strtoupper($w['operator'] ?? '=');
                    $value = $w['value'] ?? null;
                    $logic = strtoupper($w['boolean'] ?? 'AND');
                } else {
                    $field = $w[0] ?? null;
                    $op    = strtoupper($w[1] ?? '=');
                    $value = $w[2] ?? null;
                    $logic = 'AND';
                }
                if (!$field) continue;
                $paramBase = "w_{$field}_{$i}";
                if ($op === 'IN' && is_array($value)) {
                    $placeholders = [];
                    foreach ($value as $j => $v) {
                        $key            = ":{$paramBase}_$j";
                        $placeholders[] = $key;
                        $params[$key]   = $v;
                    }
                    if ($placeholders) {
                        $whereParts[] = "$logic `$field` IN (" . implode(',', $placeholders) . ")";
                    }
                } elseif ($op === 'BETWEEN' && is_array($value) && count($value) === 2) {
                    $keyStart             = ":{$paramBase}_start";
                    $keyEnd               = ":{$paramBase}_end";
                    $whereParts[]         = "$logic `$table`.`$field` BETWEEN $keyStart AND $keyEnd";
                    $params[$keyStart]    = $value[0];
                    $params[$keyEnd]      = $value[1];
                } else {
                    $key          = ":$paramBase";
                    $whereParts[] = "$logic `$table`.`$field` $op $key";
                    $params[$key] = $value;
                }
            }
        }
        if ($search !== '') {
            if ($searchCol && in_array($searchCol, $columns, true)) {
                $whereParts[]      = "`$table`.`$searchCol` LIKE :search";
                $params[':search'] = "%$search%";
            } else {
                $orParts = [];
                foreach ($columns as $c) {
                    $key          = ":s_$c";
                    $orParts[]    = "`$table`.`$c` LIKE $key";
                    $params[$key] = "%$search%";
                }
                if ($orParts) $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
            }
        }
        foreach ($sqlFilters as $c => $v) {
            if ($v !== '' && in_array($c, $columns, true)) {
                $cleanCol    = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
                $cleanTable  = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $columnExpr  = '`' . $cleanTable . '`.`' . $cleanCol . '`';
                $whereParts[] = $columnExpr . ' LIKE :f_' . $cleanCol;
                $params[":f_$cleanCol"] = "%$v%";
            }
        }
        $rangePresence = [];
        foreach ($advFilters as $af) {
            $f = $af['field'] ?? '';
            if (preg_match('/^(.*)_(from|to)$/', $f, $m)) {
                $rangePresence[$m[1]][$m[2]] = (($af['value'] ?? '') !== '');
            }
        }
        foreach ($advFilters as &$af) {
            $f = $af['field'] ?? '';
            if (preg_match('/^(.*)_(from|to)$/', $f, $m)) {
                $base = $m[1];
                $role = $m[2];
                $hasFrom = !empty($rangePresence[$base]['from']);
                $hasTo   = !empty($rangePresence[$base]['to']);
                if ($role === 'from' && $hasFrom && !$hasTo) {
                    $af['operator'] = '=';
                }
                if ($role === 'to' && $hasTo && !$hasFrom) {
                    $af['operator'] = '=';
                }
            }
        }
        unset($af);

        foreach ($advFilters as $af) {
            $afField    = $af['field']    ?? null;
            $afOperator = strtoupper(trim($af['operator'] ?? '='));
            $afValue    = $af['value']    ?? null;
            if (!$afField || $afValue === null || $afValue === '') continue;
            $afField = preg_replace('/[^a-zA-Z0-9_]/', '', $afField);
            if (!$afField) continue;

            $rangeRole = null;
            $realField = $afField;
            if (!in_array($realField, $columns, true)) {
                if (preg_match('/^(.*)_(from|to)$/', $afField, $m) && in_array($m[1], $columns, true)) {
                    $realField = $m[1];
                    $rangeRole = $m[2];
                }
            }
            if (!in_array($realField, $columns, true)) continue;

            $afExpr   = "`{$table}`.`{$realField}`";
            $paramKey = ':af_' . $afField . '_' . count($whereParts);

            if ($rangeRole === 'to'
                && $afOperator === '<='
                && is_string($afValue)
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', $afValue)) {
                $afValue = $afValue . ' 23:59:59';
            }

            if ($afOperator === 'IN' && is_array($afValue)) {
                $placeholders = [];
                foreach ($afValue as $idx => $v) {
                    $k = $paramKey . '_' . $idx;
                    $placeholders[] = $k;
                    $params[$k]     = $v;
                }
                if ($placeholders)
                    $whereParts[] = "AND $afExpr IN (" . implode(',', $placeholders) . ")";
            } elseif ($afOperator === 'LIKE') {
                $whereParts[]      = "AND $afExpr LIKE $paramKey";
                $params[$paramKey] = '%' . $afValue . '%';
            } else {
                $allowed = ['=','!=','<>','>','<','>=','<='];
                if (!in_array($afOperator, $allowed, true)) continue;
                $whereParts[]      = "AND $afExpr $afOperator $paramKey";
                $params[$paramKey] = $afValue;
            }
        }
        if ($whereParts) {
            $whereParts[0] = preg_replace('/^(AND|OR)\s+/i', '', $whereParts[0]);
            foreach ($whereParts as $i => &$part) {
                if ($i === 0) continue;
                if (!preg_match('/^(AND|OR)\s+/i', $part)) {
                    $part = 'AND ' . $part;
                }
            }
            unset($part);
            $where = ' WHERE ' . implode(' ', $whereParts);
        } else {
            $where = '';
        }
        $groupBySql = '';
        $selectSql  = $select;
        if (!empty($groupBy)) {
            $validGroupBy = array_values(
                array_filter($groupBy ?? [], function ($c) use ($columns) {
                    return is_string($c) && $c !== '' && in_array($c, $columns, true);
                })
            );
            if ($validGroupBy) {
                $groupBySql = ' GROUP BY ' . implode(', ', array_map(function ($c) {
                    return '`' . str_replace('`', '', $c) . '`';
                }, $validGroupBy));
                if (empty($selectColumns)) {
                    $aggCols   = array_diff($columns, $validGroupBy);
                    $aggSelect = [];
                    foreach ($aggCols as $col) {
                        $aggSelect[] = "MAX(`$col`) AS `$col`";
                    }
                    $selectSql = implode(', ', array_map(function ($c) {
                        return '`' . str_replace('`', '', $c) . '`';
                    }, $validGroupBy));
                    if ($aggSelect) {
                        $selectSql .= ', ' . implode(', ', $aggSelect);
                    }
                }
            }
        }
        $orderBy = '';
        if ($sortColumn && in_array($sortColumn, $columns, true)) {
            $dir     = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
            $orderBy = " ORDER BY `$table`.`$sortColumn` $dir";
        }
        $applyVirtual = function (array &$rows) use ($db, $subselects, $calculatedFields): void {
            foreach ($rows as &$row) {
                foreach ($subselects as $alias => $subSql) {
                    $parsedSql = preg_replace_callback('/\{(.*?)\}/', function ($m) use ($row) {
                        $key = $m[1];
                        foreach ($row as $rk => $rv) {
                            if (strcasecmp($rk, $key) === 0) {
                                return is_numeric($rv) ? $rv : 0;
                            }
                        }
                        return 0;
                    }, $subSql);
                    try {
                        $subStmt     = $db->query($parsedSql);
                        $value       = $subStmt->fetchColumn();
                        $row[$alias] = ($value !== null) ? (float)$value : 0;
                    } catch (Throwable $e) {
                        $row[$alias] = 0;
                    }
                }
                foreach ($calculatedFields as $alias => $expr) {
                    $parsed = $expr;
                    foreach ($row as $key => $value) {
                        $numVal = is_numeric($value) ? (float)$value : 0;
                        $parsed = str_replace('{' . $key . '}', $numVal, $parsed);
                    }
                    $safe = preg_replace('/[^0-9+\-*\/().\s]/', '', $parsed);
                    try {
                        $row[$alias] = $safe !== '' ? eval("return ($safe);") : 0;
                    } catch (Throwable $e) {
                        $row[$alias] = 0;
                    }
                }
            }
            unset($row);
        };

        $summaryResult = [];
        $summaryRowCfg = json_decode($_POST['summaryRow'] ?? '{}', true) ?: [];
        if (!empty($summaryRowCfg) && is_array($summaryRowCfg)) {
            $aggMap = ['sum' => 'SUM', 'avg' => 'AVG', 'min' => 'MIN', 'max' => 'MAX', 'count' => 'COUNT'];
            $aggSelects = [];
            foreach ($summaryRowCfg as $col => $ops) {
                if (!in_array($col, $columns, true)) continue;
                if (in_array($col, $virtualKeys, true)) continue; // solo columnas reales
                $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                foreach ((array)$ops as $op) {
                    $op = strtolower(trim($op));
                    if (!isset($aggMap[$op])) continue;
                    $aggSelects[] = "{$aggMap[$op]}(`$table`.`$cleanCol`) AS `sum_{$op}_{$cleanCol}`";
                }
            }
            if ($aggSelects) {
                $sumSql = "SELECT " . implode(', ', $aggSelects) . " FROM `$table` $joinSql $where";
                $sumStmt = $db->prepare($sumSql);
                foreach ($params as $k => $v) $sumStmt->bindValue($k, $v);
                $sumStmt->execute();
                $summaryResult = $sumStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }
        }

        if (!empty($phpFilters)) {
            $sqlAll  = "SELECT $select FROM `$table` $joinSql $where $groupBySql $orderBy";
            $stmtAll = $db->prepare($sqlAll);
            foreach ($params as $k => $v) $stmtAll->bindValue($k, $v);
            $stmtAll->execute();
            $allRows = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
            $applyVirtual($allRows);
            foreach ($phpFilters as $col => $val) {
                $allRows = array_filter($allRows, fn($row) =>
                    stripos((string)($row[$col] ?? ''), (string)$val) !== false
                );
                $allRows = array_values($allRows);
            }
            $total      = count($allRows);
            $totalPages = $limit ? (int)ceil($total / $limit) : 1;
            $rows       = $limit ? array_slice($allRows, $offset, $limit) : $allRows;
        } else {
            $countStmt = $db->prepare("SELECT COUNT(*) FROM `$table` $joinSql $where $groupBySql");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            $sql = "SELECT $select FROM `$table` $joinSql $where $groupBySql $orderBy";
            if ($limit) $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            if ($limit) {
                $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($subselects) || !empty($calculatedFields)) {
                $applyVirtual($rows);
            }
            $totalPages = $limit ? ceil($total / $limit) : 1;
        }
        $cbConfig = artigrid_load_grid_config((string)$gridId);
        if (!is_array($rows)) {
            $rows = [];
        }
        foreach ($rows as &$row) {
            $row = artigrid_run_callbacks('beforeRenderRow', $cbConfig, $row);
        }
        unset($row);
        echo json_encode([
            'data'       => array_values($rows),
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $limit ?: 'all',
            'totalPages' => $totalPages,
            'summary'    => $summaryResult
        ]);
    exit;

    case 'calendar_events':
    if (!$table) {
        echo json_encode([]);
        exit;
    }
    $calCfg = json_decode($_POST['calendar'] ?? '{}', true) ?: [];
    $titleField = $calCfg['titleField'] ?? 'title';
    $startField = $calCfg['startField'] ?? 'start';
    $endField   = $calCfg['endField']   ?? 'end';
    $colorField = $calCfg['colorField'] ?? null;
    $allDayField = $calCfg['allDayField'] ?? null;

    $whereParts = [];
    $params     = [];
    $whereConditions = json_decode($_POST['where'] ?? '[]', true) ?: [];
    if (is_array($whereConditions)) {
        foreach ($whereConditions as $i => $w) {
            $field = $w['field'] ?? ($w[0] ?? null);
            $op    = strtoupper($w['operator'] ?? ($w[1] ?? '='));
            $value = $w['value'] ?? ($w[2] ?? null);
            if (!$field) continue;
            $key = ":w_{$field}_{$i}";
            $whereParts[] = "`$table`.`$field` $op $key";
            $params[$key] = $value;
        }
    }
    
    if (!empty($_POST['range_start']) && !empty($_POST['range_end'])) {
        $whereParts[] = "`$table`.`$startField` >= :range_start AND `$table`.`$startField` < :range_end";
        $params[':range_start'] = $_POST['range_start'];
        $params[':range_end']   = $_POST['range_end'];
    }

    $where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
    $sql = "SELECT * FROM `$table` $where";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($rows as $row) {
        $event = [
            'id'    => $row[$primaryKey] ?? null,
            'title' => $row[$titleField] ?? '(sin título)',
            'start' => $row[$startField] ?? null,
        ];
        if ($endField && !empty($row[$endField])) {
            $event['end'] = $row[$endField];
        }
        if ($colorField && !empty($row[$colorField])) {
            $event['color'] = $row[$colorField];
        }
        if ($allDayField && isset($row[$allDayField])) {
            $event['allDay'] = (bool)$row[$allDayField];
        }
        $event['extendedProps'] = $row;
        $events[] = $event;
    }

    echo json_encode($events);
    exit;

    case 'dependent_select':
    $field = $_POST['field'] ?? '';
    $parent_value = $_POST['parent_value'] ?? '';
    $depends_field = $_POST['depends_field'] ?? '';
    $configJson = $_POST['config'] ?? '';
    $whereJson = $_POST['where'] ?? '';
    $formValuesJson = $_POST['form_values'] ?? '';
    if (!$field || !$configJson) {
        echo json_encode([]);
        exit;
    }
    $config = json_decode($_POST['config'] ?? '{}', true);
    $where = json_decode($whereJson, true) ?? [];
    $formValues = json_decode($formValuesJson, true) ?? [];
    if (!$config || empty($config['comboBoxes'][$field])) {
        echo json_encode([]);
        exit;
    }
    $cfg = $config['comboBoxes'][$field];
    if ($cfg['source'] !== 'table') {
        echo json_encode([]);
        exit;
    }
    $table = $cfg['table'];
    $valueColumn = $cfg['value'];
    $labelColumnSql = is_array($cfg['label'])
        ? "CONCAT_WS(' ', " . implode(', ', array_map(fn($c) => "`$c`", $cfg['label'])) . ")"
        : "`{$cfg['label']}`";
    $orderBySql = is_array($cfg['label'])
        ? implode(", ", array_map(fn($c) => "`$c`", $cfg['label']))
        : "`{$cfg['label']}`";
    $whereClauses = [];
    $params = [];
    if (!empty($depends_field) && $parent_value !== '') {
        $whereClauses[] = "`$depends_field` = ?";
        $params[] = $parent_value;
    }
    if (!empty($where) && is_array($where)) {
        foreach ($where as $col => $condition) {
            $operator = '=';
            $val = $condition;
            if (is_array($condition) && count($condition) === 2) {
                [$operator, $val] = $condition;
                if ($operator === '==') {
                    $operator = '=';
                }
                $allowedOperators = [
                    '=', '!=', '<>', '>', '<', '>=', '<=',
                    'LIKE', 'NOT LIKE'
                ];
                if (!in_array(strtoupper($operator), $allowedOperators, true)) {
                    continue;
                }
            }
            if (is_string($val) && preg_match('/^{(.+)}$/', $val, $match)) {
                $dynamicField = $match[1];
                $val = $formValues[$dynamicField] ?? ($_POST[$dynamicField] ?? null);
            }
            if ($val === null || $val === '') {
                continue;
            }
            $whereClauses[] = "`$col` {$operator} ?";
            $params[] = $val;
        }
    }
    $whereSQL = '';
    if (!empty($whereClauses)) {
        $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
    }
    $sql = "
        SELECT `$valueColumn` AS val, $labelColumnSql AS txt
        FROM `$table`
        $whereSQL
        ORDER BY $orderBySql
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;

    case 'inline_update':
        $id    = $_POST['id'] ?? null;
        $field = $_POST['field'] ?? null;
        $value = $_POST['value'] ?? null;
        if ($id === null || $field === null) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid data'
            ]);
            exit;
        }
        $colsStmt = $db->query("DESCRIBE `$table`");
        $allowedFields = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array($field, $allowedFields)) {
            echo json_encode([
                'success' => false,
                'error' => 'Field not allowed'
            ]);
            exit;
        }
        $postData = [
            $primaryKey => $id,
            $field => $value
        ];
        $result = artigrid_update_handler($db, $table, $gridId, $postData);
        echo json_encode($result);
    exit;

    case 'insert':
        if ($mode === 'query') {
            echo json_encode(['success' => false, 'errors' => ['global' => ['Read-only mode']]]);
            exit;
        }
        try {
            if (!$gridId || empty($_SESSION['artigrid'][$gridId])) {
                throw new Exception('Grid not found in session');
            }
            $config = $_SESSION['artigrid'][$gridId]['config'] ?? [];
            if (empty($config)) {
                throw new Exception('Grid configuration not available');
            }
            $data = $_POST;
            unset(
                $data['action'],
                $data['table'],
                $data['mode'],
                $data['query'],
                $data['grid_id'],
                $data['requiredFields'],
                $data['config'],
                $data['csrf_token']
            );
            $preparedUploads = artigrid_handle_uploads($data, true);
            if (!$data) {
                throw new Exception('Empty data');
            }
            $colsStmt    = $db->query("DESCRIBE `$table`");
            $columnsInfo = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
            $realPrimaryKey  = null;
            $isAutoIncrement = false;
            foreach ($columnsInfo as $col) {
                if ($col['Key'] === 'PRI') {
                    $realPrimaryKey  = $col['Field'];
                    $isAutoIncrement = strpos($col['Extra'] ?? '', 'auto_increment') !== false;
                    break;
                }
            }
            if (!$realPrimaryKey) {
                $realPrimaryKey = $config['primaryKey'] ?? 'id';
            }
            if ($isAutoIncrement) {
                unset($data[$realPrimaryKey]);
            }
            unset($data['id']);
            $configFormFieldValues = $config['formFieldValues'] ?? [];
            if (!is_array($configFormFieldValues)) {
                $configFormFieldValues = [];
            }
            foreach ($configFormFieldValues as $field => $value) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $data[$field] = is_callable($value) ? $value() : $value;
                }
            }
            $whereConditionsConfig = $config['where'] ?? [];
            if (!is_array($whereConditionsConfig)) {
                $whereConditionsConfig = [];
            }
            foreach ($whereConditionsConfig as $condition) {
                $field = $condition['field'] ?? null;
                $value = $condition['value'] ?? null;
                if ($field && $value !== null && (!isset($data[$field]) || $data[$field] === '')) {
                    $data[$field] = $value;
                }
            }
            $errors = ['fields' => [], 'global' => []];
            $requiredFields = $config['requiredFields'] ?? []; 
            if (is_string($requiredFields)) { 
                $requiredFields = array_filter( array_map('trim', explode(',', $requiredFields))); 
            }
            foreach ($requiredFields as $field) {
                $isFile      = isset($_FILES[$field]);
                $isEmptyPost = !array_key_exists($field, $data) || trim((string)$data[$field]) === '';
                $isEmptyFile = true;
                if ($isFile) {
                    $file = $_FILES[$field];
                    if (is_array($file['name'])) {
                        $isEmptyFile = empty(array_filter($file['name']));
                    } else {
                        $isEmptyFile = empty($file['name']);
                    }
                }
                if ($isEmptyPost && (!$isFile || $isEmptyFile)) {
                    $errors['fields'][$field] = "This field is required";
                }
            }
            if (!empty($errors['fields'])) {
                echo json_encode(['success' => false, 'errors' => $errors, 'data' => $data]);
                exit;
            }
            foreach ($data as $k => $v) {
                if ($v === '') {
                    $data[$k] = null;
                }
            }
            $grid = new ArtiGrid($db);
            $grid->table($table);
            $cbConfig = artigrid_load_grid_config((string)$gridId);
            $result = artigrid_run_callbacks('beforeInsert', $cbConfig, $data);
            if (isset($result['success']) && $result['success'] === false) {
                echo json_encode($result);
                exit;
            }
            $data = $result;
            if (!empty($config)) {
                $grid->importConfig($config);
            }
            if (!empty($config['duplicateFields'])) {
                $fields = $config['duplicateFields'];
                $where  = [];
                $params = [];
                foreach ($fields as $field) {
                    $where[]         = "`$field` = :$field";
                    $params[":$field"] = $data[$field] ?? null;
                }
                $sql  = "SELECT COUNT(*) FROM `$table` WHERE " . implode(' AND ', $where);
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                if ($stmt->fetchColumn() > 0) {
                    $duplicateErrors = [];
                    foreach ($fields as $field) {
                        $duplicateErrors[$field] = ['This combination already exists'];
                    }
                    echo json_encode([
                        'success' => false,
                        'errors'  => [
                            'fields' => $duplicateErrors,
                            'global' => ['Duplicate record already exists']
                        ],
                        'data' => $data
                    ]);
                    exit;
                }
            }
            $lastId = $grid->insertData($data, $gridId);
            if (is_array($lastId) && isset($lastId['success']) && $lastId['success'] === false) {
                echo json_encode($lastId);
                exit;
            }
            echo json_encode(['success' => true, 'last_id' => $lastId]);
        } catch (PDOException $e) {
            $message = $e->getMessage();
            if ($e->getCode() === '01000' || strpos($message, 'SQLSTATE[01000]') !== false) {
                echo json_encode(['success' => true, 'last_id' => $db->lastInsertId()]);
                exit;
            }
            if (strpos($message, 'Duplicate entry') !== false) {
                $message = 'This record already exists';
            }
            echo json_encode(['success' => false, 'errors' => ['global' => [$message]]]);
        }
    exit;

    case 'select':
        header('Content-Type: application/json');
        try {
            $table = $_POST['table'] ?? '';
            if (!$table) {
                throw new Exception('Table not provided.');
            }
            $grid = new ArtiGrid($db);
            $grid->table($table);
            $data = $_POST;
            unset(
                $data['csrf_token']
            );
            $requiredFields = $config['requiredFields'] ?? [];
            if (is_string($requiredFields)) {
                $requiredFields = array_filter(array_map('trim', explode(',', $requiredFields)));
            }
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                    echo json_encode([
                        'success' => false,
                        'message' => "the field '$field' is mandatory",
                        'field'   => $field
                    ]);
                    exit;
                }
            }
            $cbConfig = artigrid_load_grid_config((string)$gridId);
            $data = artigrid_run_callbacks('beforeSelect', $cbConfig, $data);
            $success = true;
            $message = 'Correct data';
            foreach ($requiredFields as $field) {
                if ($data[$field] !== 'admin') {
                    $success = false;
                    $message = 'Invalid data';
                    break;
                }
            }
            $response = [
                'success' => $success,
                'message' => $message,
                'data'    => $success ? $data : []
            ];
            $response = artigrid_run_callbacks('afterSelect', $cbConfig, $response, $data);
            echo json_encode($response);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ]);
        }
    exit;

   case 'update':
    if ($mode === 'query') {
        echo json_encode([
            'success' => false,
            'errors' => [
                'global' => ['Read-only mode']
            ]
        ]);
        exit;
    }
    try {
        if (!$gridId || empty($_SESSION['artigrid'][$gridId])) {
            throw new Exception('Grid not found in session');
        }
        $config = $_SESSION['artigrid'][$gridId]['config'] ?? [];
        if (empty($config)) {
            throw new Exception('Grid configuration not available');
        }
        $data = $_POST;
        $preparedUploads = artigrid_handle_uploads($data, true);
        $imageFieldsConfig = $config['imageFieldsConfig'] ?? [];
        if (!is_array($imageFieldsConfig)) {
            $imageFieldsConfig = [];
        }
        foreach ($imageFieldsConfig as $imgField => $imgCfg) {
            $keepKey = $imgField . '_keep';
            if (!array_key_exists($keepKey, $data)) continue;

            $kept = json_decode($data[$keepKey] ?? '[]', true);
            if (!is_array($kept)) $kept = [];
            unset($data[$keepKey]);
            $newOnes = [];
            if (isset($data[$imgField])) {
                $decoded = json_decode($data[$imgField], true);
                $newOnes = is_array($decoded) ? $decoded : [$data[$imgField]];
            }
            $merged = array_values(array_filter(array_merge($kept, $newOnes)));

            if (empty($merged)) {
                $data[$imgField] = null;
            } elseif (!empty($imgCfg['multiple'])) {
                $data[$imgField] = json_encode($merged);
            } else {
                $data[$imgField] = end($merged);
            }
        }
        foreach ($data as $k => $v) {
            if (!preg_match('/^(.+)_keep$/', $k, $m)) continue;
            $baseField = $m[1];
            if (isset($imageFieldsConfig[$baseField])) continue; // ya lo maneja el bloque de imágenes
            $kept = json_decode($data[$k] ?? '[]', true);
            unset($data[$k]);
            if (empty($data[$baseField]) && !isset($_FILES[$baseField])) {
                $data[$baseField] = (is_array($kept) && $kept) ? end($kept) : null;
            }
        }
        unset(
            $data['action'],
            $data['table'],
            $data['mode'],
            $data['query'],
            $data['grid_id'],
            $data['requiredFields'],
            $data['config'],
            $data['csrf_token']
        );
        if (!$data) {
            throw new Exception('Empty data');
        }
        $colsStmt = $db->query("DESCRIBE `$table`");
        $columnsInfo = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
        $realPrimaryKey = null;
        foreach ($columnsInfo as $col) {
            if ($col['Key'] === 'PRI') {
                $realPrimaryKey = $col['Field'];
                break;
            }
        }
        if (!$realPrimaryKey) {
            $realPrimaryKey = $config['primaryKey'] ?? 'id';
        }
        $id = $data['id'] ?? $data[$realPrimaryKey] ?? null;
        unset($data[$realPrimaryKey], $data['id']);
        if (!$id) {
            throw new Exception('Missing primary key');
        }
        $errors = [
            'fields' => [],
            'global' => []
        ];
        $requiredFields = $config['requiredFields'] ?? [];
        if (is_string($requiredFields)) {
            $requiredFields = array_filter(array_map('trim', explode(',', $requiredFields)));
        }
        foreach ($requiredFields as $field) {
            $isFile = isset($_FILES[$field]);
            $isEmptyPost = !array_key_exists($field, $data) || trim((string)$data[$field]) === '';
            $isEmptyFile = true;
            if ($isFile) {
                $file = $_FILES[$field];
                if (is_array($file['name'])) {
                    $isEmptyFile = empty(array_filter($file['name']));
                } else {
                    $isEmptyFile = empty($file['name']);
                }
            }
            if ($isEmptyPost && (!$isFile || $isEmptyFile)) {
                $errors['fields'][$field] = "This field is required";
            }
        }
        if (!empty($errors['fields'])) {
            echo json_encode([
                'success' => false,
                'errors' => $errors,
                'data' => $data
            ]);
            exit;
        }
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }
        $grid = new ArtiGrid($db);
        $grid->table($table);
        $cbConfig = artigrid_load_grid_config((string)$gridId);
        $result = artigrid_run_callbacks(
            'beforeUpdate',
            $cbConfig,
            $data,
            [
                'table' => $table,
                'id' => $id,
                'pk' => $realPrimaryKey
            ]
        );
        if (isset($result['success']) && $result['success'] === false) {
            echo json_encode($result);
            exit;
        }
        $data = $result;
        if (!empty($config)) {
            $grid->importConfig($config);
        }
        if (!empty($config['duplicateFields'])) {
            $fields = $config['duplicateFields'];
            $where = [];
            $params = [];
            foreach ($fields as $field) {
                $where[] = "`$field` = :$field";
                $params[":$field"] = $data[$field] ?? null;
            }
            $sql = "SELECT COUNT(*) FROM `$table` 
                    WHERE " . implode(' AND ', $where) . "
                    AND `$realPrimaryKey` != :__pk";
            $params[':__pk'] = $id;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $exists = $stmt->fetchColumn();
            if ($exists > 0) {
                $duplicateErrors = [];
                foreach ($fields as $field) {
                    $duplicateErrors[$field] = ['This combination already exists'];
                }
                echo json_encode([
                    'success' => false,
                    'errors' => [
                        'fields' => $duplicateErrors,
                        'global' => ['Duplicate record already exists']
                    ],
                    'data' => $data
                ]);
                exit;
            }
        }
        $set = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
        $sql = "UPDATE `$table`
                SET $set
                WHERE `$realPrimaryKey` = :__pk";
        $stmt = $db->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->bindValue(':__pk', $id);
        $stmt->execute();
        $updated = $stmt->rowCount() > 0;
        artigrid_run_callbacks(
            'afterUpdate',
            $cbConfig,
            [
                'updated' => $updated,
                'id' => $id
            ],
            $data
        );
        echo json_encode([
            'success' => true,
            'updated' => $updated
        ]);
    } catch (PDOException $e) {
        $message = $e->getMessage();
        if ($e->getCode() === '01000' || strpos($message, 'SQLSTATE[01000]') !== false) {
            echo json_encode([
                'success' => true,
                'updated' => true
            ]);
            exit;
        }
        if (strpos($message, 'Duplicate entry') !== false) {
            $message = 'This record already exists';
        }
        echo json_encode([
            'success' => false,
            'errors' => [
                'global' => [$message]
            ]
        ]);
    }
    exit;

    case 'delete':
        $data = $_POST;
        unset(
            $data['csrf_token'],
            $data['action'],
            $data['table'],
            $data['mode'],
            $data['query'],
            $data['grid_id']
        );
        $pkValue = $data['value'] ?? null;
        $pkField = $data['pk'] ?? $primaryKey;
        if (!$table || !$pkField || !$pkValue) {
            echo json_encode(['success'=>false,'error'=>'Data is missing to delete']);
            exit;
        }
        try {
            $gridConfig = artigrid_load_grid_config((string)($gridId ?: $table));
            $payload = [
                'table' => $table,
                'pk'    => $pkField,
                'value' => $pkValue,
            ];
            $payload = artigrid_run_callbacks('beforeDelete', $gridConfig, $payload);
        } catch (Throwable $e) {
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
            exit;
        }
        $stmt = $db->prepare("DELETE FROM `$table` WHERE `$pkField`=?");
        $stmt->execute([$pkValue]);
        echo json_encode(['success'=>true]);
    exit;

    case 'edit-multiple':
        header('Content-Type: application/json');
        try {
            $idsRaw = $_POST['ids'] ?? [];
            if (is_string($idsRaw)) {
                $decoded = json_decode($idsRaw, true);
                $idsRaw = is_array($decoded) ? $decoded : explode(',', $idsRaw);
            }
            $ids = array_filter(
                array_map('trim', (array)$idsRaw),
                fn($v) => $v !== ''
            );
            if (empty($ids)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid IDs'
                ]);
                exit;
            }
            $colsStmt = $db->query("DESCRIBE `$table`");
            $allowedFields = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
            $updateData = [];
            foreach ($_POST as $field => $value) {
                if (in_array($field, [
                    'action','ids','bulk_ids','table','grid_id','mode','csrf_token'
                ])) continue;
                if (!in_array($field, $allowedFields)) continue;
                if ($value === '' || $value === null) continue;
                $updateData[$field] = $value;
            }
            if (empty($updateData)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No fields to update'
                ]);
                exit;
            }
            $gridConfig = artigrid_load_grid_config((string)$gridId);
            $db->beginTransaction();
            foreach ($ids as $id) {
                if (!empty($gridConfig)) {
                    artigrid_run_callbacks('beforeUpdateMultiple', $gridConfig, [
                        'table' => $table,
                        'pk'    => $primaryKey,
                        'value' => $id,
                        'data'  => $updateData
                    ]);
                }
                $set = [];
                $params = [];
                foreach ($updateData as $col => $val) {
                    $set[] = "`$col` = ?";
                    $params[] = $val;
                }
                $params[] = $id;
                $sql = "UPDATE `$table` SET " . implode(',', $set) . " WHERE `$primaryKey` = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                if (!empty($gridConfig)) {
                    artigrid_run_callbacks('afterUpdateMultiple', $gridConfig, [
                        'table' => $table,
                        'pk'    => $primaryKey,
                        'value' => $id,
                        'data'  => $updateData
                    ]);
                }
            }
            $db->commit();
            echo json_encode([
                'success' => true,
                'updated' => count($ids)
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    exit;

 case 'nested-table':
    $parentId       = $_POST['parent_id'] ?? $_POST['id'] ?? null;
    $gridId         = $_POST['parent_grid_id'] ?? $_POST['grid_id'] ?? null;
    $childTable     = $_POST['child_table'] ?? null;
    $childKey       = $_POST['child_key'] ?? null;
    $nestedGridId   = $_POST['nested_grid_id'] ?? null;
    if (!$parentId || !$childTable || !$childKey || !$gridId) {
        echo '<div class="alert alert-warning p-2 small">Missing parameters</div>';
        exit;
    }
    try {
        $fullConfig = [];
        if ($nestedGridId && isset($_SESSION['artigrid'][$nestedGridId])) {
            $fullConfig = array_merge(
                $_SESSION['artigrid'][$nestedGridId]['parent_config'] ?? [],
                $_SESSION['artigrid'][$nestedGridId]['config'] ?? []
            );
        } 
        elseif (isset($_SESSION['artigrid'][$gridId])) {
            $fullConfig = $_SESSION['artigrid'][$gridId]['config'] ?? [];
        }
        if (empty($fullConfig)) {
            throw new Exception("Grid configuration not found");
        }
        $nestedGrid = new ArtiGrid($db);
        $nestedGrid->applyConfig($fullConfig);
        $nestedGrid->table($childTable);
        $nestedGrid->where($childKey, '=', $parentId);
        if (!empty($fullConfig['nestedGrids'])) {
            $nestedGrid->reconstructNestedTablesFromConfig($fullConfig['nestedGrids']);
        }
        echo $nestedGrid->render('crud');
    } catch (Throwable $e) {
        echo '<div class="alert alert-danger p-2 small">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
    exit;

   case 'delete-multiple':
    $data = $_POST;
    unset(
        $data['csrf_token'],
        $data['action'],
        $data['table'],
        $data['mode'],
        $data['query'],
        $data['grid_id']
    );
    $idsRaw = $data['ids'] ?? [];
    if (is_string($idsRaw)) $idsRaw = explode(',', $idsRaw);
    $ids = array_filter(array_map('intval', (array)$idsRaw), fn($v) => $v > 0);
    if (!$primaryKey) {
        echo json_encode(['success' => false, 'error' => 'PK not defined']);
        exit;
    }
    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'No valid IDs were received']);
        exit;
    }
    try {
        $gridConfig = artigrid_load_grid_config((string)$gridId);
        if (!empty($gridConfig)) {
            foreach ($ids as $id) {
                $payload = [
                    'table' => $table,
                    'pk'    => $primaryKey,
                    'value' => $id,
                ];
                artigrid_run_callbacks('beforeDelete', $gridConfig, $payload);
            }
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM `$table` WHERE `$primaryKey` IN ($placeholders)");
        $stmt->execute($ids);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}