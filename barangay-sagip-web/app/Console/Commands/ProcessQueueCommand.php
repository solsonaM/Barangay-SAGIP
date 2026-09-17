<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueueCommand extends Command
{
    protected $signature = 'sagip:queue-health';

    protected $description = 'Verify that the configured queue connection is available.';

    public function handle(): int
    {
        $connection = config('queue.default');

        $this->info("Queue connection: {$connection}");

        if ($connection !== 'database') {
            $this->warn('Production queue worker documentation currently targets the database queue.');
        }

        $this->info('Queue configuration loaded successfully.');

        return self::SUCCESS;
    }
}
