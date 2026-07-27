<?php

namespace App\Console\Commands;

use App\Jobs\WriteQueueHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProductionHeartbeatCommand extends Command
{
    protected $signature = 'production:heartbeat';

    protected $description =
        'Write scheduler and queue-worker health heartbeats.';

    public function handle(): int
    {
        $directory = storage_path(
            'app/private/'
            .config(
                'production-readiness.heartbeat.directory',
                'production-readiness'
            )
        );

        File::ensureDirectoryExists(
            $directory,
            0700,
            true
        );

        $path = $directory.'/scheduler.json';
        $temporary = $path.'.tmp';

        File::put(
            $temporary,
            json_encode(
                [
                    'type' => 'scheduler',
                    'recorded_at' =>
                        now()->toIso8601String(),

                    'timestamp' =>
                        now()->timestamp,

                    'hostname' =>
                        gethostname() ?: null,

                    'process_id' =>
                        getmypid(),
                ],
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
            )
        );

        @chmod($temporary, 0600);

        rename($temporary, $path);

        $queueName = (string) config(
            'production-readiness.heartbeat.queue_name',
            'default'
        );

        WriteQueueHeartbeat::dispatch()
            ->onQueue($queueName);

        $this->components->info(
            'Scheduler heartbeat recorded and queue heartbeat dispatched.'
        );

        return self::SUCCESS;
    }
}
