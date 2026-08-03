<?php

// callbacks/users.php

return [
    'beforeInsert' => [
        ['callback' => 'insert_users', 'file' => 'functions.php'],
    ],
    'beforeUpdate' => [
        ['callback' => 'update_users', 'file' => 'functions.php'],
    ],
    'beforeSelect' => [
        ['callback' => 'login', 'file' => 'functions.php'],
    ]
];