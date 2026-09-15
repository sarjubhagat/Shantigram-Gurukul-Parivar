<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\PersonalInfo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BirthdayController extends AdminController
{
    public function index()
    {
        return view('admin.birthday.index', [
            'today' => now()->format('Y-m-d'),
        ]);
    }

    public function get_data(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $date = Carbon::parse(request('date') ?: now()->toDateString())
            ->timezone(config('app.timezone', 'Asia/Kolkata'))
            ->startOfDay();

        $month = (int) $date->month;
        $day   = (int) $date->day;
        $isToday = $date->isSameDay(now());

        $rows = PersonalInfo::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $month)
            ->whereDay('date_of_birth', $day)
            ->orderBy('en_first_name')
            ->orderBy('en_last_name')
            ->get()
            ->map(function (PersonalInfo $p) use ($date) {
                $dob = $p->date_of_birth;
                $age = $dob ? max(0, $date->year - (int) $dob->year) : null;

                return [
                    'id'            => $p->id,
                    'unique_id'     => $p->unique_id,
                    'full_name'     => $p->display_name ?: $p->englishFullName(),
                    'gu_full_name'  => $p->gujaratiFullName(),
                    'initials'      => $p->initials(),
                    'photo_url'     => $p->photoUrl(),
                    'phone'         => $p->primaryPhone() ?: '—',
                    'date_of_birth' => $dob?->format('d M Y'),
                    'dob_iso'       => $dob?->format('Y-m-d'),
                    'age'           => $age,
                    'native_place'  => $p->native_place ?: '—',
                    'edit_url'      => url('/admin/parivar/' . $p->id . '/edit'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $rows,
            'meta'    => [
                'date'          => $date->format('Y-m-d'),
                'date_label'    => $date->format('d M Y'),
                'is_today'      => $isToday,
                'count'         => $rows->count(),
                'month'         => $month,
                'day'           => $day,
            ],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not used.'], 501);
    }

    public function AddEditAction(int $id = null)
    {
        return redirect()->to(url('/admin/birthday'));
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        abort(404);
    }
}
