<?php

namespace App\Jobs;

use App\Services\Video\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue wrapper for `VideoProcessingService::process()`.
 *
 * Lives here only to hand off work to the queue — no business logic.
 */
class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;
    public int $tries = 1; // FFmpeg failures are not transient — no automatic retry

    public function __construct(
        public int    $videoId,
        public string $inputPath,
    ) {
        $this->timeout = (int) config('video.ffmpeg.timeout', 7200);
    }

    public function handle(VideoProcessingService $pipeline): void
    {
        $pipeline->process($this->videoId, $this->inputPath);
    }
}
