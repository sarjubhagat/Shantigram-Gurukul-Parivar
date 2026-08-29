<?php

namespace App\Jobs;

use App\Services\Audio\AudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue wrapper for AudioProcessingService::process().
 *
 * WAV / FLAC conversion can be slow for large files, so timeout is generous.
 * No automatic retry — conversion failures are deterministic.
 */
class ProcessAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;
    public int $tries = 1;

    public function __construct(
        public int    $audioId,
        public string $inputPath,
    ) {
        $this->timeout = (int) config('audio.ffmpeg.timeout', 600);
    }

    public function handle(AudioProcessingService $pipeline): void
    {
        $pipeline->process($this->audioId, $this->inputPath);
    }
}
