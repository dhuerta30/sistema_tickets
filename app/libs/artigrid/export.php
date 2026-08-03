<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once 'db.php';
require_once 'ArtiGrid.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$type       = $_POST['type'] ?? '';
$gridId     = $_POST['grid_id'] ?? '';
$config     = json_decode($_POST['config'] ?? '', true);
$rowsFromJS = json_decode($_POST['rows'] ?? '', true);
$headersJS  = json_decode($_POST['headers'] ?? '', true);

if (!$config || empty($config['table'])) die('Grid invalid');

$table = $config['table'];

$cols = $headersJS ?: ($config['columns'] ?? []);
if (empty($cols)) $cols = $config['formFields'] ?? ['*'];
$cols = array_map(fn($c) => $c === '*' ? '*' : preg_replace('/[^a-zA-Z0-9_]/', '', $c), $cols);

if ($rowsFromJS) {
    $rows = $rowsFromJS;
} else {
    $search      = $_POST['search'] ?? '';
    $searchCol   = $_POST['searchCol'] ?? '';
    $sortColumn  = $_POST['sortColumn'] ?? '';
    $sortOrder   = $_POST['sortOrder'] ?? '';
    $colFilters  = $_POST['columnFilters'] ?? [];

    $where  = [];
    $params = [];

    if ($search) {
        if ($searchCol && in_array($searchCol, $cols, true)) {
            $where[]  = "`$searchCol` LIKE ?";
            $params[] = "%$search%";
        } else {
            $likeParts = [];
            foreach ($cols as $c) {
                if ($c === '*') continue;
                $likeParts[] = "`$c` LIKE ?";
                $params[] = "%$search%";
            }
            if ($likeParts) $where[] = '(' . implode(' OR ', $likeParts) . ')';
        }
    }

    if (is_array($colFilters)) {
        foreach ($colFilters as $col => $val) {
            if ($val !== '' && in_array($col, $cols, true)) {
                $where[]  = "`$col` LIKE ?";
                $params[] = "%$val%";
            }
        }
    }

    $sql = "SELECT " . implode(',', $cols) . " FROM `$table`";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    if ($sortColumn && in_array($sortColumn, $cols, true)) {
        $order = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';
        $sql  .= " ORDER BY `$sortColumn` $order";
    }

    $stmt = DB::connect()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$rows) exit('no data');

if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $cols);

    foreach ($rows as $r) {
        fputcsv($out, $r);
    }

    fclose($out);
    exit;
}

if ($type === 'excel') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="export.xlsx"');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray($cols, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if ($type === 'pdf') {
    $html = '<style>
        table{width:100%;border-collapse:collapse;font-size:11px;}
        th,td{border:1px solid #000;padding:4px;}
        th{background:#f0f0f0;}
    </style><table><tr>';

    foreach ($cols as $h) {
        $html .= "<th>$h</th>";
    }
    $html .= '</tr>';

    foreach ($rows as $r) {
        $html .= '<tr>';
        foreach ($r as $v) {
            $html .= '<td>'.htmlspecialchars((string)$v).'</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table>';

    $dompdf = new Dompdf();
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream('export.pdf', ['Attachment' => true]);
    exit;
}

exit('type invalid');