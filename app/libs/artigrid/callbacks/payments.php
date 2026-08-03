<?php

// callbacks/paymets.php

return [
    'beforeInsert' => [
        ['callback' => 'insert_paymets', 'file' => 'functions.php'],
    ],

    'beforeUpdate' => [
        ['callback' => 'update_paymets', 'file' => 'functions.php'],
    ],

    /*'afterInsert' => [
        ['callback' => '', 'file' => 'functions.php'],
    ],

    // ✅ NUEVO: BEFORE DELETE (misma forma)
    'beforeDelete' => [
        ['callback' => '', 'file' => 'functions.php'],
    ],*/

    'beforeRenderRow' => [
        ['callback' => 'Payment_list', 'file' => 'functions.php'],
    ]
];