<?php

namespace App\Console\Commands;

use App\Services\Backups\BackupStreamCipher;
use Illuminate\Console\Command;
use Throwable;

class GenerateMobilityCloudBackupKey extends Command
{
    protected $signature = 'mobilitycloud:backup-key-generate
        {--path= : Absolute path where the encryption key is stored}';

    protected $description = 'Generate the client-side encryption key used for external MobilityCloud backups';

    public function handle(BackupStreamCipher $cipher): int
    {
        $path = (string) ($this->option('path') ?: config('mobilitycloud.external_backups.key_path'));

        try {
            $keyId = $cipher->generateKeyFile($path);
            $this->info('Backup encryption key created.');
            $this->line('Key ID: '.$keyId);
            $this->warn('Store a protected recovery copy outside the production server. The key itself is never printed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
