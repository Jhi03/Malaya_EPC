<?php
// Database configuration for backup
$backup_config = [
    'host' => 'localhost',
    'username' => 'u188693564_adminsolar',
    'password' => '@Malayasolarenergies1',
    'database' => 'u188693564_malayasol'
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