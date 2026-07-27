<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class WriteQueueHeartbeat implements
    ShouldQueue,
    ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 15;

    public int $uniqueFor = 55;

    public function uniqueId(): string
    {
        return 'production-readiness-queue-heartbeat';
    }

    public function handle(): void
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

        $path = $directory.'/queue.json';
        $temporary = $path.'.tmp';

        $payload = [
            'type' => 'queue',
            'recorded_at' => now()->toIso8601String(),
            'timestamp' => now()->timestamp,
            'queue' => $this->queue
                ?: config(
                    'production-readiness.heartbeat.queue_name',
                    'default'
                ),
            'connection' => $this->connection
                ?: config('queue.default'),
            'hostname' => gethostname() ?: null,
            'process_id' => getmypid(),
        ];

        File::put(
            $temporary,
            json_encode(
                $payload,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
            )
        );

        @chmod($temporary, 0600);

        rename($temporary, $path);
    }
}
