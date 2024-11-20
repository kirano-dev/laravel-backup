<?php

namespace KiranoDev\LaravelBackup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use KiranoDev\LaravelBackup\Helpers\TG;
use Spatie\DbSnapshots\Helpers\Format;
use Illuminate\Support\Facades\File;

class Backup extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Make database backup and send it to telegram';

    public function handle(): void
    {
        try {
            $name = str_replace(' ', '-', mb_strtolower(config('app.name')) . '-' . now()->format('Y-m-d'));
            $fullname = $name . '.sql.gz';

            Artisan::call(
                'snapshot:create',
                [
                    'name' => $name
                ]
            );

            $path = storage_path("app/snapshots/$fullname");
            $size = File::size($path);
            $humanSize = Format::humanReadableSize($size);

            if($size / 1024 / 1024 > 50) {
                app(TG::class)->sendFormatMessage([
                    'Слишком большой файл' => $fullname,
                    'Вес' => $humanSize
                ]);
            } else {
                app(TG::class)::sendFile(
                    Storage::disk('snapshots')->path($fullname),
                    [
                        config('app.name') => '#idsystem',
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
