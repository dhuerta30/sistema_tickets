<?php

return [
    'beforeInsert' => [
        ['callback' => 'insertproductlines', 'file' => 'functions.php'],
    ],

    'afterInsert' => [
        ['callback' => '', 'file' => 'functions.php'],
    ],

    'beforeUpdate' => [
        ['callback' => '', 'file' => 'functions.php'],
    ],

    'beforeDelete' => [
        ['callback' => '', 'file' => 'functions.php'],
    ],
];