<?php

namespace KiranoDev\LaravelBackup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use KiranoDev\LaravelBackup\Helpers\TG;
use Spatie\DbSnapshots\Helpers\Format;
use Illuminate\Support\Facades\File;
use Spatie\DbSnapshots\SnapshotFactory;

class Backup extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Make database backup and send it to telegram';

    public function handle(): void
    {
        try {

            $connectionName = config('database.default');
            $snapshotName = str_replace(' ', '-', mb_strtolower(config('backup.prefix')) . '-' . now()->format('Y-m-d'));
            $compress = true;
            $tables = null;
            $exclude = null;

            $snapshot = app(SnapshotFactory::class)->create(
                $snapshotName,
                config('db-snapshots.disk'),
                $connectionName,
                $compress,
                $tables,
                $exclude
            );

            $size = $snapshot->size();
            $humanSize = Format::humanReadableSize($size);



//            $name = str_replace(' ', '-', mb_strtolower(config('app.name')) . '-' . now()->format('Y-m-d'));
            $fullname = $snapshotName . '.sql.gz';
//
//            Artisan::call(
//                'snapshot:create',
//                [
//                    'name' => $name,
//                    '--compress' => true,
//                ]
//            );
//
            $path = storage_path("app/snapshots/$fullname");
//            $size = File::size($path);
//            $humanSize = Format::humanReadableSize($size);

            if($size / 1024 / 1024 > 50) {
                app(TG::class)->sendFormatMessage([
                    'Слишком большой файл' => $fullname,
                    'Вес' => $humanSize
                ]);
            } else {
                app(TG::class)->sendFile(
                    Storage::disk('snapshots')->path($fullname),
                    [
                        config('app.name') => '#' . config('backup.tag'),
                        'Вес' => $humanSize,
                    ],
                );
            }

            File::delete($path);
        } catch (\Exception $e) {
            info('Backup Error: ' . $e->getMessage());
        }
    }
}
