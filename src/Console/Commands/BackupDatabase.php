<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Backup the database, zip it and send via email';

    public function handle()
    {
        $databaseName     = config('database.connections.mysql.database');
        $databaseUser     = config('database.connections.mysql.username');
        $databasePassword = config('database.connections.mysql.password');
        $databaseHost     = config('database.connections.mysql.host');

        $backupDir = config('backup.path');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $timestamp  = date('Y-m-d_H-i-s');
        $sqlFile    = $backupDir . "/backup_db_{$timestamp}.sql";
        $zipFile    = $backupDir . "/backup_db_{$timestamp}.zip";

        /*
        |--------------------------------------------------------------------------
        | Create SQL Dump
        |--------------------------------------------------------------------------
        */
        $process = new Process([
            'mysqldump',
            "--user={$databaseUser}",
            "--password={$databasePassword}",
            "--host={$databaseHost}",
            $databaseName,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Database backup failed: ' . $process->getErrorOutput());
            return 1;
        }

        file_put_contents($sqlFile, $process->getOutput());

        if (!file_exists($sqlFile) || filesize($sqlFile) === 0) {
            $this->error('Failed to create a valid SQL backup file!');
            return 1;
        }

        /*
        |--------------------------------------------------------------------------
        | Create ZIP Archive
        |--------------------------------------------------------------------------
        */
        $zip = new ZipArchive();

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Could not create ZIP archive.');
            return 1;
        }

        // Add SQL file
        $zip->addFile($sqlFile, basename($sqlFile));

        // Add extra files/folders
        foreach (config('backup.include', []) as $path) {
            $fullPath = base_path($path);

            if (file_exists($fullPath)) {
                if (is_dir($fullPath)) {
                    $this->addFolderToZip($fullPath, $zip);
                } else {
                    $zip->addFile($fullPath, basename($fullPath));
                }
            }
        }

        // Set password if defined
        $password = config('backup.zip_password');
        if ($password) {
            $zip->setPassword($password);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zip->setEncryptionIndex($i, ZipArchive::EM_AES_256);
            }
        }

        $zip->close();

        // Remove raw SQL
        unlink($sqlFile);

        /*
        |--------------------------------------------------------------------------
        | Send Email
        |--------------------------------------------------------------------------
        */
        $recipients = config('backup.recipients');
        $appName    = config('app.name');
        $emailTimestamp = now()->toDateTimeString();

        foreach ($recipients as $recipient) {
            Mail::raw("{$appName} Database Backup attached (ZIP).", function ($message) use ($recipient, $zipFile, $appName, $emailTimestamp) {
                $message->to($recipient)
                    ->subject("{$appName} Database Backup / {$emailTimestamp}")
                    ->attach($zipFile);
            });
        }

        $this->info("Backup ZIP created and emailed successfully.");

        if (!config('backup.keep_local') && file_exists($zipFile)) {
            unlink($zipFile);
            $this->info('Temporary ZIP file removed.');
        }

        return 0;
    }

    private function addFolderToZip($folder, ZipArchive $zip, $parentFolder = '')
    {
        $files = scandir($folder);

        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $folder . '/' . $file;
                $localPath = $parentFolder ? $parentFolder . '/' . $file : $file;

                if (is_dir($filePath)) {
                    $this->addFolderToZip($filePath, $zip, $localPath);
                } else {
                    $zip->addFile($filePath, $localPath);
                }
            }
        }
    }
}
