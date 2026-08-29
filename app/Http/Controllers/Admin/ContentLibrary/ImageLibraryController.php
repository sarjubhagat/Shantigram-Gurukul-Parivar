<?php

namespace App\Http\Controllers\Admin\ContentLibrary;

use App\Http\Controllers\Admin\AdminController;
use App\Models\ContentLibrary\Image;
use App\Services\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Image library — uploads to R2, gallery UI with AJAX pagination, Laravel soft deletes.
 */
class ImageLibraryController extends AdminController
{
    public function index(): View
    {
        return view('admin.image-library.index');
    }

    public function get_data(): JsonResponse
    {
        return response()->json([
            'draw'            => (int) request()->input('draw', 0),
            'recordsTotal'    => 0,
            'recordsFiltered' => 0,
            'data'            => [],
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

    /**
     * Paginated gallery JSON (does not load entire library at once).
     */
    public function gallery(Request $request): JsonResponse
    {
        $default = (int) config('image.gallery.default_per_page', 24);
        $max     = (int) config('image.gallery.max_per_page', 60);
        $perPage = min(max((int) $request->input('per_page', $default), 1), $max);
        $page    = max((int) $request->input('page', 1), 1);

        $filter = [
            'search'       => trim((string) $request->input('q', '')),
            'with_deleted' => $request->boolean('with_deleted'),
        ];

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords()) {
            $filter['created_by'] = $user->id;
        }

        $q = Image::query()->galleryFilter($filter);

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);
        $ttl       = (int) config('image.storage.signed_url_ttl', 120);
        $r2        = app(R2StorageService::class);

        $items = $paginator->getCollection()->map(function (Image $img) use ($r2, $ttl) {
            return [
                'id'                => $img->id,
                'title'             => $img->title,
                'unique_key'        => $img->unique_key,
                'url'               => $r2->signedUrl($img->image_key, $ttl),
                'width'             => $img->width,
                'height'            => $img->height,
                'size_human'        => $img->formattedSize(),
                'tags'              => $img->tags ?? [],
                'alt_text'          => $img->alt_text,
                'original_filename' => $img->original_filename,
                'upload_batch_id'   => $img->upload_batch_id,
                'sort_order'        => $img->sort_order,
                'is_deleted'        => $img->trashed(),
                'updated_at'        => $img->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data'         => $items,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]);
    }

    /**
     * All selectable image IDs for the current gallery filter (active images only).
     */
    public function galleryIds(Request $request): JsonResponse
    {
        $cap = 100;

        $filter = [
            'search' => trim((string) $request->input('q', '')),
        ];

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords()) {
            $filter['created_by'] = $user->id;
        }

        $baseQuery = Image::query()->galleryFilter($filter);
        $total     = (clone $baseQuery)->count();

        $ids = (clone $baseQuery)
            ->limit($cap)
            ->pluck('id')
            ->values()
            ->all();

        return response()->json([
            'ids'    => $ids,
            'total'  => $total,
            'capped' => $total > $cap,
            'cap'    => $cap,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $image = Image::find($id);

        if (! $image) {
            return response()->json(['status' => false, 'message' => 'Image not found.'], 404);
        }

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords() && (int) $image->created_by !== (int) $user->id) {
            return response()->json(['status' => false, 'message' => 'Image not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->payload($image),
        ]);
    }

    /**
     * Bulk upload: one shared title (and tags) for every file in the request; same upload_batch_id.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $maxFiles = (int) config('image.bulk.max_files', 40);
        $data     = $request->validate([
            'title'   => 'required|string|max:255',
            'tags'    => 'nullable|string|max:5000',
            'files'   => 'required|array|min:1|max:' . $maxFiles,
            'files.*' => 'required|file|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
        ]);

        $tags    = Image::normalizeTagsInput($data['tags'] ?? null);
        $batchId = (string) Str::uuid();
        $folder  = trim((string) config('image.storage.folder', 'library-images'), '/');
        $r2      = app(R2StorageService::class);
        $uid     = Auth::id();

        $created = [];
        $errors  = [];

        foreach ($request->file('files', []) as $idx => $file) {
            if (! $file || ! $file->isValid()) {
                $errors[] = ['index' => $idx, 'message' => 'Invalid upload.'];

                continue;
            }

            try {
                $info = @getimagesize($file->getRealPath());
                $w    = is_array($info) ? (int) ($info[0] ?? 0) : null;
                $h    = is_array($info) ? (int) ($info[1] ?? 0) : null;

                $stored = $r2->upload($file, $folder);

                $image = Image::create([
                    'upload_batch_id'   => $batchId,
                    'title'             => $data['title'],
                    'tags'              => $tags,
                    'alt_text'          => null,
                    'image_key'         => $stored['path'],
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type'         => $stored['mime'] ?? $file->getMimeType(),
                    'width'             => $w ?: null,
                    'height'            => $h ?: null,
                    'file_size_bytes'   => $stored['size'] ?? $file->getSize(),
                    'processing_status' => Image::STATUS_ACTIVE,
                    'sort_order'        => 0,
                    'created_by'        => $uid,
                    'updated_by'        => $uid,
                ]);

                $created[] = ['id' => $image->id, 'unique_key' => $image->unique_key];
            } catch (\Throwable $e) {
                $errors[] = ['index' => $idx, 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'ok'       => count($created) > 0,
            'created'  => $created,
            'errors'   => $errors,
            'batch_id' => $batchId,
            'message'  => count($created) . ' image(s) uploaded' . (count($errors) ? ' (' . count($errors) . ' failed).' : '.'),
        ], count($created) > 0 ? 200 : 422);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $image = Image::find($id);

        if (! $image || ! $image->isActive()) {
            return response()->json(['status' => false, 'message' => 'Image not found.'], 404);
        }

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords() && (int) $image->created_by !== (int) $user->id) {
            return response()->json(['status' => false, 'message' => 'Image not found.'], 404);
        }

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'tags'       => 'nullable|string|max:5000',
            'alt_text'   => 'nullable|string|max:500',
            'sort_order' => 'nullable|numeric',
            'file'       => 'nullable|file|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
        ]);

        $folder = trim((string) config('image.storage.folder', 'library-images'), '/');
        $r2     = app(R2StorageService::class);

        $image->update([
            'title'      => $data['title'],
            'tags'       => Image::normalizeTagsInput($data['tags'] ?? null),
            'alt_text'   => $data['alt_text'] ?? null,
            'sort_order' => $data['sort_order'] ?? $image->sort_order,
            'updated_by' => Auth::id(),
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $old  = $image->image_key;

            $info = @getimagesize($file->getRealPath());
            $w    = is_array($info) ? (int) ($info[0] ?? 0) : null;
            $h    = is_array($info) ? (int) ($info[1] ?? 0) : null;

            $stored = $r2->upload($file, $folder);

            $image->update([
                'image_key'         => $stored['path'],
                'original_filename' => $file->getClientOriginalName(),
                'mime_type'         => $stored['mime'] ?? $file->getMimeType(),
                'width'             => $w ?: null,
                'height'            => $h ?: null,
                'file_size_bytes'   => $stored['size'] ?? $file->getSize(),
            ]);

            if ($old && $old !== $image->image_key) {
                $r2->delete($old);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Image updated.',
            'data'    => $this->payload($image->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $image = Image::find($id);

        if (! $image) {
            return response()->json(['ok' => false, 'message' => 'Image not found.'], 404);
        }

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords() && (int) $image->created_by !== (int) $user->id) {
            return response()->json(['ok' => false, 'message' => 'Image not found.'], 404);
        }

        $image->updated_by = Auth::id();
        $image->save();
        $image->delete();

        return response()->json(['ok' => true, 'message' => 'Image deleted.']);
    }

    public function DeleteRecord(): JsonResponse
    {
        return $this->bulkDestroy(request());
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:images,id',
        ]);

        $query = Image::whereIn('id', $data['ids']);
        $user  = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords()) {
            $query->where('created_by', $user->id);
        }

        $count = 0;
        foreach ($query->get() as $image) {
            $image->updated_by = Auth::id();
            $image->save();
            $image->delete();
            $count++;
        }

        return response()->json([
            'ok'      => $count > 0,
            'message' => $count . ' image' . ($count === 1 ? '' : 's') . ' deleted.',
        ]);
    }

    public function download(Request $request, int $id): Response
    {
        $image = $this->findAccessibleImage($id);

        if (! $image || ! $image->image_key) {
            abort(404, 'Image not available for download.');
        }

        try {
            $url = Storage::disk('r2')->temporaryUrl(
                $image->image_key,
                now()->addMinutes(5),
                ['ResponseContentDisposition' => 'attachment; filename="' . $this->downloadFilename($image) . '"']
            );
        } catch (\Throwable) {
            abort(500, 'Download unavailable.');
        }

        return redirect()->away($url);
    }

    public function bulkDownload(Request $request): BinaryFileResponse
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:images,id',
        ]);

        $query = Image::whereIn('id', $data['ids']);
        $user  = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords()) {
            $query->where('created_by', $user->id);
        }

        $images = $query->get()->filter(fn (Image $img) => $img->image_key);

        if ($images->isEmpty()) {
            abort(404, 'No images available for download.');
        }

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/images-' . Str::uuid() . '.zip';
        $zip     = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create download archive.');
        }

        $disk      = Storage::disk('r2');
        $usedNames = [];
        $added     = 0;

        foreach ($images as $image) {
            try {
                $contents = $disk->get($image->image_key);
            } catch (\Throwable) {
                continue;
            }

            $name = $this->downloadFilename($image);
            if (isset($usedNames[$name])) {
                $usedNames[$name]++;
                $dot  = strrpos($name, '.');
                $base = $dot !== false ? substr($name, 0, $dot) : $name;
                $ext  = $dot !== false ? substr($name, $dot) : '';
                $name = $base . '-' . $usedNames[$name] . $ext;
            } else {
                $usedNames[$name] = 1;
            }

            if ($zip->addFromString($name, $contents)) {
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            abort(404, 'Could not read image files for download.');
        }

        return response()->download(
            $zipPath,
            'images-' . now()->format('Y-m-d-His') . '.zip'
        )->deleteFileAfterSend(true);
    }

    protected function payload(Image $image): array
    {
        $ttl = (int) config('image.storage.signed_url_ttl', 120);

        return [
            'id'                => $image->id,
            'unique_key'        => $image->unique_key,
            'title'             => $image->title,
            'tags'              => $image->tags ?? [],
            'tags_string'       => $image->tagsAsString(),
            'alt_text'          => $image->alt_text,
            'sort_order'        => $image->sort_order,
            'width'             => $image->width,
            'height'            => $image->height,
            'size_human'        => $image->formattedSize(),
            'original_filename' => $image->original_filename,
            'url'               => app(R2StorageService::class)->signedUrl($image->image_key, $ttl),
            'download_url'      => route('admin.' . $this->moduleName . '.download', $image->id),
        ];
    }

    private function findAccessibleImage(int $id): ?Image
    {
        $image = Image::find($id);

        if (! $image) {
            return null;
        }

        $user = Auth::user();
        if ($user && ! $user->canViewAllLibraryRecords() && (int) $image->created_by !== (int) $user->id) {
            return null;
        }

        return $image;
    }

    private function downloadFilename(Image $image): string
    {
        if ($image->original_filename) {
            $name = basename((string) $image->original_filename);
            if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $name)) {
                return preg_replace('/[^\w\s\-.]/', '_', $name) ?: 'image.jpg';
            }
        }

        $mime = strtolower((string) $image->mime_type);
        $ext  = match (true) {
            str_contains($mime, 'png')  => 'png',
            str_contains($mime, 'gif')  => 'gif',
            str_contains($mime, 'webp') => 'webp',
            default                     => 'jpg',
        };

        $base = trim(preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $image->title) ?: 'image', '._-');

        return ($base !== '' ? $base : 'image') . '.' . $ext;
    }
}
