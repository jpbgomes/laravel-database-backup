<?php

namespace Jpbgomes\DatabaseBackup;

use Illuminate\Support\ServiceProvider;
use Jpbgomes\DatabaseBackup\Console\Commands\BackupDatabase;

class BackupServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/backup.php', 'backup');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/backup.php' => config_path('backup.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/Console/Commands/BackupDatabase.php' => app_path('Console/Commands/BackupDatabase.php'),
            ], 'command');

            $this->commands([
                BackupDatabase::class,
            ]);
        }
    }
}
