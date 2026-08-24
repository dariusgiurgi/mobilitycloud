<?php

namespace App\Console\Commands;

use App\Support\DemoWorkspace;
use Illuminate\Console\Command;

class SyncLiveDemoWorkspace extends Command
{
    protected $signature = 'mobilitycloud:sync-live-demo';

    protected $description = 'Create or refresh the isolated public read-only MobilityCloud demo workspace';

    public function handle(): int
    {
        $project = DemoWorkspace::ensure();

        $this->info("Live demo workspace ready: {$project->name} (project #{$project->id}).");

        return self::SUCCESS;
    }
}
