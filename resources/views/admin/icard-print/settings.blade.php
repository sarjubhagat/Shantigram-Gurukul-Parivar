@extends('admin.layouts.app')

@section('title', 'I-Card Settings')
@section('page-title', 'I-Card Settings')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/admin/icard-print') }}">I-Card Print</a></li>
    <li class="breadcrumb-item active">Settings</li>
@endsection

@section('page-actions')
    <a href="{{ url('/admin/icard-print') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
@endsection

@section('content')

@php
    $colorLabels = [
        'front_name_value'   => 'Front name',
        'back_mobile_label'  => 'Back phone label',
        'back_mobile_value'  => 'Back phone value',
        'back_address_label' => 'Back address label',
        'back_address_value' => 'Back address value',
    ];
@endphp

<form id="icardSettingsForm" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card uc-card mb-4">
                <div class="card-header uc-header"><strong>Card copy & tray</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Year text on card</label>
                        <input type="text" name="academic_year" class="form-control" value="{{ $tray['academic_year'] }}" placeholder="e.g. 2026-27">
                        <div class="form-text">Optional. Not printed on the current card layout.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">First card — distance from top (mm)</label>
                            <input type="number" step="0.1" name="margin_top" class="form-control" value="{{ $tray['margin_top'] }}">
                            <div class="form-text">Designed gap from the top of the tray. Usually 10.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">First card — distance from left (mm)</label>
                            <input type="number" step="0.1" name="margin_left" class="form-control" value="{{ $tray['margin_left'] }}">
                            <div class="form-text">Designed gap from the left of the tray. Usually 15.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fine-tune up / down (mm)</label>
                            <input type="number" step="0.1" name="cal_top" class="form-control" value="{{ $tray['cal_top'] }}">
                            <div class="form-text">Cards print too high → increase. Too low → decrease.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fine-tune left / right (mm)</label>
                            <input type="number" step="0.1" name="cal_left" class="form-control" value="{{ $tray['cal_left'] }}">
                            <div class="form-text">Cards print too far right → decrease. Too far left → increase.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Move whole sheet up / down (mm)</label>
                            <input type="number" step="0.1" name="page_shift_top" class="form-control" value="{{ $tray['page_shift_top'] }}">
                            <div class="form-text">Only if every card is shifted the same. Keep 0 normally.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Move whole sheet left / right (mm)</label>
                            <input type="number" step="0.1" name="page_shift_left" class="form-control" value="{{ $tray['page_shift_left'] }}">
                            <div class="form-text">Only if the printer driver offsets the full page. Keep 0 normally.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Start filling from row</label>
                            <select name="start_row" class="form-select">
                                @for($r = 1; $r <= 5; $r++)
                                    <option value="{{ $r }}" @selected((int)$tray['start_row'] === $r)>Row {{ $r }} (top is 1)</option>
                                @endfor
                            </select>
                            <div class="form-text">Skip upper rows if those slots were already printed.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Start filling from side</label>
                            <select name="start_column" class="form-select">
                                <option value="1" @selected((int)$tray['start_column'] === 1)>Left card</option>
                                <option value="2" @selected((int)$tray['start_column'] === 2)>Right card</option>
                            </select>
                            <div class="form-text">Skip the left card in that row if it was already printed.</div>
                        </div>
                    </div>
                    <p class="form-text mt-3 mb-0">Tray is 10 cards (5 rows × 2). After a test print, change only the two <strong>Fine-tune</strong> fields until cards sit in the slots.</p>
                </div>
            </div>

            <div class="card uc-card">
                <div class="card-header uc-header"><strong>Backgrounds (R2)</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Front background</label>
                        @if($front?->image_path)
                            <div class="mb-2"><img src="{{ $front->signedUrl() }}" alt="Front" style="max-width:220px;border-radius:8px;border:1px solid var(--border);"></div>
                        @endif
                        <input type="file" name="front_bg" class="form-control" accept="image/jpeg,image/png">
                        <div class="form-text">JPEG or PNG, up to 10 MB.</div>
                    </div>
                    <div>
                        <label class="form-label">Back background</label>
                        @if($back?->image_path)
                            <div class="mb-2"><img src="{{ $back->signedUrl() }}" alt="Back" style="max-width:220px;border-radius:8px;border:1px solid var(--border);"></div>
                        @endif
                        <input type="file" name="back_bg" class="form-control" accept="image/jpeg,image/png">
                        <div class="form-text">JPEG or PNG, up to 10 MB.</div>
                    </div>
                    <div class="invalid-feedback d-block" data-err="front_bg"></div>
                    <div class="invalid-feedback d-block" data-err="back_bg"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card uc-card">
                <div class="card-header uc-header"><strong>Text colors</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($colorLabels as $key => $label)
                            <div class="col-sm-6">
                                <label class="form-label">{{ $label }}</label>
                                <input type="color" name="colors[{{ $key }}]" class="form-control form-control-color w-100" value="{{ $colors[$key] ?? '#2D1A0E' }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
            <span class="txt"><i class="bi bi-check-lg me-1"></i>Save settings</span>
            <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
        </button>
    </div>
</form>

@endsection

@push('styles')
<style>
.uc-card { border-radius: var(--r-lg); box-shadow: 0 2px 12px rgba(139,26,26,.07); overflow: hidden; border: 1.5px solid var(--border-light); }
.uc-header { padding: .82rem 1.25rem; border-bottom: 1.5px solid var(--border-light); background: #fff; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var maxBytes = {{ (int) config('icard.storage.bg_max_kb', 10240) }} * 1024;

    function fileTooBig(input) {
        return input && input.files && input.files[0] && input.files[0].size > maxBytes;
    }

    $('#icardSettingsForm').on('submit', function (e) {
        e.preventDefault();
        $('[data-err]').text('');

        var oversized = [];
        if (fileTooBig(this.front_bg)) oversized.push('Front background must be 10 MB or smaller.');
        if (fileTooBig(this.back_bg)) oversized.push('Back background must be 10 MB or smaller.');
        if (oversized.length) {
            Swal.fire({ icon: 'error', title: 'File too large', text: oversized.join(' ') });
            return;
        }

        var $btn = $('#saveSettingsBtn');
        $btn.prop('disabled', true);
        $btn.find('.txt').addClass('d-none');
        $btn.find('.load').removeClass('d-none');
        $.ajax({
            url: '{{ route("admin.icard-print.save_settings") }}',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function (r) {
                Swal.fire({ icon: 'success', title: 'Saved', text: r.message, timer: 1800, showConfirmButton: false });
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                var errors = res.errors || {};
                Object.keys(errors).forEach(function (key) {
                    $('[data-err="' + key + '"]').text(errors[key][0] || '');
                });
                var first = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : (res.message || 'Could not save settings.');
                Swal.fire({ icon: 'error', title: 'Error', text: first });
            },
            complete: function () {
                $btn.prop('disabled', false);
                $btn.find('.txt').removeClass('d-none');
                $btn.find('.load').addClass('d-none');
            }
        });
    });
})();
</script>
@endpush
