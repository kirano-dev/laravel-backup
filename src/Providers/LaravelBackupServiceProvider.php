<?php

namespace KiranoDev\LaravelBackup\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use KiranoDev\LaravelBackup\Helpers\TG;

class LaravelBackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton(TG::class, fn () => new TG());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/backup.php' => config_path('backup.php'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/backup.php');
        $this->mergeConfigFrom(
            __DIR__.'/../config/backup.php', 'backup'
        );

        Config::set('filesystems.disks.snapshots', config('backup.disks.snapshots'));

        if ( ! defined('CURL_SSLVERSION_TLSv1_2')) { define('CURL_SSLVERSION_TLSv1_2', 6); }
    }
}