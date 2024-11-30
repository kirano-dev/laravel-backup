<?php

namespace KiranoDev\LaravelBackup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use KiranoDev\LaravelBackup\Helpers\TG;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\DbSnapshots\Helpers\Format;
use Illuminate\Support\Facades\File;
use Spatie\DbSnapshots\SnapshotFactory;
use ZipArchive;

class Backup extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Make database backup and send it to telegram';

    public function addFilesToZip($zip, $source, $parentDir): void
    {
        // Если путь это директория
        if (is_dir($source)) {
            // Создаем директорию в архиве
            $zip->addEmptyDir($parentDir);

            // Открываем директорию
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            // Добавляем все файлы из директории в архив
            foreach ($files as $file) {
                // Пропускаем директории
                if ($file->isDir()) continue;

                $filePath = $file->getRealPath();
                $relativePath = $parentDir . DIRECTORY_SEPARATOR . substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    public function removeFiles($directory): void
    {
        // Удаляем все файлы и папки в директории
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        // Удаляем саму папку
//        rmdir($directory);
    }

    public function handle(): void
    {
        $name = str_replace(' ', '-', mb_strtolower(config('backup.prefix')) . '-' . now()->format('Y-m-d'));

        try {
//            $connectionName = config('database.default');
//
//            $compress = true;
//            $tables = null;
//            $exclude = null;
//
//            $snapshot = app(SnapshotFactory::class)->create(
//                $name,
//                config('db-snapshots.disk'),
//                $connectionName,
//                $compress,
//                $tables,
//                $exclude
//            );
//
//            $size = $snapshot->size();
//            $humanSize = Format::humanReadableSize($size);
//
//            $fullname = $name . '.sql.gz';
//            $path = storage_path("app/snapshots/$fullname");
//
//            if($size / 1024 / 1024 > 50) {
//                app(TG::class)->sendFormatMessage([
//                    'Слишком большой файл' => $fullname,
//                    'Вес' => $humanSize
//                ]);
//            } else {
//                app(TG::class)->sendFile(
//                    Storage::disk('snapshots')->path($fullname),
//                    [
//                        config('app.name') => '#' . config('backup.tag'),
//                        'Вес' => $humanSize,
//                    ],
//                );
//            }
//
//            File::delete($path);
        } catch (\Exception $e) {
            info('Backup Error: ' . $e->getMessage());
        }

        try {
            try {
                mkdir(storage_path("app/snapshots/files"));
            } catch (\Exception $e) {}

            $publicPath = public_path();
            $destinationPath = storage_path("app/snapshots/files");
            $archivePath = "{$destinationPath}/$name.zip";
            $splitCommand = "zip -r -9 -s 45m $archivePath $publicPath";
            exec($splitCommand);

            $chunks = File::allFiles($destinationPath);
            $index = 1;

            foreach ($chunks as $chunk) {
                $size = $chunk->getSize();
                $humanSize = Format::humanReadableSize($size);
                $fullname = $chunk->getFilename();
                $path = storage_path("app/snapshots/files/$fullname");

                try {
                    app(TG::class)->sendFile(
                        $path,
                        [
                            'Файлы' => "Чанк $index",
                            config('app.name') => '#' . config('backup.tag'),
                            'Вес' => $humanSize,
                        ],
                    );
                } catch (\Exception $e) {
                    app(TG::class)->sendFile(
                        $path,
                        [
                            'Файлы' => "Чанк $index",
                            config('app.name') => '#' . config('backup.tag'),
                            'Вес' => $humanSize,
                        ],
                    );
                }

                $index++;
            }

            $this->removeFiles($destinationPath);
        } catch (\Exception $e) {
            info('Backup Error: ' . $e->getMessage());
        }
    }
}
