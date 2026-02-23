<?php

namespace Jpbgomes\DatabaseBackup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Backup the database, zip it (with optional password) and send via email';

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

        // Add extra files/folders recursively
        $includes = config('backup.include', []);
        foreach ($includes as $item) {
            $path = base_path($item);
            if (file_exists($path)) {
                if (is_file($path)) {
                    $zip->addFile($path, $item);
                } elseif (is_dir($path)) {
                    $this->addFolderToZip($path, $zip, $item);
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
        $recipients = config('backup.recipients', []);
        $appName    = config('app.name');
        $emailTimestamp = now()->toDateTimeString();

        if (!is_array($recipients)) {
            $recipients = [$recipients];
        }

        foreach ($recipients as $recipient) {
            Mail::raw("{$appName} Database Backup attached (ZIP).", function ($message) use ($recipient, $zipFile, $appName, $emailTimestamp) {
                $message->to($recipient)
                    ->subject("{$appName} Database Backup / {$emailTimestamp}")
                    ->attach($zipFile);
            });
        }

        $this->info("Backup ZIP created and emailed successfully to: " . implode(', ', $recipients));

        // Remove ZIP if not keeping locally
        if (!config('backup.keep_local') && file_exists($zipFile)) {
            unlink($zipFile);
            $this->info('Temporary ZIP file removed.');
        }

        return 0;
    }

    /**
     * Recursively add folder contents to ZIP archive
     */
    private function addFolderToZip($folder, ZipArchive $zip, $parentFolder = '')
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $parentFolder . '/' . substr($filePath, strlen($folder) + 1);
            if ($file->isDir()) {
                // Optionally add empty folders
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
