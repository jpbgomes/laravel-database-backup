<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Storage Path
    |--------------------------------------------------------------------------
    | Where backup files should be temporarily stored before being emailed.
    */
    'path' => storage_path('app/backups'),

    /*
    |--------------------------------------------------------------------------
    | Backup Recipient Email
    |--------------------------------------------------------------------------
    | Default email address where database backups will be sent.
    */
    'recipient' => env('BACKUP_EMAIL', 'admin@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Keep Local Copy
    |--------------------------------------------------------------------------
    | Whether to keep the backup file locally after sending.
    */
    'keep_local' => false,
];
