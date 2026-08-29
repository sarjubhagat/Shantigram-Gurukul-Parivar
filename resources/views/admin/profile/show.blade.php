@extends('admin.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('breadcrumb')
    <li class="breadcrumb-item active">My Profile</li>
@endsection

@section('content')

<div class="row g-4 align-items-start">

    {{-- ═══════════════════════════════════════════════════════
         LEFT — Profile identity card
         ═══════════════════════════════════════════════════════ --}}
    <div class="col-lg-4">
        <div class="card pf-id-card">

            <div class="pf-banner">
                <div class="pf-avatar">
                    {{ strtoupper(mb_substr($user->name ?? 'U', 0, 2)) }}
                </div>
            </div>

            <div class="card-body pf-id-body text-center">

                <h5 class="pf-name">{{ $user->name }}</h5>
                <div class="pf-email-txt">{{ $user->email }}</div>

                <div class="pf-roles mt-2 mb-4">
                    @forelse($user->roles as $role)
                        <span class="pf-role-chip">{{ $role->name }}</span>
                    @empty
                        <span style="font-size:.8rem;color:var(--text-muted);">No roles assigned</span>
                    @endforelse
                </div>

                <div class="pf-divider"></div>

                <div class="pf-meta-list">
                    <div class="pf-meta-row">
                        <span class="pf-meta-icon"><i class="bi bi-telephone"></i></span>
                        <span>{{ $user->number ?: '—' }}</span>
                    </div>
                    <div class="pf-meta-row">
                        <span class="pf-meta-icon"><i class="bi bi-calendar3"></i></span>
                        <span>Joined {{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="pf-meta-row">
                        @if($user->is_active)
                            <span class="pf-meta-icon" style="color:#22c55e;"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i></span>
                            <span style="color:#22c55e;font-weight:600;">Active</span>
                        @else
                            <span class="pf-meta-icon" style="color:#ef4444;"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i></span>
                            <span style="color:#ef4444;font-weight:600;">Inactive</span>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         RIGHT — Change password
         ═══════════════════════════════════════════════════════ --}}
    <div class="col-lg-8">

        @if(session('password_success'))
            <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss mb-3" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span>{{ session('password_success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card pf-form-card">

            <div class="pf-form-head">
                <span class="pf-form-icon"><i class="bi bi-lock-fill"></i></span>
                <span class="pf-form-title">Change Password</span>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.profile.password') }}" novalidate>
                    @csrf

                    <div class="row g-3">

                        <div class="col-sm-6">
                            <label for="newPwd" class="pf-label">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="pf-pwd-wrap">
                                <input type="password"
                                       class="form-control pf-input pf-pwd-inp @error('password', 'password') is-invalid @enderror"
                                       id="newPwd" name="password"
                                       placeholder="Min. 8 characters" autocomplete="new-password">
                                <button type="button" class="pf-eye-btn" data-target="newPwd" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password', 'password')
                                <div class="pf-field-err">{{ $message }}</div>
                            @enderror
                            <div class="pf-strength-wrap d-none" id="strengthWrap">
                                <div class="pf-strength-track">
                                    <div class="pf-strength-bar" id="strengthBar"></div>
                                </div>
                                <span class="pf-strength-lbl" id="strengthLbl"></span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label for="cfmPwd" class="pf-label">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="pf-pwd-wrap">
                                <input type="password"
                                       class="form-control pf-input pf-pwd-inp"
                                       id="cfmPwd" name="password_confirmation"
                                       placeholder="Repeat new password" autocomplete="new-password">
                                <button type="button" class="pf-eye-btn" data-target="cfmPwd" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="pf-field-err d-none" id="cfmErr">Passwords do not match.</div>
                        </div>

                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary pf-btn">
                            <i class="bi bi-shield-check me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Profile identity card ───────────────────────────────── */
.pf-id-card {
    border-radius: var(--r-lg);
    box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05);
    border: 1.5px solid var(--border-light);
    overflow: hidden;
}
.pf-banner {
    height: 100px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
    display: flex; align-items: flex-end; justify-content: center;
}
.pf-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    background: #fff; color: var(--primary);
    font-size: 1.7rem; font-weight: 800; letter-spacing: -.5px;
    display: flex; align-items: center; justify-content: center;
    border: 3px solid rgba(255,255,255,.5);
    box-shadow: 0 4px 18px rgba(0,0,0,.18);
    transform: translateY(40px);
}
.pf-id-body { padding: 2.8rem 1.5rem 1.5rem; }
.pf-name    { font-size: 1.05rem; font-weight: 700; color: var(--text); margin-bottom: .2rem; }
.pf-email-txt { font-size: .82rem; color: var(--text-muted); }

.pf-roles { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: center; }
.pf-role-chip {
    font-size: .72rem; font-weight: 600; padding: .22em .75em;
    background: rgba(139,26,26,.09); color: var(--primary);
    border-radius: 2rem; border: 1.5px solid rgba(139,26,26,.15);
}

.pf-divider { height: 1.5px; background: var(--border-light); margin: 0 0 .9rem; }

.pf-meta-list { text-align: left; }
.pf-meta-row {
    display: flex; align-items: center; gap: .65rem;
    padding: .48rem 0;
    border-bottom: 1px solid var(--border-light);
    font-size: .83rem; color: var(--text-2);
}
.pf-meta-row:last-child { border-bottom: none; }
.pf-meta-icon { width: 22px; text-align: center; flex-shrink: 0; color: var(--text-muted); }

/* ── Form card ───────────────────────────────────────────── */
.pf-form-card {
    border-radius: var(--r-lg);
    box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05);
    border: 1.5px solid var(--border-light);
    overflow: hidden;
}
.pf-form-head {
    display: flex; align-items: center; gap: .55rem;
    padding: .82rem 1.25rem;
    border-bottom: 1.5px solid var(--border-light);
    background: var(--bg);
}
.pf-form-icon {
    width: 28px; height: 28px;
    background: rgba(139,26,26,.09); border-radius: var(--r-sm);
    display: inline-flex; align-items: center; justify-content: center;
    color: var(--primary); font-size: .88rem; flex-shrink: 0;
}
.pf-form-title { font-size: .9rem; font-weight: 700; color: var(--text); }

.pf-label {
    display: block;
    font-size: .8rem; font-weight: 700; color: var(--text); margin-bottom: .35rem;
}
.pf-input {
    font-size: .875rem;
    border: 1.5px solid var(--border); border-radius: var(--r-sm);
    transition: border-color .2s, box-shadow .2s;
}
.pf-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(139,26,26,.1);
    outline: none;
}
.pf-btn { font-size: .875rem; font-weight: 600; min-width: 140px; }

/* ── Password field ──────────────────────────────────────── */
.pf-pwd-wrap { position: relative; }
.pf-pwd-inp  { padding-right: 2.6rem !important; }
.pf-eye-btn {
    position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
    background: none; border: none; padding: .2rem .35rem;
    color: var(--text-muted); cursor: pointer; font-size: .9rem;
    transition: color .15s; line-height: 1;
}
.pf-eye-btn:hover { color: var(--primary); }

/* ── Strength indicator ──────────────────────────────────── */
.pf-strength-wrap { margin-top: .45rem; display: flex; align-items: center; gap: .55rem; }
.pf-strength-track {
    flex: 1; height: 4px; background: var(--border-light);
    border-radius: 2rem; overflow: hidden;
}
.pf-strength-bar { height: 4px; border-radius: 2rem; width: 0%; transition: width .3s, background .3s; }
.pf-strength-lbl { font-size: .73rem; font-weight: 600; white-space: nowrap; }

.pf-field-err { font-size: .77rem; color: #dc3545; margin-top: .3rem; }

@media (max-width: 991.98px) {
    .pf-banner { height: 80px; }
    .pf-avatar { width: 68px; height: 68px; font-size: 1.4rem; transform: translateY(34px); }
    .pf-id-body { padding-top: 2.4rem; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {

    // ── Show / hide password ────────────────────────────────
    $(document).on('click', '.pf-eye-btn', function () {
        var inp = $('#' + $(this).data('target'));
        var ico = $(this).find('i');
        var isHidden = inp.attr('type') === 'password';
        inp.attr('type', isHidden ? 'text' : 'password');
        ico.toggleClass('bi-eye', !isHidden).toggleClass('bi-eye-slash', isHidden);
    });

    // ── Strength meter ──────────────────────────────────────
    $('#newPwd').on('input', function () {
        var val  = this.value;
        var wrap = $('#strengthWrap');
        if (!val) { wrap.addClass('d-none'); return; }
        wrap.removeClass('d-none');

        var score, color, label;
        if      (val.length < 8)  { score = 25;  color = '#ef4444'; label = 'Too weak';    }
        else if (val.length < 12) { score = 55;  color = '#f97316'; label = 'Moderate';    }
        else if (val.length < 16) { score = 80;  color = '#eab308'; label = 'Strong';      }
        else                      { score = 100; color = '#22c55e'; label = 'Very strong'; }

        $('#strengthBar').css({ width: score + '%', background: color });
        $('#strengthLbl').text(label).css('color', color);
    });

    // ── Confirm match hint ──────────────────────────────────
    $('#cfmPwd').on('input', function () {
        var noMatch = this.value && this.value !== $('#newPwd').val();
        var match   = this.value && this.value === $('#newPwd').val();
        $(this).toggleClass('is-invalid', !!noMatch).toggleClass('is-valid', !!match);
        $('#cfmErr').toggleClass('d-none', !noMatch);
    });

})();
</script>
@endpush
