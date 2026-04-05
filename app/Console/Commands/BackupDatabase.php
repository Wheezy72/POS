<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--disk=} {--path=}';

    protected $description = 'Create a zipped SQLite backup and push it to the configured filesystem disk.';

    public function handle(): int
    {
        $databasePath = (string) config('database.connections.sqlite.database');

        if ($databasePath === '' || $databasePath === ':memory:') {
            $this->error('Database backup requires a file-based SQLite database.');

            return self::FAILURE;
        }

        if (! is_file($databasePath)) {
            $this->error("SQLite database file was not found at [{$databasePath}].");

            return self::FAILURE;
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The ZipArchive extension is required for database backups.');
        }

        $disk = (string) ($this->option('disk') ?: config('filesystems.backup_disk', 's3'));
        $targetPath = (string) ($this->option('path') ?: 'database-backups/' . now()->format('Y/m/d') . '/database-' . now()->format('His') . '.zip');

        $temporaryDirectory = storage_path('app/private/database-backups');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0777, true);
        }

        $temporaryZipPath = $temporaryDirectory . '/database-' . now()->format('YmdHis') . '.zip';

        $zip = new ZipArchive();

        if ($zip->open($temporaryZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the backup archive.');
        }

        $zip->addFile($databasePath, 'database.sqlite');
        $zip->close();

        Storage::disk($disk)->put($targetPath, file_get_contents($temporaryZipPath));

        @unlink($temporaryZipPath);

        $this->info("Database backup stored on disk [{$disk}] at [{$targetPath}].");

        return self::SUCCESS;
    }
}
