<?php

namespace Jpbgomes\DatabaseBackup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Backup the database and send it via email.';

    public function handle()
    {
        $databaseName = config('database.connections.mysql.database');
        $databaseUser = config('database.connections.mysql.username');
        $databasePassword = config('database.connections.mysql.password');
        $databaseHost = config('database.connections.mysql.host');

        $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupDir = config('backup.path');
        $backupPath = $backupDir . '/' . $backupFile;

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $process = new Process([
            'mysqldump',
            "--user={$databaseUser}",
            "--password={$databasePassword}",
            "--host={$databaseHost}",
            $databaseName,
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Backup failed: ' . $process->getErrorOutput());
            return 1;
        }

        file_put_contents($backupPath, $process->getOutput());

        if (!file_exists($backupPath) || filesize($backupPath) === 0) {
            $this->error('Failed to create a valid database backup file!');
            return 1;
        }

        $recipient = config('backup.recipient');
        $appName = config('app.name');
        $timestamp = now()->toDateTimeString();

        Mail::raw("'{$appName}' Database backup file '{$databaseName}' attached", function ($message) use ($appName, $backupPath, $timestamp, $recipient) {
            $message->to($recipient)
                ->subject("'{$appName}' SQL Backup / {$timestamp}")
                ->attach($backupPath);
        });

        $this->info("Database backup created and emailed successfully to {$recipient}.");

        if (!config('backup.keep_local')) {
            unlink($backupPath);
            $this->info('Temporary backup file removed.');
        }

        return 0;
    }
}
