<?php

// config/config.php
return [
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'sistema_tickets',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8'
    ],
    'forms' => [
        'required_all_fields' => true
    ],
    'filter' => true,
    'search' => true,
    'add' => true,
    'refresh' => true,
    'actionsPosition' => 'right',
    'edit' => true,
    'clone' => false,
    'view' => true,
    'checkbox' => true,
    'dropdownpage' => true,
    'pagination' => true,
    'delete' => true,
    'delete_multiple' => true,
    'edit_multiple' => true,
    'mail' => [
        'host' => 'smtp.gmail.com',
        'username' => 'daniel.telematico@gmail.com',
        'password' => 'vynk ovgv foyv vbcs',
        'port' => 587,
        'secure' => 'tls',
        'from' => 'daniel.telematico@gmail.com',
        'from_name' => 'ArtiGrid'
    ]
];
