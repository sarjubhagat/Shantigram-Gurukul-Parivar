<?php

namespace App\Http\Controllers\Admin\ContentLibrary;

use App\Http\Controllers\Admin\AdminController;
use App\Jobs\ProcessVideoJob;
use App\Models\ContentLibrary\Video;
use App\Services\Video\TusUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global video library — upload once, reference unique_key (video_xxx…) from any module.
 * Playback uses HLS proxied through /stream/{quality}/{file} routes.
 */
class VideoLibraryController extends AdminController
{
    /* ── AdminController abstract contract ─────────────────────── */

    public function index(): View
    {
        return view('admin.video-library.index');
    }

    public function get_data(): JsonResponse
    {
        $request = request();

        $searchRaw = is_string($request->input('searchValue', ''))
            ? trim($request->input('searchValue', ''))
            : '';

        $colMap = [0 => 'id', 1 => 'title', 2 => 'processing_status', 3 => 'updated_at'];
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

        $rows  = Video::getRecordList($filterArr, false);
        $total = Video::getRecordList($filterArr, true);
        $grand = Video::getRecordList($baseFilter, true);

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

    /* ── Single-record fetch (edit modal pre-fill) ──────────────── */

    public function show(Request $request, int $id): JsonResponse
    {
        $video = Video::with('qualities')->find($id);

        if (! $video) {
            return response()->json(['status' => false, 'message' => 'Video not found.'], 404);
        }

        return response()->json(['status' => true, 'data' => $this->videoPayload($video)]);
    }

    /* ── TUS upload handler ─────────────────────────────────────── */

    public function tus(Request $request): Response
    {
        // Must match the dynamic module slug route: /admin/{slug}/tus
        $prefix = 'admin/' . ($this->moduleName ?: 'video-library') . '/tus';

        return app(TusUploadService::class)->handle($request, $prefix);
    }

    /* ── Post-upload completion → create row + dispatch job ─────── */

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tus_key'   => 'required|string',
            'title'     => 'required|string|max:255',
            'tags'      => 'nullable|string|max:5000',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer|min:1',
        ]);

        $tus = app(TusUploadService::class);

        if (! $tus->isUploadComplete($data['tus_key'])) {
            return response()->json(['ok' => false, 'message' => 'Upload not finished yet — retry in a moment.'], 422);
        }

        $inputPath = $tus->uploadFilePath($data['tus_key']);
        if (! $inputPath || ! is_file($inputPath)) {
            return response()->json(['ok' => false, 'message' => 'Uploaded file is missing on the server.'], 422);
        }

        $video = Video::create([
            'title'             => $data['title'],
            'tags'              => Video::normalizeTagsInput($data['tags'] ?? null),
            'file_size_bytes'   => $data['file_size'] ?? (is_file($inputPath) ? filesize($inputPath) : null),
            'processing_status' => Video::STATUS_PENDING,
            'sort_order'        => 0,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]);

        try {
            $stagedPath = $tus->stageForPipeline($inputPath, $video->id, $data['file_name'] ?? null);
        } catch (\Throwable $e) {
            $video->update([
                'processing_status' => Video::STATUS_FAILED,
                'processing_error'  => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        ProcessVideoJob::dispatch($video->id, $stagedPath);

        return response()->json([
            'ok'         => true,
            'video_id'   => $video->id,
            'unique_key' => $video->unique_key,
            'status'     => $video->processing_status,
        ]);
    }

    /* ── Status poll ────────────────────────────────────────────── */

    public function videoStatus(Request $request, int $id): JsonResponse
    {
        $video = Video::with('qualities')->find($id);

        if (! $video) {
            return response()->json(['status' => 'error', 'message' => 'Video not found.'], 404);
        }

        return response()->json([
            'id'             => $video->id,
            'status'         => $video->processing_status,
            'error'          => $video->processing_error,
            'duration_human' => $video->formattedDuration(),
            'size_human'     => $video->formattedSize(),
        ]);
    }

    /* ── Update title / tags (+ optional file replacement) ──────── */

    public function update(Request $request, int $id): JsonResponse
    {
        $video = Video::find($id);

        if (! $video) {
            return response()->json(['status' => false, 'message' => 'Video not found.'], 404);
        }

        if (! $video->isActive()) {
            return response()->json(['status' => false, 'message' => 'Only active videos can be edited.'], 422);
        }

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'tags'      => 'nullable|string|max:5000',
            'tus_key'   => 'nullable|string',
            'file_name' => 'nullable|string|max:255',
        ]);

        $tus       = app(TusUploadService::class);
        $tusKey    = $data['tus_key'] ?? null;
        $replace   = false;
        $inputPath = null;

        if ($tusKey) {
            if (! $tus->isUploadComplete($tusKey)) {
                return response()->json(['status' => false, 'message' => 'New video upload not finished yet.'], 422);
            }
            $inputPath = $tus->uploadFilePath($tusKey);
            if (! $inputPath || ! is_file($inputPath)) {
                return response()->json(['status' => false, 'message' => 'Replacement file missing on server.'], 422);
            }
            $replace = true;
        }

        $video->update([
            'title'             => $data['title'],
            'tags'              => Video::normalizeTagsInput($data['tags'] ?? null),
            'updated_by'        => Auth::id(),
            'processing_status' => $replace ? Video::STATUS_PENDING : $video->processing_status,
        ]);

        if ($replace && $inputPath) {
            try {
                $stagedPath = $tus->stageForPipeline($inputPath, $video->id, $data['file_name'] ?? null);
            } catch (\Throwable $e) {
                $video->update([
                    'processing_status' => Video::STATUS_FAILED,
                    'processing_error'  => $e->getMessage(),
                ]);

                return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
            }

            ProcessVideoJob::dispatch($video->id, $stagedPath);
        }

        return response()->json([
            'status'  => true,
            'message' => $replace
                ? 'Video queued for re-processing. Title and tags updated.'
                : 'Video updated successfully.',
            'data'    => $this->videoPayload($video->fresh()->load('qualities')),
        ]);
    }

    /* ── Preview page ───────────────────────────────────────────── */

    public function preview(Request $request, int $id): View
    {
        $video = Video::with('qualities')->findOrFail($id);

        return view('admin.video-library.show', compact('video'));
    }

    /* ── HLS master playlist ────────────────────────────────────── */

    public function masterPlaylist(Request $request, int $id): Response
    {
        $video = Video::with('qualities')->find($id);

        if (! $video || ! $video->isActive() || $video->qualities->isEmpty()) {
            abort(404);
        }

        $qualityConfig = (array) config('video.qualities', []);

        $lines = ['#EXTM3U'];

        foreach ($video->qualities->sortByDesc('size_bytes') as $q) {
            $cfg       = $qualityConfig[$q->quality] ?? [];
            $videoBps  = $this->parseBitrateToInt($cfg['bitrate'] ?? '1000k');
            $audioBps  = $this->parseBitrateToInt($cfg['audio']   ?? '128k');
            $bandwidth = $videoBps + $audioBps;
            $width     = (int) ($cfg['width']  ?? 1280);
            $height    = (int) ($cfg['height'] ?? 720);

            $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$width}x{$height},NAME=\"{$q->quality}\"";
            $lines[] = "{$q->quality}/playlist.m3u8";
        }

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'application/vnd.apple.mpegurl')
            ->header('Cache-Control', 'private, no-cache, no-store');
    }

    /* ── HLS proxy — streams R2 variant playlist + TS segments ─── */

    public function stream(Request $request, int $id, string $quality, string $file): Response
    {
        $video = Video::with('qualities')->find($id);

        if (! $video || ! $video->isActive()) {
            abort(404);
        }

        $qualityRow = $video->qualities->firstWhere('quality', $quality);

        if (! $qualityRow) {
            abort(404);
        }

        $base  = rtrim(dirname($qualityRow->hls_key), '/');
        $r2Key = $base . '/' . $file;

        try {
            $contents = Storage::disk('r2')->get($r2Key);
        } catch (\Throwable $e) {
            abort(404);
        }

        if ($contents === null) {
            abort(404);
        }

        $mime = str_ends_with($file, '.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/mp2t';

        return response($contents, 200)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /* ── Download original file ─────────────────────────────────── */

    public function download(Request $request, int $id): Response
    {
        $video = Video::find($id);

        if (! $video) {
            abort(404);
        }

        // Derive the R2 key: prefer the stored original_key; fall back to
        // the predictable path built from thumbnail_key for older records.
        $r2Key = $video->original_key;
        if (! $r2Key && $video->thumbnail_key) {
            $r2Key = rtrim(dirname($video->thumbnail_key), '/') . '/original.mp4';
        }

        if (! $r2Key) {
            abort(404, 'Original file not available for download.');
        }

        $filename = preg_replace('/[^\w\s\-.]/', '', $video->title) . '.mp4';

        try {
            $url = Storage::disk('r2')->temporaryUrl(
                $r2Key,
                now()->addMinutes(5),
                ['ResponseContentDisposition' => 'attachment; filename="' . $filename . '"']
            );
        } catch (\Throwable $e) {
            abort(500, 'Download unavailable.');
        }

        return redirect()->away($url);
    }

    /* ── Delete (bulk) ──────────────────────────────────────────── */

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No records selected.'], 422);
        }

        $videos = Video::whereIn('id', $ids)->get();
        $count  = 0;

        foreach ($videos as $video) {
            $video->updated_by = Auth::id();
            $video->save();
            $video->delete();
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' video' . ($count === 1 ? '' : 's') . ' deleted.',
        ]);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        $video = Video::find($id);

        if (! $video) {
            return response()->json(['success' => false, 'message' => 'Video not found.'], 404);
        }

        $video->updated_by = Auth::id();
        $video->save();
        $video->delete();

        return response()->json(['success' => true, 'message' => 'Video deleted.']);
    }

    /* ── Private helpers ────────────────────────────────────────── */

    private function videoPayload(Video $video): array
    {
        return [
            'id'          => $video->id,
            'unique_key'  => $video->unique_key,
            'title'       => $video->title,
            'tags'        => $video->tags ?? [],
            'tags_string' => $video->tagsAsString(),
            'status'      => $video->processing_status,
            'error'       => $video->processing_error,
            'duration'    => $video->formattedDuration(),
            'size_human'  => $video->formattedSize(),
            'qualities'   => ($video->relationLoaded('qualities') ? $video->qualities : collect())->map(fn ($q) => [
                'quality'    => $q->quality,
                'size_bytes' => $q->size_bytes,
            ])->values(),
            'created_at'  => $video->created_at?->format('Y-m-d H:i'),
            'updated_at'  => $video->updated_at?->format('Y-m-d H:i'),
        ];
    }

    private function datatableRow(Video $video): array
    {
        $dotMap = [
            Video::STATUS_PENDING    => ['label' => 'Pending',    'class' => 'vl-pending'],
            Video::STATUS_PROCESSING => ['label' => 'Processing', 'class' => 'vl-processing'],
            Video::STATUS_ACTIVE     => ['label' => 'Active',     'class' => 'vl-active'],
            Video::STATUS_FAILED     => ['label' => 'Failed',     'class' => 'vl-failed'],
        ];
        $sm = $dotMap[$video->processing_status] ?? ['label' => ucfirst($video->processing_status), 'class' => ''];

        $uk     = e($video->unique_key ?? '');
        $copyJs = "navigator.clipboard.writeText('" . addslashes($video->unique_key ?? '') . "');var e=this;e.style.color='#16a34a';setTimeout(function(){e.style.color='';},1200);";

        $idHtml = '<div style="line-height:1.4;">'
            . '<span style="font-family:ui-monospace,monospace;font-size:.75rem;color:var(--text-muted);">#' . $video->id . '</span><br>'
            . '<span onclick="' . $copyJs . '" style="font-family:ui-monospace,monospace;font-size:.68rem;color:var(--primary);cursor:pointer;white-space:nowrap;" title="Click to copy">' . $uk . '</span>'
            . '</div>';

        $thumbUrl = $video->thumbnail_url;
        $thumb    = $thumbUrl
            ? '<img src="' . e($thumbUrl) . '" alt="thumb" style="width:100px;height:56px;object-fit:cover;border-radius:6px;">'
            : '<div style="width:100px;height:56px;border-radius:6px;background:linear-gradient(135deg,rgba(139,26,26,.06),rgba(139,26,26,.15));display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.3rem;"><i class="bi bi-film"></i></div>';

        $titleHtml = '<span style="font-weight:600;">' . e($video->title) . '</span>';
        if ($video->processing_error) {
            $titleHtml .= '<br><small class="text-danger" title="' . e($video->processing_error) . '">' . e(mb_substr($video->processing_error, 0, 60)) . '…</small>';
        }

        $tagsHtml = '';
        if (is_array($video->tags) && count($video->tags)) {
            foreach ($video->tags as $t) {
                $tagsHtml .= '<span class="vl-tag">' . e($t) . '</span>';
            }
        } else {
            $tagsHtml = '<span class="text-muted small">—</span>';
        }

        $statusHtml = '<span class="vl-status ' . $sm['class'] . '" data-role="status" data-video-id="' . $video->id . '">'
            . '<span class="dot"></span>' . $sm['label'] . '</span>';

        $duration = $video->duration_seconds ? e($video->formattedDuration()) : '<span class="text-muted">—</span>';
        $size     = $video->file_size_bytes   ? e($video->formattedSize())     : '<span class="text-muted">—</span>';

        $qual = $video->qualities->count()
            ? $video->qualities->pluck('quality')
                ->map(fn ($q) => '<span style="font-size:.65rem;background:rgba(139,26,26,.1);color:var(--primary);padding:2px 7px;border-radius:4px;font-weight:600;">' . e(strtoupper($q)) . '</span>')
                ->implode(' ')
            : '<span class="text-muted small">—</span>';

        $actions = '<div class="d-flex gap-1 justify-content-center">';
        if ($video->isActive()) {
            $previewUrl = route('admin.video-library.preview', $video->id);
            $actions .= '<a href="' . e($previewUrl) . '" class="btn btn-sm btn-icon vl-btn-play" title="Watch video" target="_blank">'
                . '<i class="bi bi-play-fill" style="font-size:.85rem;"></i></a>';
            $actions .= '<button type="button" class="btn btn-sm btn-icon vl-btn-edit" data-id="' . $video->id . '" title="Edit video">'
                . '<i class="bi bi-pencil-square" style="font-size:.85rem;"></i></button>';
            if ($video->processing_status == 'active' && auth()->user()?->can('video-library-download')) {
                $downloadUrl = route('admin.video-library.download', $video->id);
                $actions .= '<a href="' . e($downloadUrl) . '" class="btn btn-sm btn-icon vl-btn-dl" title="Download original">'
                    . '<i class="bi bi-download" style="font-size:.85rem;"></i></a>';
            }
        }
        if (! $video->trashed()) {
            $actions .= '<button type="button" class="btn btn-sm btn-icon vl-btn-del" data-id="' . $video->id . '" data-title="' . e($video->title) . '" title="Delete video">'
                . '<i class="bi bi-trash" style="font-size:.85rem;"></i></button>';
        }
        $actions .= '</div>';

        return [$idHtml, $thumb, $titleHtml, $tagsHtml, $statusHtml, $duration, $size, $qual, $actions];
    }

    private function parseBitrateToInt(string $bitrate): int
    {
        $raw = trim(strtolower($bitrate));

        if (str_ends_with($raw, 'k')) {
            return (int) $raw * 1_000;
        }
        if (str_ends_with($raw, 'm')) {
            return (int) $raw * 1_000_000;
        }

        return (int) $raw;
    }
}
