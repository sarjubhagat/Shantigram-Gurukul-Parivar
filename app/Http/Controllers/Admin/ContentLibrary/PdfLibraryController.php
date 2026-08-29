<?php

namespace App\Http\Controllers\Admin\ContentLibrary;

use App\Http\Controllers\Admin\AdminController;
use App\Jobs\ProcessPdfJob;
use App\Models\ContentLibrary\Pdf;
use App\Services\Pdf\PdfTusUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Global PDF library — upload once, reference unique_key (pdf_xxx…) from any module.
 * Linearized PDFs are served with HTTP Range support for PDF.js / viewers.
 */
class PdfLibraryController extends AdminController
{
    public function index(): View
    {
        return view('admin.pdf-library.index');
    }

    public function get_data(): JsonResponse
    {
        $request = request();

        $searchRaw = is_string($request->input('searchValue', ''))
            ? trim($request->input('searchValue', ''))
            : '';

        $colMap = [0 => 'id', 2 => 'title', 4 => 'processing_status', 3 => 'updated_at'];
        $orderByFieldName   = null;
        $orderTypeAscOrDesc = null;
        if ($request->has('order')) {
            $first              = $request->input('order')[0] ?? [];
            $colIdx             = (int) ($first['column'] ?? 0);
            $orderByFieldName   = $colMap[$colIdx] ?? 'id';
            $orderTypeAscOrDesc = ($first['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        }

        $withDeleted = $request->boolean('with_deleted');

        $filterArr = [
            'searchFilter'       => $searchRaw,
            'orderByFieldName'   => $orderByFieldName,
            'orderTypeAscOrDesc' => $orderTypeAscOrDesc,
            'iDisplayStart'      => (int) $request->input('start', 0),
            'iDisplayLength'     => (int) $request->input('length', 10),
            'with_deleted'       => $withDeleted,
        ];

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords()) {
            $filterArr['created_by'] = $user->id;
        }

        $baseFilter = ['with_deleted' => $withDeleted];
        if (! empty($filterArr['created_by'])) {
            $baseFilter['created_by'] = $filterArr['created_by'];
        }

        $rows  = Pdf::getRecordList($filterArr, false);
        $total = Pdf::getRecordList($filterArr, true);
        $grand = Pdf::getRecordList($baseFilter, true);

        return response()->json([
            'draw'            => (int) $request->input('draw', 0),
            'recordsTotal'    => (int) $grand,
            'recordsFiltered' => (int) $total,
            'data'            => $rows->map(fn ($row) => $this->datatableRow($row))->values(),
        ]);
    }

    public function AddEditAction(int $id = null)
    {
        abort(404);
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        abort(404);
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Use the TUS upload endpoint.'], 501);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pdf = Pdf::find($id);

        if (! $pdf) {
            return response()->json(['status' => false, 'message' => 'PDF not found.'], 404);
        }

        return response()->json(['status' => true, 'data' => $this->pdfPayload($pdf)]);
    }

    public function tus(Request $request): Response
    {
        $prefix = 'admin/' . ($this->moduleName ?: 'pdf-library') . '/tus';

        return app(PdfTusUploadService::class)->handle($request, $prefix);
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tus_key'   => 'required|string',
            'title'     => 'required|string|max:255',
            'tags'      => 'nullable|string|max:5000',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer|min:1',
        ]);

        $tus = app(PdfTusUploadService::class);

        if (! $tus->isUploadComplete($data['tus_key'])) {
            return response()->json(['ok' => false, 'message' => 'Upload not finished yet — retry in a moment.'], 422);
        }

        $inputPath = $tus->uploadFilePath($data['tus_key']);
        if (! $inputPath || ! is_file($inputPath)) {
            return response()->json(['ok' => false, 'message' => 'Uploaded file is missing on the server.'], 422);
        }

        $ext = strtolower(pathinfo($data['file_name'] ?? $inputPath, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return response()->json(['ok' => false, 'message' => 'Only PDF files are allowed.'], 422);
        }

        $pdf = Pdf::create([
            'title'             => $data['title'],
            'tags'              => Pdf::normalizeTagsInput($data['tags'] ?? null),
            'file_size_bytes'   => $data['file_size'] ?? (is_file($inputPath) ? filesize($inputPath) : null),
            'processing_status' => Pdf::STATUS_PENDING,
            'sort_order'        => 0,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]);

        try {
            $stagedPath = $tus->stageForPipeline($inputPath, $pdf->id, $data['file_name'] ?? null);
        } catch (\Throwable $e) {
            $pdf->update([
                'processing_status' => Pdf::STATUS_FAILED,
                'processing_error'  => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $pdf->update(['processing_status' => Pdf::STATUS_PROCESSING]);
        ProcessPdfJob::dispatch($pdf->id, $stagedPath);

        return response()->json([
            'ok'         => true,
            'pdf_id'     => $pdf->id,
            'unique_key' => $pdf->unique_key,
            'status'     => $pdf->processing_status,
        ]);
    }

    public function pdfStatus(Request $request, int $id): JsonResponse
    {
        $pdf = Pdf::find($id);

        if (! $pdf) {
            return response()->json(['status' => 'error', 'message' => 'PDF not found.'], 404);
        }

        return response()->json([
            'id'            => $pdf->id,
            'status'        => $pdf->processing_status,
            'error'         => $pdf->processing_error,
            'page_count'    => $pdf->page_count,
            'size_human'    => $pdf->formattedSize(),
            'thumbnail_url' => $pdf->thumbnail_url,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pdf = Pdf::find($id);

        if (! $pdf) {
            return response()->json(['status' => false, 'message' => 'PDF not found.'], 404);
        }

        if (! $pdf->isActive()) {
            return response()->json(['status' => false, 'message' => 'Only active PDFs can be edited.'], 422);
        }

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'tags'      => 'nullable|string|max:5000',
            'tus_key'   => 'nullable|string',
            'file_name' => 'nullable|string|max:255',
        ]);

        $tus       = app(PdfTusUploadService::class);
        $tusKey    = $data['tus_key'] ?? null;
        $replace   = false;
        $inputPath = null;

        if ($tusKey) {
            if (! $tus->isUploadComplete($tusKey)) {
                return response()->json(['status' => false, 'message' => 'New PDF upload not finished yet.'], 422);
            }
            $inputPath = $tus->uploadFilePath($tusKey);
            if (! $inputPath || ! is_file($inputPath)) {
                return response()->json(['status' => false, 'message' => 'Replacement file missing on server.'], 422);
            }
            $replace = true;
        }

        $pdf->update([
            'title'             => $data['title'],
            'tags'              => Pdf::normalizeTagsInput($data['tags'] ?? null),
            'updated_by'        => Auth::id(),
            'processing_status' => $replace ? Pdf::STATUS_PENDING : $pdf->processing_status,
        ]);

        if ($replace && $inputPath) {
            try {
                $stagedPath = $tus->stageForPipeline($inputPath, $pdf->id, $data['file_name'] ?? null);
            } catch (\Throwable $e) {
                $pdf->update([
                    'processing_status' => Pdf::STATUS_FAILED,
                    'processing_error'  => $e->getMessage(),
                ]);

                return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
            }

            $pdf->update(['processing_status' => Pdf::STATUS_PROCESSING]);
            ProcessPdfJob::dispatch($pdf->id, $stagedPath);
        }

        return response()->json([
            'status'  => true,
            'message' => $replace
                ? 'PDF queued for re-linearization. Title and tags updated.'
                : 'PDF updated successfully.',
            'data'    => $this->pdfPayload($pdf->fresh()),
        ]);
    }

    public function preview(int $id): View
    {
        $pdf = Pdf::findOrFail($id);

        return view('admin.pdf-library.show', compact('pdf'));
    }

    public function stream(Request $request, int $id): StreamedResponse|Response
    {
        $pdf = Pdf::find($id);

        if (! $pdf || ! $pdf->isActive() || ! $pdf->pdf_key) {
            abort(404);
        }

        $disk   = Storage::disk('r2');
        $client = $disk->getClient();
        $bucket = config('filesystems.disks.r2.bucket');
        $key    = $pdf->pdf_key;

        try {
            $head = $client->headObject(['Bucket' => $bucket, 'Key' => $key]);
        } catch (\Throwable) {
            abort(404);
        }

        $totalSize = (int) ($head['ContentLength'] ?? 0);
        if ($totalSize <= 0) {
            abort(404);
        }

        $rangeHeader = $request->header('Range');
        $hasRange    = is_string($rangeHeader) && str_starts_with(strtolower($rangeHeader), 'bytes=');

        if (! $hasRange) {
            return $this->streamFromR2($client, $bucket, $key, 0, $totalSize - 1, $totalSize, false, $this->downloadFilename($pdf));
        }

        $spec = substr($rangeHeader, 6);
        $spec = explode(',', $spec, 2)[0];
        [$startStr, $endStr] = array_pad(explode('-', $spec, 2), 2, '');

        if ($startStr === '' && $endStr === '') {
            return $this->rangeNotSatisfiable($totalSize);
        }

        if ($startStr === '' && $endStr !== '') {
            $len   = (int) $endStr;
            $start = max(0, $totalSize - $len);
            $end   = $totalSize - 1;
        } else {
            $start = (int) $startStr;
            $end   = ($endStr === '') ? ($totalSize - 1) : (int) $endStr;
        }

        if ($start < 0 || $end >= $totalSize || $start > $end) {
            return $this->rangeNotSatisfiable($totalSize);
        }

        return $this->streamFromR2($client, $bucket, $key, $start, $end, $totalSize, true, $this->downloadFilename($pdf));
    }

    public function download(Request $request, int $id): Response
    {
        $pdf = Pdf::find($id);

        if (! $pdf || ! $pdf->isActive() || ! $pdf->pdf_key) {
            abort(404, 'PDF not available for download.');
        }

        try {
            $url = Storage::disk('r2')->temporaryUrl(
                $pdf->pdf_key,
                now()->addMinutes(5),
                ['ResponseContentDisposition' => 'attachment; filename="' . $this->downloadFilename($pdf) . '"']
            );
        } catch (\Throwable) {
            abort(500, 'Download unavailable.');
        }

        return redirect()->away($url);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No records selected.'], 422);
        }

        $count = 0;
        foreach (Pdf::whereIn('id', $ids)->get() as $pdf) {
            $pdf->updated_by = Auth::id();
            $pdf->save();
            $pdf->delete();
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' PDF' . ($count === 1 ? '' : 's') . ' deleted.',
        ]);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        $pdf = Pdf::find($id);

        if (! $pdf) {
            return response()->json(['success' => false, 'message' => 'PDF not found.'], 404);
        }

        $pdf->updated_by = Auth::id();
        $pdf->save();
        $pdf->delete();

        return response()->json(['success' => true, 'message' => 'PDF deleted.']);
    }

    private function streamFromR2($client, string $bucket, string $key, int $start, int $end, int $total, bool $partial, string $filename): StreamedResponse
    {
        $length  = $end - $start + 1;
        $status  = $partial ? 206 : 200;
        $headers = [
            'Content-Type'        => 'application/pdf',
            'Content-Length'      => (string) $length,
            'Accept-Ranges'       => 'bytes',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ];
        if ($partial) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$total}";
        }

        return new StreamedResponse(function () use ($client, $bucket, $key, $start, $end) {
            $result = $client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'Range'  => "bytes={$start}-{$end}",
            ]);
            $stream = $result['Body'];
            $out    = fopen('php://output', 'wb');
            while (! $stream->eof()) {
                $chunk = $stream->read(64 * 1024);
                if ($chunk === '') {
                    break;
                }
                fwrite($out, $chunk);
                if (function_exists('ob_get_level') && ob_get_level() > 0) {
                    @ob_flush();
                }
                @flush();
            }
            fclose($out);
        }, $status, $headers);
    }

    private function rangeNotSatisfiable(int $totalSize): Response
    {
        return response('', 416, [
            'Content-Range' => "bytes */{$totalSize}",
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function downloadFilename(Pdf $pdf): string
    {
        $base = trim(preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $pdf->title) ?: 'document', '._-');

        return ($base !== '' ? $base : 'document') . '.pdf';
    }

    private function pdfPayload(Pdf $pdf): array
    {
        return [
            'id'            => $pdf->id,
            'unique_key'    => $pdf->unique_key,
            'title'         => $pdf->title,
            'tags'          => $pdf->tags ?? [],
            'tags_string'   => $pdf->tagsAsString(),
            'status'        => $pdf->processing_status,
            'error'         => $pdf->processing_error,
            'page_count'    => $pdf->page_count,
            'size_human'    => $pdf->formattedSize(),
            'thumbnail_url' => $pdf->thumbnail_url,
            'created_at'    => $pdf->created_at?->format('Y-m-d H:i'),
            'updated_at'    => $pdf->updated_at?->format('Y-m-d H:i'),
        ];
    }

    private function datatableRow(Pdf $pdf): array
    {
        if ($pdf->trashed()) {
            $sm = ['label' => 'Deleted', 'class' => 'pl-deleted'];
        } else {
            $dotMap = [
                Pdf::STATUS_PENDING    => ['label' => 'Pending',    'class' => 'pl-pending'],
                Pdf::STATUS_PROCESSING => ['label' => 'Processing', 'class' => 'pl-processing'],
                Pdf::STATUS_ACTIVE     => ['label' => 'Active',     'class' => 'pl-active'],
                Pdf::STATUS_FAILED     => ['label' => 'Failed',     'class' => 'pl-failed'],
            ];
            $sm = $dotMap[$pdf->processing_status] ?? ['label' => ucfirst($pdf->processing_status), 'class' => ''];
        }

        $thumb = $pdf->thumbnail_url
            ? '<img src="' . e($pdf->thumbnail_url) . '" alt="" style="width:52px;height:68px;object-fit:cover;border-radius:var(--r-sm);border:1px solid var(--border);">'
            : '<div style="width:52px;height:68px;border-radius:var(--r-sm);background:rgba(139,26,26,.08);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.2rem;border:1px solid var(--border);"><i class="bi bi-file-earmark-pdf"></i></div>';

        $uk     = e($pdf->unique_key ?? '');
        $copyJs = "navigator.clipboard.writeText('" . addslashes($pdf->unique_key ?? '') . "');var e=this;e.style.color='#16a34a';setTimeout(function(){e.style.color='';},1200);";
        $idHtml = '<div style="line-height:1.4;">'
            . '<span style="font-family:ui-monospace,monospace;font-size:.75rem;color:var(--text-muted);">#' . $pdf->id . '</span><br>'
            . '<span onclick="' . $copyJs . '" style="font-family:ui-monospace,monospace;font-size:.68rem;color:var(--primary);cursor:pointer;white-space:nowrap;" title="Click to copy">' . $uk . '</span>'
            . '</div>';

        $titleHtml = '<span style="font-weight:600;">' . e($pdf->title) . '</span>';
        if ($pdf->processing_error) {
            $titleHtml .= '<br><small class="text-danger" title="' . e($pdf->processing_error) . '">' . e(mb_substr($pdf->processing_error, 0, 60)) . '…</small>';
        }

        $tagsHtml = '';
        if (is_array($pdf->tags) && count($pdf->tags)) {
            foreach ($pdf->tags as $t) {
                $tagsHtml .= '<span class="pl-tag">' . e($t) . '</span>';
            }
        } else {
            $tagsHtml = '<span class="text-muted small">—</span>';
        }

        $statusHtml = '<span class="pl-status ' . $sm['class'] . '" data-role="status" data-pdf-id="' . $pdf->id . '">'
            . '<span class="dot"></span>' . $sm['label'] . '</span>';

        $pages = $pdf->page_count ? e((string) $pdf->page_count) : '<span class="text-muted">—</span>';
        $size  = $pdf->file_size_bytes ? e($pdf->formattedSize()) : '<span class="text-muted">—</span>';

        $actions = '<div class="d-flex gap-1 justify-content-center">';
        if ($pdf->isActive()) {
            $previewUrl = route('admin.' . $this->moduleName . '.preview', $pdf->id);
            $actions .= '<a href="' . e($previewUrl) . '" class="btn btn-sm btn-icon pl-btn-view" title="View PDF" target="_blank">'
                . '<i class="bi bi-eye" style="font-size:.85rem;"></i></a>';
            $actions .= '<button type="button" class="btn btn-sm btn-icon pl-btn-edit" data-id="' . $pdf->id . '" title="Edit PDF">'
                . '<i class="bi bi-pencil-square" style="font-size:.85rem;"></i></button>';
            if ($pdf->pdf_key && auth()->user()?->can($this->moduleName . '-download')) {
                $downloadUrl = route('admin.' . $this->moduleName . '.download', $pdf->id);
                $actions .= '<a href="' . e($downloadUrl) . '" class="btn btn-sm btn-icon pl-btn-dl" title="Download">'
                    . '<i class="bi bi-download" style="font-size:.85rem;"></i></a>';
            }
        }
        if (! $pdf->trashed()) {
            $actions .= '<button type="button" class="btn btn-sm btn-icon pl-btn-del" data-id="' . $pdf->id . '" data-title="' . e($pdf->title) . '" title="Delete">'
                . '<i class="bi bi-trash" style="font-size:.85rem;"></i></button>';
        }
        $actions .= '</div>';

        return [$idHtml, $thumb, $titleHtml, $tagsHtml, $statusHtml, $pages, $size, $actions];
    }
}
