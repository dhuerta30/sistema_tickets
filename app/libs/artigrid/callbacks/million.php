<?php

// callbacks/million.php

return [
    'beforeInsert' => [
        ['callback' => 'beforeUserInsert', 'file' => 'functions.php'],
    ],

    'afterInsert' => [
        ['callback' => 'afterUserInsert', 'file' => 'functions.php'],
    ],

    'beforeUpdate' => [
        ['callback' => 'beforeUpdate', 'file' => 'functions.php'],
    ],

    'beforeDelete' => [
        ['callback' => 'miBeforeDelete', 'file' => 'functions.php'],
    ],

    'beforeRenderRow' => [
        ['callback' => 'beforeRenderRow', 'file' => 'functions.php'],
    ]
];
