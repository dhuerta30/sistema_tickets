<?php

// BEFORE INSERT: recibe array $data y DEBE devolver array $data

use function Safe\password_hash;

function beforeUserInsert(array $data): array
{
    return $data;
}

function beforeUserUpdate(array $data){
    print_r($data);
    die();
    return $data;
}

function afterUserInsert($id, array $data = []): void
{
    error_log("Insertado ID: $id");
}

function miBeforeDelete($data) {
    return $data;
}

function antes_insertar_payments(array $data): array
{
    print_r($data);
    die();
    return $data;
}

function beforeUpdate(array $data): array
{
    return $data;
}


function insertproductlines($data){
    echo "Insert stop";
    die();
    return $data;
}

function beforeRenderRow(array $row){
    if ($row['activated'] == 0) {
        $row['activated'] = "No hay Status";
    }
    return $row;
}

function beforeRenderRowGrid(array $row)
{
    $row["changes"] = html_entity_decode($row["changes"]);
    $lines = preg_split("/\r\n|\n|\r/", $row["changes"]);
    $listHtml = "<ul style='padding-left:20px;margin:0;'>";
    foreach ($lines as $line) {
        if (trim($line) !== '') {
            $listHtml .= "<li>" . htmlspecialchars($line) . "</li>";
        }
    }
    $listHtml .= "</ul>";
    $row["changes"] = $listHtml;
    return $row;
}

function login($data){
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $db = DB::connect();
    $user = trim($data['username'] ?? '');
    $pass   = trim($data['password'] ?? '');
    if ($user === '' || $pass === '') {
        return [
            'success' => false,
            'message' => 'Empty username or password'
        ];
    }
    $q = new Queryfy($db);
    $row = $q->table('admin_users')
            ->where('username', $user)
            ->limit(1)
            ->get();
    
    $row = $row[0] ?? null;
    if ($row && password_verify($pass, $row['password'])) {
        $permissions = getPermissionsByRole($row['role']);
        $_SESSION['artigrid_auth'] = [
            'id'      => $row['id'],
            'role'     => $row['role'],
            'usuario' => $row['username'],
            'permissions' => $permissions
        ];
        return [
            'success' => true,
            'message' => 'Successful login',
            'redirect' => 'management.php'
        ];
    }
    return [
        'success' => false,
        'message' => 'Incorrect username or password'
    ];
}

function getPermissionsByRole($role){
    $map = [
        'admin' => ['add', 'view', 'edit', 'delete'],
        'editor' => ['view', 'edit'],
        'viewer' => ['view']
    ];
    return $map[$role] ?? [];
}

function insert_users($data){
    $data["password"] = \password_hash($data["password"], PASSWORD_DEFAULT);
    return $data;
}

function update_users($data){
    $data["password"] = \password_hash($data["password"], PASSWORD_DEFAULT);
    return $data;
}

function insert_paymets($data) {
    $errors = [
        'fields' => [],
        'global' => []
    ];

    if (empty($data['customerNumber'])) {
        $errors['fields']['customerNumber'] = 'Customer is required';
    }

    if (empty($data['checkNumber'])) {
        $errors['fields']['checkNumber'] = 'Check number is required';
    } elseif (strlen($data['checkNumber']) > 50) {
        $errors['fields']['checkNumber'] = 'Maximum 50 characters allowed';
    }

    /*if (empty($data['paymentDate'])) {
        $errors['fields']['paymentDate'] = 'Payment date is required';
    } elseif (!strtotime($data['paymentDate'])) {
        $errors['fields']['paymentDate'] = 'Invalid date format';
    }*/

    if (!isset($data['amount']) || $data['amount'] === '') {
        $errors['fields']['amount'] = 'Amount is required';
    } elseif (!is_numeric($data['amount'])) {
        $errors['fields']['amount'] = 'Amount must be a number';
    } elseif ($data['amount'] <= 0) {
        $errors['fields']['amount'] = 'Amount must be greater than 0';
    }

    if (!empty($data['paymentDate'])) {
        $today = date('Y-m-d');
        if ($data['paymentDate'] > $today) {
            $errors['global'][] = 'Payment date cannot be in the future';
        }
    }

    if (!empty($errors['fields']) || !empty($errors['global'])) {
        return [
            'success' => false,
            'errors' => $errors,
            'data' => $data
        ];
    }
    return $data;
}

function update_paymets($data){
    $errors = [
        'fields' => [],
        'global' => []
    ];

    if (empty($data['customerNumber'])) {
        $errors['fields']['customerNumber'] = 'Customer is required';
    }

    if (empty($data['checkNumber'])) {
        $errors['fields']['checkNumber'] = 'Check number is required';
    } elseif (strlen($data['checkNumber']) > 50) {
        $errors['fields']['checkNumber'] = 'Maximum 50 characters allowed';
    }

    if (empty($data['paymentDate'])) {
        $errors['fields']['paymentDate'] = 'Payment date is required';
    } elseif (!strtotime($data['paymentDate'])) {
        $errors['fields']['paymentDate'] = 'Invalid date format';
    }

    if (!isset($data['amount']) || $data['amount'] === '') {
        $errors['fields']['amount'] = 'Amount is required';
    } elseif (!is_numeric($data['amount'])) {
        $errors['fields']['amount'] = 'Amount must be a number';
    } elseif ($data['amount'] <= 0) {
        $errors['fields']['amount'] = 'Amount must be greater than 0';
    }

    if (!empty($data['paymentDate'])) {
        $today = date('Y-m-d');
        if ($data['paymentDate'] > $today) {
            $errors['global'][] = 'Payment date cannot be in the future';
        }
    }

    if (!empty($errors['fields']) || !empty($errors['global'])) {
        return [
            'success' => false,
            'errors' => $errors,
            'data' => $data
        ];
    }
    return $data;
}

function Payment_list($row)
{
    if (!empty($row['receipt_images'])) {
        $images = json_decode($row['receipt_images'], true);
        if (is_array($images) && count($images) > 0) {
            $html = '';
            foreach ($images as $image) {
                $html .= '<img src="/ArtiGrid/artigrid/uploads/'.$image.'" width="80" style="margin:3px;">';
            }
            $row['receipt_images'] = $html;
        } else {
            $row['receipt_images'] = '';
        }
    }
    return $row;
}