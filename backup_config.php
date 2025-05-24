<?php
// Database configuration for backup
$backup_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'malayasol'
];

// Backup directories and files
$backup_paths = [
    'files' => [
        '*.php',
        'css/',
        'js/',
        'icons/',
        'images/',
        '.htaccess'
    ],
    'exclude' => [
        'backups/',
        'temp/',
        'logs/'
    ]
];

// Backup storage directory
$backup_dir = 'backups/';

// Ensure backup directory exists
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}
?>