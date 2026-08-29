<?php

namespace App\Jobs;

use App\Services\Pdf\PdfProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue wrapper for PdfProcessingService::process().
 */
class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;
    public int $tries = 1;

    public function __construct(
        public int    $pdfId,
        public string $inputPath,
    ) {
        $this->timeout = (int) config('pdf.qpdf.timeout', 1200);
    }

    public function handle(PdfProcessingService $pipeline): void
    {
        $pipeline->process($this->pdfId, $this->inputPath);
    }
}
