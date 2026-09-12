<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Update Details — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --gp-crimson: #8B1A1A;
            --gp-crimson-deep: #6D1313;
            --gp-gold: #C9A227;
            --gp-ink: #2A1414;
            --gp-muted: #7A5A5A;
            --gp-paper: #FBF6F2;
            --gp-line: #E8D6D0;
            --gp-r: 14px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Source Sans 3', sans-serif;
            color: var(--gp-ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(201,162,39,.18), transparent 55%),
                radial-gradient(900px 420px at 100% 0%, rgba(139,26,26,.12), transparent 50%),
                linear-gradient(180deg, #FFF9F5 0%, var(--gp-paper) 45%, #F3E8E2 100%);
        }
        .mu-shell { width: min(920px, calc(100% - 1.5rem)); margin: 0 auto; padding: 1.5rem 0 3rem; }
        .mu-brand {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(1.7rem, 4vw, 2.4rem);
            font-weight: 700;
            color: var(--gp-crimson-deep);
            letter-spacing: -.02em;
            margin: 0;
        }
        .mu-lead { color: var(--gp-muted); margin: .35rem 0 1.25rem; max-width: 36rem; }
        .mu-card {
            background: rgba(255,255,255,.92);
            border: 1px solid var(--gp-line);
            border-radius: var(--gp-r);
            box-shadow: 0 18px 40px rgba(109,19,19,.07);
            padding: 1.25rem 1.35rem;
        }
        .mu-step-title { font-weight: 700; font-size: 1.05rem; margin-bottom: .85rem; }
        .form-label { font-size: .84rem; font-weight: 600; color: var(--gp-ink); }
        .form-control, .form-select {
            border: 1.5px solid var(--gp-line);
            border-radius: 10px;
            padding: .65rem .8rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: rgba(139,26,26,.45);
            box-shadow: 0 0 0 .2rem rgba(139,26,26,.12);
        }
        .btn-mu {
            background: linear-gradient(135deg, var(--gp-crimson) 0%, var(--gp-crimson-deep) 100%);
            color: #fff; border: 0; border-radius: 999px;
            padding: .65rem 1.2rem; font-weight: 700;
        }
        .btn-mu:hover { color: #fff; filter: brightness(1.05); }
        .btn-mu-light {
            background: #fff; color: var(--gp-crimson-deep);
            border: 1.5px solid var(--gp-line); border-radius: 999px;
            padding: .55rem 1rem; font-weight: 650;
        }
        .mu-hint {
            display: inline-flex; gap: .4rem; align-items: center;
            background: rgba(201,162,39,.14); color: #6B4E0E;
            border-radius: 999px; padding: .25rem .7rem; font-size: .78rem; font-weight: 600;
        }
        .mu-member-pick {
            display: flex; gap: .85rem; align-items: center; width: 100%;
            border: 1.5px solid var(--gp-line); background: #fff;
            border-radius: 12px; padding: .8rem; text-align: left; margin-bottom: .55rem;
            cursor: pointer;
        }
        .mu-member-pick:hover { border-color: rgba(139,26,26,.4); }
        .mu-avatar {
            width: 48px; height: 48px; border-radius: 50%; overflow: hidden;
            background: #F0E2DC; display: grid; place-items: center; flex-shrink: 0;
            font-weight: 700; color: var(--gp-crimson);
        }
        .mu-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .mu-sec {
            font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
            color: var(--gp-crimson); border-bottom: 1px solid var(--gp-line);
            padding-bottom: .35rem; margin: 1.1rem 0 .8rem;
        }
        .mu-readonly {
            border: 1px dashed var(--gp-line); border-radius: 12px; padding: .8rem .9rem;
            background: #FCF8F5; font-size: .88rem;
        }
        .mu-note.gu-note {
            background: #FFF8EF;
            border: 1px solid #F0DFC0;
            border-radius: 12px;
            padding: .75rem .9rem;
            font-size: .9rem;
            line-height: 1.55;
            color: #5C3D18;
        }
        input[readonly] {
            background: #F5EDEA;
            color: #5A3E3E;
            cursor: default;
        }
        .mu-topbar { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: 1rem; }
        .mu-block {
            border: 1.5px solid var(--gp-line);
            border-radius: 12px;
            padding: .85rem .9rem;
            margin-bottom: .65rem;
            background: #fff;
        }
        .mu-block-head {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: .55rem;
            font-weight: 700; font-size: .86rem;
        }
        .mu-saint-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .35rem .55rem;
            margin-top: .35rem;
        }
        .mu-saint-grid label { font-size: .82rem; display: flex; gap: .35rem; align-items: center; margin: 0; }
        .mu-photo-card {
            border: 1.5px solid var(--gp-line);
            border-radius: 12px;
            padding: .75rem;
            background: #fff;
            height: 100%;
        }
        .mu-photo-label { font-size: .78rem; font-weight: 700; margin-bottom: .45rem; color: var(--gp-crimson); }
        .mu-photo-frame {
            width: 100%;
            aspect-ratio: 3.5 / 4.5;
            max-width: 180px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            background: #F3E8E2;
            border: 1px solid var(--gp-line);
            display: grid;
            place-items: center;
        }
        .mu-photo-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mu-photo-empty { color: var(--gp-muted); font-size: .82rem; font-weight: 600; }
        .mu-photo-ref { max-width: 180px; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="mu-shell" id="muApp"
     data-send-otp="{{ route('member-update.send_otp') }}"
     data-verify-otp="{{ route('member-update.verify_otp') }}"
     data-select-member="{{ route('member-update.select_member') }}"
     data-profile="{{ route('member-update.profile') }}"
     data-submit="{{ route('member-update.submit') }}"
     data-logout="{{ route('member-update.logout') }}"
     data-test-mode="{{ $testMode ? '1' : '0' }}"
     data-branches='@json($branches)'
     data-years='@json($years)'>

    <div class="mu-topbar">
        <div>
            <p class="mu-brand">Gurukul Parivar</p>
            <p class="mu-lead">Enter your registered mobile number to view and request updates to your details.</p>
        </div>
        @if($testMode)
            <span class="mu-hint"><i class="bi bi-flask"></i> Test OTP: 12345</span>
        @endif
    </div>

    {{-- Step 1: phone --}}
    <div class="mu-card" id="stepPhone">
        <div class="mu-step-title">1. Mobile number</div>
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="phoneInput">Registered mobile</label>
                <input type="tel" id="phoneInput" class="form-control" placeholder="e.g. 9876543210" maxlength="20" inputmode="numeric">
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-mu w-100" id="btnSendOtp">
                    <span class="txt">Get OTP</span>
                    <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Sending…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Step 2: OTP --}}
    <div class="mu-card d-none mt-3" id="stepOtp">
        <div class="mu-step-title">2. Enter OTP</div>
        <p class="text-muted small mb-2">Verification code sent to <strong id="otpSentPhone"></strong></p>
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="otpInput">OTP</label>
                <input type="text" id="otpInput" class="form-control" placeholder="{{ $testMode ? '12345' : 'Enter OTP' }}" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-mu w-100" id="btnVerifyOtp">
                    <span class="txt">Verify</span>
                    <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Checking…</span>
                </button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" id="btnChangePhone"><i class="bi bi-arrow-left"></i> Change number</button>
            <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" id="btnResendOtp"><i class="bi bi-arrow-repeat"></i> Resend OTP</button>
        </div>
    </div>

    {{-- Step 2b: pick member if multiple --}}
    <div class="mu-card d-none mt-3" id="stepPick">
        <div class="mu-step-title">Choose your profile</div>
        <div id="memberPickList"></div>
    </div>

    {{-- Step 3: details --}}
    <div class="mu-card d-none mt-3" id="stepProfile">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
                <div class="mu-step-title mb-1">Your details</div>
                <div class="text-muted" style="font-size:.86rem;" id="profileMeta"></div>
            </div>
            <button type="button" class="btn btn-mu-light btn-sm" id="btnLogout">Sign out</button>
        </div>

        <div id="pendingBanner" class="alert alert-warning py-2 px-3 d-none" style="font-size:.86rem;">
            You already have a pending update request. New changes cannot be submitted until admin reviews it.
        </div>

        <form id="profileForm" novalidate autocomplete="off" enctype="multipart/form-data">
            <div class="mu-sec">Passport photo <span class="text-danger">*</span></div>
            <div class="mu-note gu-note mb-3">
                Passport-size photo અપલોડ કરવો ફરજિયાત છે. નીચેના reference જેવો સ્પષ્ટ, સામેની તરફ, સાદા background સાથેનો ફોટો અપલોડ કરો.
            </div>
            <div class="row g-3 align-items-start mb-2">
                <div class="col-md-4">
                    <div class="mu-photo-card">
                        <div class="mu-photo-label">Your photo</div>
                        <div class="mu-photo-frame" id="photoFrame">
                            <img id="photoPreview" src="" alt="Profile" class="d-none">
                            <div id="photoEmpty" class="mu-photo-empty">No photo</div>
                        </div>
                        <input type="file" name="photo" id="photoInput" class="d-none" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <button type="button" class="btn btn-mu-light btn-sm w-100 mt-2" id="btnPickPhoto">
                            <i class="bi bi-camera me-1"></i><span id="photoBtnText">Upload photo</span>
                        </button>
                        <div class="form-text mt-1" id="photoHelp">JPEG / PNG / WebP · max {{ round(((int) config('parivar.photo.max_kb', 2048)) / 1024, 1) }} MB</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mu-photo-card">
                        <div class="mu-photo-label">Reference (correct type)</div>
                        <div class="mu-photo-frame mu-photo-ref">
                            <img src="{{ asset('assets/member-update/passport-reference.png') }}" alt="Passport reference">
                        </div>
                        <div class="form-text mt-2">આ જ પ્રકારનો passport-size ફોટો અપલોડ કરો.</div>
                    </div>
                </div>
            </div>

            <div class="mu-sec">English name</div>
            <div class="mu-note gu-note mb-3">
                અટક, નામ અને પિતાનું નામ આંતરિક (internal) ઉપયોગ માટે છે — અહીં સીધું બદલી શકાતું નથી. આ નામમાં પાછળ ભાઈ, કુમાર કે લાલ એવું હશે નહીં.
                છાપકામ / I-card માટે, અન્ય બીજી જગ્યાએ <strong>Display name</strong> વાપરો. જો અટક / નામ / પિતાનું નામ બદલવું હોય તો નીચેના નોંધ બોક્સમાં લખો.
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Surname (અટક)</label>
                    <input type="text" name="en_last_name" id="en_last_name" class="form-control" readonly tabindex="-1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Name (નામ)</label>
                    <input type="text" name="en_first_name" id="en_first_name" class="form-control" readonly tabindex="-1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father name (પિતાનું નામ)</label>
                    <input type="text" name="en_middle_name" id="en_middle_name" class="form-control" readonly tabindex="-1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Grandfather name</label>
                    <input type="text" name="en_grandfather_name" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label">Display name (printing) — વ્યાપક ઉપયોગ / છાપકામ</label>
                    <input type="text" name="display_name" class="form-control" placeholder="e.g. PATEL RAJESH RAMESH BHAI">
                    <div class="form-text">આ નામ I-card અને અન્ય છાપકામમાં વપરાશે.</div>
                </div>
                
                <div class="col-12">
                    <label class="form-label">નામ / અન્ય ફેરફારની વિનંતી (નોંધ)</label>
                    <textarea name="member_note" id="member_note" class="form-control" rows="4"
                              placeholder="જો અટક, નામ, પિતાનું નામ અથવા અન્ય કંઈ બદલવું હોય તો અહીં વિગત લખો…"></textarea>
                </div>
            </div>

            <div class="mu-sec">Gujarati name</div>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">અટક</label><input type="text" name="gu_last_name" class="form-control" lang="gu"></div>
                <div class="col-md-3"><label class="form-label">નામ</label><input type="text" name="gu_first_name" class="form-control" lang="gu"></div>
                <div class="col-md-3"><label class="form-label">પિતાનું નામ</label><input type="text" name="gu_middle_name" class="form-control" lang="gu"></div>
                <div class="col-md-3"><label class="form-label">દાદાનું નામ</label><input type="text" name="gu_grandfather_name" class="form-control" lang="gu"></div>
            </div>

            <div class="mu-sec">Identity & contact</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Aadhaar number</label>
                    <input type="text" name="aadhaar_number" id="aadhaar_number" class="form-control"
                           placeholder="XXXX-XXXX-XXXX" inputmode="numeric" maxlength="14" autocomplete="off">
                    <div id="aadhaarAutoHint" class="mu-note gu-note mt-2 d-none">
                        આ Aadhaar number <strong>0000</strong> થી શરૂ થાય છે — આ auto-generated છે.
                        કૃપા કરીને તમારો <strong>અસલ (actual) Aadhaar</strong> નંબર અહીં લખો.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">—</option>
                        @foreach($genders as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marital status</label>
                    <select name="marital_status" class="form-select">
                        <option value="">—</option>
                        @foreach($maritals as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Primary phone</label>
                    <div class="input-group">
                        <select name="c_code_1" class="form-select" style="max-width:120px;">
                            @foreach($codes as $code => $label)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="phone_1" class="form-control" maxlength="20">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secondary phone</label>
                    <div class="input-group">
                        <select name="c_code_2" class="form-select" style="max-width:120px;">
                            <option value="">—</option>
                            @foreach($codes as $code => $label)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="phone_2" class="form-control" maxlength="20">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Native place</label>
                    <input type="text" name="native_place" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Current address (સંપૂર્ણ Address લખવું - પીનકોડ સાથે)</label>
                    <textarea name="current_address" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="mu-sec">Business</div>
            <div id="bizList"></div>
            <button type="button" class="btn btn-mu-light btn-sm mt-2" id="btnAddBiz"><i class="bi bi-plus-lg me-1"></i>Add business</button>

            <div class="mu-sec">Gurukul</div>
            <div class="form-check mb-3">
                <input type="hidden" name="is_gurukul_student" value="0">
                <input class="form-check-input" type="checkbox" name="is_gurukul_student" value="1" id="is_gurukul_student">
                <label class="form-check-label" for="is_gurukul_student">Gurukul student / was a Gurukul student</label>
            </div>
            <div id="gkList"></div>
            <button type="button" class="btn btn-mu-light btn-sm mt-2" id="btnAddGk"><i class="bi bi-plus-lg me-1"></i>Add Gurukul record</button>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-mu" id="btnSubmit">
                    <span class="txt">Submit update request</span>
                    <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Submitting…</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function () {
    var $app = $('#muApp');
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var phone = '';
    var member = null;
    var hasExistingPhoto = false;
    var newPhotoSelected = false;
    var branches = $app.data('branches') || [];
    var years = $app.data('years') || [];
    var bizIndex = 0;
    var gkIndex = 0;

    function setBusy($btn, on) {
        $btn.prop('disabled', on);
        $btn.find('.txt').toggleClass('d-none', on);
        $btn.find('.load').toggleClass('d-none', !on);
    }

    function showStep(id) {
        $('#stepPhone, #stepOtp, #stepPick, #stepProfile').addClass('d-none');
        $(id).removeClass('d-none');
    }

    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c];
        });
    }

    function formatAadhaar(raw) {
        var digits = String(raw || '').replace(/\D+/g, '').slice(0, 12);
        var parts = [];
        if (digits.length > 0) parts.push(digits.slice(0, 4));
        if (digits.length > 4) parts.push(digits.slice(4, 8));
        if (digits.length > 8) parts.push(digits.slice(8, 12));
        return parts.join('-');
    }

    function yearOptions(selected) {
        var html = '<option value="">—</option>';
        years.forEach(function (y) {
            html += '<option value="' + y + '"' + (String(selected) === String(y) ? ' selected' : '') + '>' + y + '</option>';
        });
        return html;
    }

    function branchOptions(selected) {
        var html = '<option value="">Select branch</option>';
        branches.forEach(function (b) {
            html += '<option value="' + b.id + '"' + (String(selected) === String(b.id) ? ' selected' : '') + '>' + esc(b.branch_name) + '</option>';
        });
        return html;
    }

    function addBizRow(data) {
        data = data || {};
        var i = bizIndex++;
        var html = '<div class="mu-block" data-biz>'
            + '<div class="mu-block-head"><span>Business</span><button type="button" class="btn btn-link btn-sm text-danger p-0 btn-remove-biz">Remove</button></div>'
            + '<input type="hidden" name="businesses[' + i + '][id]" value="' + esc(data.id || '') + '">'
            + '<div class="mb-2"><label class="form-label">Business name</label>'
            + '<input type="text" class="form-control" name="businesses[' + i + '][business_name]" value="' + esc(data.business_name || '') + '"></div>'
            + '<div><label class="form-label">Business address</label>'
            + '<textarea class="form-control" rows="2" name="businesses[' + i + '][business_address]">' + esc(data.business_address || '') + '</textarea></div>'
            + '</div>';
        $('#bizList').append(html);
    }

    function addGkRow(data) {
        data = data || {};
        var i = gkIndex++;
        var saintsText = data.saints_text || ((data.saints || []).join(', '));
        var html = '<div class="mu-block" data-gk>'
            + '<div class="mu-block-head"><span>Gurukul record</span><button type="button" class="btn btn-link btn-sm text-danger p-0 btn-remove-gk">Remove</button></div>'
            + '<input type="hidden" name="gurukul[' + i + '][id]" value="' + esc(data.id || '') + '">'
            + '<div class="row g-2">'
            + '<div class="col-md-6"><label class="form-label">Branch</label><select class="form-select" name="gurukul[' + i + '][branch_id]">' + branchOptions(data.branch_id) + '</select></div>'
            + '<div class="col-md-3"><label class="form-label">Start year</label><select class="form-select" name="gurukul[' + i + '][start_year]">' + yearOptions(data.start_year) + '</select></div>'
            + '<div class="col-md-3"><label class="form-label">End year</label><select class="form-select" name="gurukul[' + i + '][end_year]">' + yearOptions(data.end_year) + '</select></div>'
            + '<div class="col-12"><label class="form-label">Connected saints</label>'
            + '<input type="text" class="form-control" name="gurukul[' + i + '][saints_text]" value="' + esc(saintsText) + '" placeholder="e.g. Swami Name, Another Swami">'
            + '<div class="form-text">સંતનું નામ લખો. એકથી વધુ હોય તો comma (,) થી અલગ કરો.</div></div>'
            + '</div></div>';
        $('#gkList').append(html);
    }

    function fillForm(m) {
        member = m;
        var p = m.personal || {};
        Object.keys(p).forEach(function (k) {
            var $el = $('#profileForm [name="' + k + '"]');
            if (!$el.length) return;
            if (k === 'aadhaar_number') {
                $el.val(formatAadhaar(p[k] || m.aadhaar_formatted || ''));
            } else if (k === 'is_gurukul_student') {
                $('#is_gurukul_student').prop('checked', !!p[k]);
            } else if ($el.is(':checkbox')) {
                $el.prop('checked', !!p[k]);
            } else {
                $el.val(p[k] == null ? '' : p[k]);
            }
        });
        $('#member_note').val('');
        $('#profileMeta').text('Member #' + m.unique_id + (m.full_name ? ' · ' + m.full_name : ''));
        $('#pendingBanner').toggleClass('d-none', !m.pending_update);
        $('#btnSubmit').prop('disabled', !!m.pending_update);
        updateAadhaarHint();
        setPhotoPreview(m.photo_url || '');
        hasExistingPhoto = !!m.photo_url;
        newPhotoSelected = false;
        $('#photoInput').val('');
        $('#photoBtnText').text(hasExistingPhoto ? 'Replace photo' : 'Upload photo');

        $('#bizList').empty();
        bizIndex = 0;
        (m.businesses || []).forEach(function (b) { addBizRow(b); });
        if (!(m.businesses || []).length) addBizRow();

        $('#gkList').empty();
        gkIndex = 0;
        (m.gurukul || []).forEach(function (g) { addGkRow(g); });
        if (!(m.gurukul || []).length) addGkRow();
    }

    function setPhotoPreview(url) {
        if (url) {
            $('#photoPreview').attr('src', url).removeClass('d-none');
            $('#photoEmpty').addClass('d-none');
        } else {
            $('#photoPreview').attr('src', '').addClass('d-none');
            $('#photoEmpty').removeClass('d-none');
        }
    }

    function updateAadhaarHint() {
        var digits = String($('#aadhaar_number').val() || '').replace(/\D+/g, '');
        $('#aadhaarAutoHint').toggleClass('d-none', !(digits.length >= 4 && digits.slice(0, 4) === '0000'));
    }

    $('#aadhaar_number').on('input', function () {
        this.value = formatAadhaar(this.value);
        updateAadhaarHint();
    });
    $('#btnPickPhoto').on('click', function () { $('#photoInput').trigger('click'); });
    $('#photoInput').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        newPhotoSelected = true;
        var reader = new FileReader();
        reader.onload = function (e) { setPhotoPreview(e.target.result); };
        reader.readAsDataURL(file);
        $('#photoBtnText').text('Change photo');
    });
    $('#btnAddBiz').on('click', function () { addBizRow(); });
    $('#btnAddGk').on('click', function () { addGkRow(); });
    $(document).on('click', '.btn-remove-biz', function () {
        $(this).closest('[data-biz]').remove();
        if (!$('#bizList [data-biz]').length) addBizRow();
    });
    $(document).on('click', '.btn-remove-gk', function () {
        $(this).closest('[data-gk]').remove();
        if (!$('#gkList [data-gk]').length) addGkRow();
    });

    function doSendOtp($btn) {
        phone = String($('#phoneInput').val() || '').replace(/\D+/g, '');
        if (phone.length < 6) {
            Swal.fire({ icon: 'error', title: 'Invalid number', text: 'Enter a valid mobile number.' });
            return;
        }
        setBusy($btn, true);
        $.ajax({
            url: $app.data('send-otp'),
            type: 'POST',
            data: { _token: csrf, phone: phone },
            success: function (r) {
                Swal.fire({ icon: 'success', title: 'OTP Sent', text: r.message, timer: 2200, showConfirmButton: false });
                $('#otpSentPhone').text($('#phoneInput').val().trim());
                showStep('#stepOtp');
                $('#otpInput').val($app.data('test-mode') == 1 ? '12345' : '').focus();
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Could not send OTP.' });
            },
            complete: function () { setBusy($btn, false); }
        });
    }

    $('#btnSendOtp').on('click', function () {
        doSendOtp($(this));
    });

    $('#btnResendOtp').on('click', function () {
        doSendOtp($(this));
    });

    $('#phoneInput').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnSendOtp').click();
        }
    });

    $('#otpInput').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnVerifyOtp').click();
        }
    });

    $('#btnChangePhone').on('click', function () {
        showStep('#stepPhone');
        $('#phoneInput').focus();
    });

    $('#btnVerifyOtp').on('click', function () {
        var otp = String($('#otpInput').val() || '').trim();
        var $btn = $(this);
        setBusy($btn, true);
        $.ajax({
            url: $app.data('verify-otp'),
            type: 'POST',
            data: { _token: csrf, phone: phone, otp: otp },
            success: function (r) {
                var members = (r.data && r.data.members) || [];
                if (members.length === 1) {
                    fillForm(members[0]);
                    showStep('#stepProfile');
                } else {
                    var html = '';
                    members.forEach(function (m) {
                        html += '<button type="button" class="mu-member-pick" data-id="' + m.id + '">'
                            + '<span class="mu-avatar">' + (m.photo_url ? '<img src="' + esc(m.photo_url) + '" alt="">' : esc((m.full_name || '?').charAt(0))) + '</span>'
                            + '<span><strong>' + esc(m.full_name || 'Member') + '</strong><div class="text-muted" style="font-size:.8rem;">#' + esc(String(m.unique_id)) + '</div></span>'
                            + '</button>';
                    });
                    $('#memberPickList').html(html);
                    showStep('#stepPick');
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'OTP verification failed.' });
            },
            complete: function () { setBusy($btn, false); }
        });
    });

    $('#memberPickList').on('click', '.mu-member-pick', function () {
        var id = $(this).data('id');
        $.ajax({
            url: $app.data('select-member'),
            type: 'POST',
            data: { _token: csrf, member_id: id },
            success: function (r) {
                fillForm(r.data.member);
                showStep('#stepProfile');
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Could not open profile.' });
            }
        });
    });

    $('#profileForm').on('submit', function (e) {
        e.preventDefault();
        if (member && member.pending_update) return;

        if (!hasExistingPhoto && !newPhotoSelected) {
            Swal.fire({
                icon: 'warning',
                title: 'Passport photo required',
                text: 'Please upload a passport-size photo like the reference image.',
            });
            return;
        }

        var $btn = $('#btnSubmit');
        setBusy($btn, true);
        var formData = new FormData(this);
        formData.set('_token', csrf);
        if (!$('#is_gurukul_student').is(':checked')) {
            formData.set('is_gurukul_student', '0');
        }

        $.ajax({
            url: $app.data('submit'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (r) {
                Swal.fire({ icon: 'success', title: 'Submitted', text: r.message });
                member.pending_update = true;
                $('#pendingBanner').removeClass('d-none');
                $('#btnSubmit').prop('disabled', true);
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Could not submit.' });
            },
            complete: function () { setBusy($btn, false); }
        });
    });

    $('#btnLogout').on('click', function () {
        $.post($app.data('logout'), { _token: csrf }).always(function () {
            member = null;
            phone = '';
            $('#phoneInput').val('');
            $('#otpInput').val('');
            showStep('#stepPhone');
        });
    });
})();
</script>
</body>
</html>
