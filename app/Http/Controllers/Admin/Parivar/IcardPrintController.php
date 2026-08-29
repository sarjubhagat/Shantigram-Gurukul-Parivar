<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\IcardImage;
use App\Models\Parivar\PersonalInfo;
use App\Services\IcardPrintService;
use App\Services\IcardTraySettings;
use App\Services\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IcardPrintController extends AdminController
{
    public function index()
    {
        $tray = IcardTraySettings::all();

        return view('admin.icard-print.index', [
            'startRow'    => (int) $tray['start_row'],
            'startColumn' => (int) $tray['start_column'],
        ]);
    }

    public function get_data(): JsonResponse
    {
        $members = PersonalInfo::with(['businesses', 'gurukulRecords.branch'])
            ->latest()
            ->get()
            ->map(fn (PersonalInfo $p) => [
                'id'         => $p->id,
                'unique_id'  => $p->unique_id,
                'full_name'  => $p->englishFullName(),
                'gu_full_name' => $p->gujaratiFullName(),
                'initials'   => $p->initials(),
                'photo_url'  => $p->photoUrl(),
                'phone'      => $p->primaryPhone(),
                'native_place' => $p->native_place,
                'is_gurukul_student' => (bool) $p->is_gurukul_student,
                'branch'     => $p->gurukulRecords->sortByDesc('id')->first()?->branch?->branch_name,
                'has_photo'  => (bool) $p->photo_key,
            ]);

        return response()->json(['data' => $members]);
    }

    public function settings()
    {
        if (! auth()->user()->can('icard-print-edit')) {
            abort(403, 'You don\'t have permission to edit I-card settings.');
        }

        $tray  = IcardTraySettings::all();
        $front = IcardImage::where('image_key', 'FRONT')->first();
        $back  = IcardImage::where('image_key', 'BACK')->first();
        $colors = app(IcardPrintService::class)->sanitizeColors(
            is_array($front?->color_codes) ? $front->color_codes : []
        );

        return view('admin.icard-print.settings', compact('tray', 'front', 'back', 'colors'));
    }

    public function saveSettings(Request $request): JsonResponse
    {
        if (! auth()->user()->can('icard-print-edit')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $maxKb = (int) config('icard.storage.bg_max_kb', 30720);

        $validator = Validator::make($request->all(), [
            'academic_year'   => 'nullable|string|max:40',
            'margin_top'      => 'required|numeric',
            'margin_left'     => 'required|numeric',
            'cal_top'         => 'required|numeric',
            'cal_left'        => 'required|numeric',
            'page_shift_top'  => 'required|numeric',
            'page_shift_left' => 'required|numeric',
            'start_row'       => 'required|integer|min:1|max:5',
            'start_column'    => 'required|integer|min:1|max:2',
            'front_bg'        => 'nullable|file|image|mimes:jpeg,jpg,png|max:' . $maxKb,
            'back_bg'         => 'nullable|file|image|mimes:jpeg,jpg,png|max:' . $maxKb,
            'colors'          => 'nullable|array',
            'colors.*'        => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        IcardTraySettings::save([
            'academic_year'   => $data['academic_year'] ?? '',
            'margin_top'      => $data['margin_top'],
            'margin_left'     => $data['margin_left'],
            'cal_top'         => $data['cal_top'],
            'cal_left'        => $data['cal_left'],
            'page_shift_top'  => $data['page_shift_top'],
            'page_shift_left' => $data['page_shift_left'],
            'start_row'       => $data['start_row'],
            'start_column'    => $data['start_column'],
        ]);

        $front = IcardImage::firstOrCreate(['image_key' => 'FRONT']);
        $back  = IcardImage::firstOrCreate(['image_key' => 'BACK']);

        $front->color_codes = app(IcardPrintService::class)->sanitizeColors($data['colors'] ?? []);
        $front->save();

        $r2     = app(R2StorageService::class);
        $folder = trim((string) config('icard.storage.folder', 'icard-images'), '/');

        if ($request->hasFile('front_bg')) {
            $old = $front->image_path;
            $stored = $r2->upload($request->file('front_bg'), $folder, 'FRONT_' . now()->format('YmdHis'));
            $front->update(['image_path' => $stored['path']]);
            if ($old && $old !== $stored['path']) {
                $r2->delete($old);
            }
        }

        if ($request->hasFile('back_bg')) {
            $old = $back->image_path;
            $stored = $r2->upload($request->file('back_bg'), $folder, 'BACK_' . now()->format('YmdHis'));
            $back->update(['image_path' => $stored['path']]);
            if ($old && $old !== $stored['path']) {
                $r2->delete($old);
            }
        }

        return response()->json(['success' => true, 'message' => 'I-card settings saved.']);
    }

    public function printCards(Request $request, IcardPrintService $service)
    {
        $ids = collect($request->input('member_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $startRow = (int) $request->input('start_row', IcardTraySettings::get('start_row', 1));
        $startCol = (int) $request->input('start_column', IcardTraySettings::get('start_column', 1));

        if ($ids->isEmpty()) {
            abort(422, 'Select at least one member to print.');
        }

        if ($ids->count() > 10) {
            abort(422, 'A tray holds at most 10 cards.');
        }

        $available = IcardTraySettings::availableSlots($startRow, $startCol);
        if ($ids->count() > $available) {
            abort(422, 'Too many members for this start slot. Available: ' . $available . '.');
        }

        $found = PersonalInfo::whereIn('id', $ids)->count();
        if ($found !== $ids->count()) {
            abort(422, 'One or more selected members were not found.');
        }

        $cards = $service->buildCards($ids->all());
        $slots = IcardTraySettings::mapCardsToSlots($cards, $startRow, $startCol);
        $tray  = IcardTraySettings::resolveForPrint();
        $phys  = config('icard.tray');

        return view('admin.icard-print.print', compact('slots', 'tray', 'phys', 'startRow', 'startCol'));
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Use Settings to manage I-cards.'], 501);
    }

    public function DeleteRecord(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not implemented.'], 501);
    }

    public function AddEditAction(int $id = null)
    {
        return redirect()->to(url('/admin/icard-print/settings'));
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        abort(404);
    }
}
