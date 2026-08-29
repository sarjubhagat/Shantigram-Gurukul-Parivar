<?php

namespace App\Http\Controllers\Admin\ContentLibrary;

use App\Http\Controllers\Admin\AdminController;
use App\Jobs\ProcessAudioJob;
use App\Models\ContentLibrary\Audio;
use App\Services\Audio\AudioTusUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global audio library — upload once, reference unique_key (audio_xxx…) from any module.
 * Playback uses HLS proxied through /stream/* routes.
 */
class AudioLibraryController extends AdminController
{
    /* ── AdminController abstract contract ─────────────────────── */

    public function index(): View
    {
        return view('admin.audio-library.index');
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

        $rows  = Audio::getRecordList($filterArr, false);
        $total = Audio::getRecordList($filterArr, true);
        $grand = Audio::getRecordList($baseFilter, true);

        return response()->json([
            'draw'            => (int) $request->input('draw', 0),
            'recordsTotal'    => (int) $grand,
            'recordsFiltered' => (int) $total,
            'data'            => $rows->map(fn ($row) => $this->datatableRow($row))->values(),
        ]);
    }

    // Not used — audio library uses TUS upload, not a standard modal form.
    public function AddEditAction(int $id = null)
    {
        abort(404);
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        abort(404);
    }

    // Standard create route not used — upload via TUS + /complete endpoint.
    public function create(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Use the TUS upload endpoint.'], 501);
    }

    /* ── Single-record fetch (edit modal pre-fill) ──────────────── */

    public function show(Request $request, int $id): JsonResponse
    {
        $audio = Audio::find($id);

        if (! $audio) {
            return response()->json(['status' => false, 'message' => 'Audio not found.'], 404);
        }

        return response()->json(['status' => true, 'data' => $this->audioPayload($audio)]);
    }

    /* ── TUS upload handler ─────────────────────────────────────── */

    public function tus(Request $request): Response
    {
        $prefix = (string) config('audio.tus.route_prefix', 'admin/audio-library/tus');

        return app(AudioTusUploadService::class)->handle($request, $prefix);
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

        $tus = app(AudioTusUploadService::class);

        if (! $tus->isUploadComplete($data['tus_key'])) {
            return response()->json(['ok' => false, 'message' => 'Upload not finished yet — retry in a moment.'], 422);
        }

        $inputPath = $tus->uploadFilePath($data['tus_key']);
        if (! $inputPath || ! is_file($inputPath)) {
            return response()->json(['ok' => false, 'message' => 'Uploaded file is missing on the server.'], 422);
        }

        $ext     = strtolower(pathinfo($data['file_name'] ?? $inputPath, PATHINFO_EXTENSION));
        $allowed = (array) config('audio.accepted_formats', ['mp3', 'aac', 'm4a', 'wav', 'flac']);
        if (! in_array($ext, $allowed, true)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Unsupported format "' . $ext . '". Allowed: ' . implode(', ', $allowed),
            ], 422);
        }

        $audio = Audio::create([
            'title'             => $data['title'],
            'tags'              => Audio::normalizeTagsInput($data['tags'] ?? null),
            'file_size_bytes'   => $data['file_size'] ?? (is_file($inputPath) ? filesize($inputPath) : null),
            'processing_status' => Audio::STATUS_PENDING,
            'sort_order'        => 0,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]);

        try {
            $stagedPath = $tus->stageForPipeline($inputPath, $audio->id, $data['file_name'] ?? null);
        } catch (\Throwable $e) {
            $audio->update([
                'processing_status' => Audio::STATUS_FAILED,
                'processing_error'  => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        ProcessAudioJob::dispatch($audio->id, $stagedPath);

        return response()->json([
            'ok'         => true,
            'audio_id'   => $audio->id,
            'unique_key' => $audio->unique_key,
            'status'     => $audio->processing_status,
        ]);
    }

    /* ── Status poll ────────────────────────────────────────────── */

    public function audioStatus(Request $request, int $id): JsonResponse
    {
        $audio = Audio::find($id);

        if (! $audio) {
            return response()->json(['status' => 'error', 'message' => 'Audio not found.'], 404);
        }

        return response()->json([
            'id'       => $audio->id,
            'status'   => $audio->processing_status,
            'error'    => $audio->processing_error,
            'duration' => $audio->formattedDuration(),
            'size'     => $audio->formattedSize(),
        ]);
    }

    /* ── Update title / tags (+ optional file replacement) ──────── */

    public function update(Request $request, int $id): JsonResponse
    {
        $audio = Audio::find($id);

        if (! $audio) {
            return response()->json(['status' => false, 'message' => 'Audio not found.'], 404);
        }

        if (! $audio->isActive()) {
            return response()->json(['status' => false, 'message' => 'Only active tracks can be edited.'], 422);
        }

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'tags'      => 'nullable|string|max:5000',
            'tus_key'   => 'nullable|string',
            'file_name' => 'nullable|string|max:255',
        ]);

        $tus       = app(AudioTusUploadService::class);
        $tusKey    = $data['tus_key'] ?? null;
        $replace   = false;
        $inputPath = null;

        if ($tusKey) {
            if (! $tus->isUploadComplete($tusKey)) {
                return response()->json(['status' => false, 'message' => 'New file upload not finished yet.'], 422);
            }
            $inputPath = $tus->uploadFilePath($tusKey);
            if (! $inputPath || ! is_file($inputPath)) {
                return response()->json(['status' => false, 'message' => 'Replacement file missing on server.'], 422);
            }
            $replace = true;
        }

        $audio->update([
            'title'             => $data['title'],
            'tags'              => Audio::normalizeTagsInput($data['tags'] ?? null),
            'updated_by'        => Auth::id(),
            'processing_status' => $replace ? Audio::STATUS_PENDING : $audio->processing_status,
        ]);

        if ($replace && $inputPath) {
            try {
                $stagedPath = $tus->stageForPipeline($inputPath, $audio->id, $data['file_name'] ?? null);
            } catch (\Throwable $e) {
                $audio->update([
                    'processing_status' => Audio::STATUS_FAILED,
                    'processing_error'  => $e->getMessage(),
                ]);

                return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
            }

            ProcessAudioJob::dispatch($audio->id, $stagedPath);
        }

        return response()->json([
            'status'  => true,
            'message' => $replace
                ? 'Track queued for re-processing. Title and tags updated.'
                : 'Track updated successfully.',
            'data'    => $this->audioPayload($audio->fresh()),
        ]);
    }

    /* ── Preview page ───────────────────────────────────────────── */

    public function preview(int $id): View
    {
        $audio = Audio::findOrFail($id);

        return view('admin.audio-library.show', compact('audio'));
    }

    /* ── HLS master playlist ────────────────────────────────────── */

    public function masterPlaylist(Request $request, int $id): Response
    {
        $audio = Audio::find($id);

        if (! $audio || ! $audio->isActive() || ! $audio->audio_key) {
            abort(404);
        }

        $lines = [
            '#EXTM3U',
            '#EXT-X-STREAM-INF:BANDWIDTH=128000,CODECS="mp4a.40.2",NAME="audio"',
            'playlist.m3u8',
        ];

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'application/vnd.apple.mpegurl')
            ->header('Cache-Control', 'private, no-cache, no-store');
    }

    /* ── HLS proxy (variant playlist + TS segments) ─────────────── */

    public function stream(Request $request, int $id, string $file): Response
    {
        $audio = Audio::find($id);

        if (! $audio || ! $audio->isActive() || ! $audio->audio_key) {
            abort(404);
        }

        $base  = rtrim(dirname($audio->audio_key), '/');
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
        $audio = Audio::find($id);

        if (! $audio || ! $audio->audio_key || ! $audio->original_format) {
            abort(404, 'Original file not available for download.');
        }

        $ext         = '.' . strtolower($audio->original_format);
        $filename    = preg_replace('/[^\w\s\-.]/', '', $audio->title) . $ext;
        $originalKey = rtrim(dirname($audio->audio_key), '/') . '/original' . $ext;

        try {
            $url = Storage::disk('r2')->temporaryUrl(
                $originalKey,
                now()->addMinutes(5),
                ['ResponseContentDisposition' => 'attachment; filename="' . $filename . '"']
            );
        } catch (\Throwable $e) {
            abort(500, 'Download unavailable.');
        }

        return redirect()->away($url);
    }

    /* ── Delete (bulk via standard dynamic route, single via ids) ── */

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No records selected.'], 422);
        }

        $audios = Audio::whereIn('id', $ids)->get();
        $count  = 0;

        foreach ($audios as $audio) {
            $audio->updated_by = Auth::id();
            $audio->save();
            $audio->delete();
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' track' . ($count === 1 ? '' : 's') . ' deleted.',
        ]);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        $audio = Audio::find($id);

        if (! $audio) {
            return response()->json(['success' => false, 'message' => 'Audio not found.'], 404);
        }

        $audio->updated_by = Auth::id();
        $audio->save();
        $audio->delete();

        return response()->json(['success' => true, 'message' => 'Track deleted.']);
    }

    /* ── Private helpers ────────────────────────────────────────── */

    private function audioPayload(Audio $audio): array
    {
        return [
            'id'              => $audio->id,
            'unique_key'      => $audio->unique_key,
            'title'           => $audio->title,
            'tags'            => $audio->tags ?? [],
            'tags_string'     => $audio->tagsAsString(),
            'status'          => $audio->processing_status,
            'error'           => $audio->processing_error,
            'original_format' => $audio->original_format,
            'was_converted'   => $audio->was_converted,
            'duration'        => $audio->formattedDuration(),
            'size_human'      => $audio->formattedSize(),
            'created_at'      => $audio->created_at?->format('Y-m-d H:i'),
            'updated_at'      => $audio->updated_at?->format('Y-m-d H:i'),
        ];
    }

    private function datatableRow(Audio $audio): array
    {
        $dotMap = [
            Audio::STATUS_PENDING    => ['label' => 'Pending',    'class' => 'al-pending'],
            Audio::STATUS_PROCESSING => ['label' => 'Processing', 'class' => 'al-processing'],
            Audio::STATUS_ACTIVE     => ['label' => 'Active',     'class' => 'al-active'],
            Audio::STATUS_FAILED     => ['label' => 'Failed',     'class' => 'al-failed'],
        ];
        $sm = $dotMap[$audio->processing_status] ?? ['label' => ucfirst($audio->processing_status), 'class' => ''];

        $uk     = e($audio->unique_key ?? '');
        $copyJs = "navigator.clipboard.writeText('" . addslashes($audio->unique_key ?? '') . "');var e=this;e.style.color='#16a34a';setTimeout(function(){e.style.color='';},1200);";

        $idHtml = '<div style="line-height:1.4;">'
            . '<span style="font-family:ui-monospace,monospace;font-size:.75rem;color:var(--text-muted);">#' . $audio->id . '</span><br>'
            . '<span onclick="' . $copyJs . '" style="font-family:ui-monospace,monospace;font-size:.68rem;color:var(--primary);cursor:pointer;white-space:nowrap;" title="Click to copy">' . $uk . '</span>'
            . '</div>';

        $fmt = strtoupper($audio->original_format ?? '');
        $fmtBadge = $audio->was_converted
            ? '<span title="Transcoded from ' . $fmt . ' to HLS" style="font-size:.64rem;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;margin-left:4px;">' . $fmt . '→HLS</span>'
            : '<span style="font-size:.64rem;background:rgba(139,26,26,.08);color:var(--primary);padding:1px 5px;border-radius:4px;">' . $fmt . '</span>';

        $titleHtml = '<span style="font-weight:600;">' . e($audio->title) . '</span> ' . $fmtBadge;
        if ($audio->processing_error) {
            $titleHtml .= '<br><small class="text-danger" title="' . e($audio->processing_error) . '">' . e(mb_substr($audio->processing_error, 0, 60)) . '…</small>';
        }

        $tagsHtml = '';
        if (is_array($audio->tags) && count($audio->tags)) {
            foreach ($audio->tags as $t) {
                $tagsHtml .= '<span class="al-tag">' . e($t) . '</span>';
            }
        } else {
            $tagsHtml = '<span class="text-muted small">—</span>';
        }

        $statusHtml = '<span class="al-status ' . $sm['class'] . '" data-role="status" data-audio-id="' . $audio->id . '">'
            . '<span class="dot"></span>' . $sm['label'] . '</span>';

        $duration = $audio->duration_seconds ? e($audio->formattedDuration()) : '<span class="text-muted">—</span>';
        $size     = $audio->file_size_bytes  ? e($audio->formattedSize())     : '<span class="text-muted">—</span>';

        $actions = '<div class="d-flex gap-1 justify-content-center">';
        if ($audio->isActive()) {
            $previewUrl = route('admin.audio-library.preview', $audio->id);
            $actions .= '<a href="' . e($previewUrl) . '" class="btn btn-sm btn-icon al-btn-play" title="Play audio" target="_blank">'
                . '<i class="bi bi-play-fill" style="font-size:.85rem;"></i></a>';
            $actions .= '<button type="button" class="btn btn-sm btn-icon al-btn-edit" data-id="' . $audio->id . '" title="Edit track">'
                . '<i class="bi bi-pencil-square" style="font-size:.85rem;"></i></button>';
            if ($audio->audio_key && auth()->user()?->can('audio-library-download')) {
                $downloadUrl = route('admin.audio-library.download', $audio->id);
                $actions .= '<a href="' . e($downloadUrl) . '" class="btn btn-sm btn-icon al-btn-dl" title="Download original">'
                    . '<i class="bi bi-download" style="font-size:.85rem;"></i></a>';
            }
        }
        if (! $audio->trashed()) {
            $actions .= '<button type="button" class="btn btn-sm btn-icon al-btn-del" data-id="' . $audio->id . '" data-title="' . e($audio->title) . '" title="Delete track">'
                . '<i class="bi bi-trash" style="font-size:.85rem;"></i></button>';
        }
        $actions .= '</div>';

        return [$idHtml, $titleHtml, $tagsHtml, $statusHtml, $duration, $size, $actions];
    }
}
