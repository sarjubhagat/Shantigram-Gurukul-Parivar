@extends('admin.layouts.app')

@php
    $isEdit   = isset($member) && $member;
    $fullName = $isEdit ? $member->englishFullName() : 'New Member';
    $tab      = $tab ?? 'personal';
    $codes    = config('parivar.country_codes');
    $genders  = config('parivar.genders');
    $maritals = config('parivar.marital_statuses');
    $relations = $relations ?? collect();
    $old      = fn ($key, $fallback = '') => old($key, $isEdit ? ($member->{$key} ?? $fallback) : $fallback);
@endphp

@section('title', $isEdit ? 'Manage: ' . $fullName : 'Add Member')
@section('page-title', $isEdit ? 'Manage Member' : 'Add Member')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/admin/parivar') }}">Parivar</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? '#' . $member->unique_id : 'Create' }}</li>
@endsection

@section('page-actions')
    <a href="{{ url('/admin/parivar') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Members
    </a>
@endsection

@section('content')

<div class="pv-wrap" id="pvApp"
     data-has-member="{{ $isEdit ? '1' : '0' }}"
     data-member-id="{{ $isEdit ? $member->id : '' }}"
     data-unique-id="{{ $isEdit ? $member->unique_id : '' }}"
     data-tab="{{ $tab }}"
     data-create-url="{{ route('admin.parivar.create') }}"
     data-update-url="{{ $isEdit ? route('admin.parivar.update', $member->id) : '' }}"
     data-delete-url="{{ route('admin.parivar.delete') }}"
     data-generate-aadhaar-url="{{ route('admin.parivar.generate_aadhaar') }}"
     data-check-phone-url="{{ route('admin.parivar.check_phone') }}"
     data-search-members-url="{{ route('admin.parivar.search_members') }}"
     data-suggest-relations-url="{{ route('admin.parivar.suggest_relations') }}"
     data-edit-base="{{ url('/admin/parivar') }}">

    <div class="row g-4 align-items-start">

        {{-- ════════════ LEFT NAV ════════════ --}}
        <div class="col-lg-3 col-xl-3">
            <div class="pv-sticky">

                <div class="pv-profile">
                    @php $sidebarPhoto = $isEdit ? $member->photoUrl() : null; @endphp
                    <div class="pv-avatar{{ $sidebarPhoto ? ' has-photo' : '' }}" id="pvAvatar">
                        <img id="pvAvatarImg" src="{{ $sidebarPhoto }}" alt="" @if(! $sidebarPhoto) class="d-none" @endif>
                        <span id="pvAvatarInit" @if($sidebarPhoto) class="d-none" @endif>{{ $isEdit ? $member->initials() : '?' }}</span>
                    </div>
                    <div class="pv-profile-body">
                        <div class="pv-profile-name" id="pvName">{{ $isEdit ? $fullName : 'New member' }}</div>
                        <div class="pv-profile-meta">
                            @if($isEdit)
                                <span class="pv-uid" id="pvUid">#{{ $member->unique_id }}</span>
                            @else
                                <span class="pv-uid pv-uid-pending" id="pvUid">ID after save</span>
                            @endif
                        </div>
                    </div>
                </div>

                <nav class="pv-nav" id="pvNav">
                    <button type="button" class="pv-nav-item {{ $tab === 'personal' ? 'active' : '' }}" data-tab="personal">
                        <span class="pv-nav-ico"><i class="bi bi-person-vcard"></i></span>
                        <span class="pv-nav-text">
                            <span class="pv-nav-title">Personal Info</span>
                            <span class="pv-nav-sub">Name, phone, identity</span>
                        </span>
                        <span class="pv-nav-state is-ready"><i class="bi bi-check-circle-fill"></i></span>
                    </button>

                    <button type="button" class="pv-nav-item {{ $tab === 'business' ? 'active' : '' }}" data-tab="business" data-requires-member="1">
                        <span class="pv-nav-ico"><i class="bi bi-briefcase"></i></span>
                        <span class="pv-nav-text">
                            <span class="pv-nav-title">Business Info</span>
                            <span class="pv-nav-sub">Work and address</span>
                        </span>
                        <span class="pv-nav-state {{ $isEdit ? 'is-ready' : 'is-lock' }}">
                            <i class="bi {{ $isEdit ? 'bi-unlock' : 'bi-lock-fill' }}"></i>
                        </span>
                    </button>

                    <button type="button" class="pv-nav-item {{ $tab === 'gurukul' ? 'active' : '' }}" data-tab="gurukul" data-requires-member="1">
                        <span class="pv-nav-ico"><i class="bi bi-mortarboard"></i></span>
                        <span class="pv-nav-text">
                            <span class="pv-nav-title">Gurukul Info</span>
                            <span class="pv-nav-sub">Branch, years, saints</span>
                        </span>
                        <span class="pv-nav-state {{ $isEdit ? 'is-ready' : 'is-lock' }}">
                            <i class="bi {{ $isEdit ? 'bi-unlock' : 'bi-lock-fill' }}"></i>
                        </span>
                    </button>
                </nav>

                <div class="pv-hint" id="pvHint" style="{{ $isEdit ? 'display:none;' : '' }}">
                    <i class="bi bi-info-circle"></i>
                    Save personal info first. Business and Gurukul stay locked until the member exists.
                </div>

            </div>
        </div>

        {{-- ════════════ RIGHT PANELS ════════════ --}}
        <div class="col-lg-9 col-xl-9">

            {{-- PERSONAL --}}
            <section class="pv-panel {{ $tab === 'personal' ? 'is-open' : '' }}" data-panel="personal">
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span class="pv-hl">
                            <span class="pv-head-icon"><i class="bi bi-person-vcard"></i></span>
                            <span class="pv-head-title">Personal Information</span>
                        </span>
                        @if($isEdit)
                            <span class="pv-head-badge">Member #{{ $member->unique_id }}</span>
                        @endif
                    </div>

                    <form id="personalForm" class="pv-body" novalidate autocomplete="off" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="section" value="personal">
                        <input type="hidden" name="remove_photo" id="removePhoto" value="0">

                        <div class="pv-sec-label">Passport photo</div>
                        @php $memberPhoto = $isEdit ? $member->photoUrl() : null; @endphp
                        <div class="pv-photo-row mb-4">
                            <div class="pv-passport{{ $memberPhoto ? ' has-img' : '' }}" id="photoFrame">
                                <img id="photoPreviewImg" src="{{ $memberPhoto }}" alt="Passport photo" @if(! $memberPhoto) class="d-none" @endif>
                                <div id="photoPlaceholder" class="pv-passport-empty{{ $memberPhoto ? ' d-none' : '' }}">
                                    <i class="bi bi-person-bounding-box"></i>
                                    <span>3.5 × 4.5 cm</span>
                                </div>
                            </div>
                            <div class="pv-photo-meta">
                                <label class="form-label mb-1">Passport-size photo</label>
                                <div class="form-text mb-2" style="font-size:.74rem;">JPEG, PNG or WebP · max {{ round(((int) config('parivar.photo.max_kb', 2048)) / 1024, 1) }} MB · stored on R2</div>
                                <input type="file" name="photo" id="photoInput" class="d-none" accept="image/jpeg,image/jpg,image/png,image/webp">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-light btn-sm" id="btnPickPhoto">
                                        <i class="bi bi-upload me-1"></i>{{ $memberPhoto ? 'Replace photo' : 'Upload photo' }}
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger{{ $memberPhoto ? '' : ' d-none' }}" id="btnClearPhoto">
                                        <i class="bi bi-trash me-1"></i>Remove
                                    </button>
                                </div>
                                <div class="invalid-feedback d-block" data-err="photo"></div>
                            </div>
                        </div>

                        <div class="pv-sec-label">English name</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Surname<span class="text-danger">*</span></label>
                                <input type="text" name="en_last_name" id="en_last_name" class="form-control" value="{{ $old('en_last_name') }}" placeholder="Last name">
                                <div class="invalid-feedback" data-err="en_last_name"></div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="en_first_name" id="en_first_name" class="form-control" value="{{ $old('en_first_name') }}" placeholder="First name">
                                <div class="invalid-feedback" data-err="en_first_name"></div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Father Name</label>
                                <input type="text" name="en_middle_name" id="en_middle_name" class="form-control" value="{{ $old('en_middle_name') }}" placeholder="Middle name">
                            </div>
                           
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Grandfather name</label>
                                <input type="text" name="en_grandfather_name" id="en_grandfather_name" class="form-control" value="{{ $old('en_grandfather_name') }}" placeholder="Grandfather">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Display name <span class="pv-sec-note">used on I-card / print</span></label>
                                <input type="text" name="display_name" class="form-control" value="{{ $old('display_name') }}" placeholder="e.g. PATEL RAJESH RAMESH BHAI">
                                <div class="form-text">This exact name is printed on I-cards. Leave blank to auto-build from English name.</div>
                                <div class="invalid-feedback" data-err="display_name"></div>
                            </div>
                        </div>

                        <div class="pv-sec-label">Gujarati name <span class="pv-sec-note">ગુજરાતી</span></div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">અટક</label>
                                <input type="text" name="gu_last_name" class="form-control gu-input" value="{{ $old('gu_last_name') }}" placeholder="અટક" lang="gu">
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">નામ</label>
                                <input type="text" name="gu_first_name" class="form-control gu-input" value="{{ $old('gu_first_name') }}" placeholder="નામ" lang="gu">
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">પિતાનું નામ</label>
                                <input type="text" name="gu_middle_name" class="form-control gu-input" value="{{ $old('gu_middle_name') }}" placeholder="પિતાનું નામ" lang="gu">
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">દાદાનું નામ</label>
                                <input type="text" name="gu_grandfather_name" class="form-control gu-input" value="{{ $old('gu_grandfather_name') }}" placeholder="દાદાનું નામ" lang="gu">
                            </div>
                        </div>

                        <div class="pv-sec-label">Family relations</div>
                        <div class="form-text mb-2" style="font-size:.78rem;">
                            Add once — the reverse is saved automatically on the other person
                            (e.g. Son ↔ Father / Mother, Brother ↔ Brother / Sister). Gender on both profiles improves accuracy.
                        </div>

                        <div id="relSuggestBox" class="pv-rel-suggest mb-3" hidden>
                            <div class="pv-rel-suggest-head">
                                <i class="bi bi-lightbulb"></i>
                                <span>Possible relations from name pattern</span>
                            </div>
                            <div id="relSuggestList" class="pv-rel-suggest-list"></div>
                            <div class="pv-rel-suggest-note">Suggestions only — not always true. Review before adding.</div>
                        </div>

                        <div class="pv-rel-box mb-3">
                            <div id="relList" class="pv-list mb-3">
                                @if($isEdit)
                                    @forelse($member->personRelations as $pr)
                                        <div class="pv-item" data-id="{{ $pr->id }}">
                                            <div>
                                                <div class="pv-item-title">{{ $pr->sentenceLabel() }}</div>
                                                <div class="pv-item-sub">
                                                    #{{ $pr->relatedPerson?->unique_id }}
                                                    @if($pr->relation?->relation)
                                                        · {{ $pr->relation->relation }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="pv-item-actions">
                                                <button type="button" class="btn btn-sm btn-light btn-rel-edit" data-id="{{ $pr->id }}"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="btn btn-sm btn-light text-danger btn-rel-del" data-id="{{ $pr->id }}" data-name="{{ $pr->sentenceLabel() }}"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="pv-empty" id="relEmpty">No relations yet.</div>
                                    @endforelse
                                @else
                                    <div class="pv-empty" id="relEmpty">Save the member first, then add relations.</div>
                                @endif
                            </div>

                            {{-- div (not form): nested forms break personalForm layout in browsers --}}
                            <div id="relationForm" autocomplete="off" @if(! $isEdit) class="opacity-75" @endif>
                                <input type="hidden" id="relRecordId" value="">
                                <input type="hidden" id="relRelatedId" value="">
                                <div class="pv-sec-label" id="relFormLabel" style="font-size:.78rem;margin-bottom:.65rem;">Add relation</div>

                                <div class="pv-rel-preview mb-3" id="relPreview" aria-live="polite">
                                    <span class="pv-rel-preview-text" id="relPreviewText">Select a relation to see the sentence.</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Relation <span class="text-danger">*</span></label>
                                        <select id="relType" class="form-select" @disabled(! $isEdit)>
                                            <option value="">Select relation</option>
                                            @foreach($relations as $rel)
                                                <option value="{{ $rel->id }}" data-name="{{ $rel->relation }}">{{ $rel->relation }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" data-err="relation_id"></div>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label">Related person <span class="text-danger">*</span></label>
                                        <div class="pv-rel-search">
                                            <input type="text"
                                                   id="relPersonSearch"
                                                   class="form-control"
                                                   placeholder="Search by name, ID or phone"
                                                   autocomplete="off"
                                                   @disabled(! $isEdit)>
                                            <div class="pv-rel-results" id="relPersonResults" hidden></div>
                                            <div class="pv-rel-picked d-none" id="relPersonPicked">
                                                <span id="relPersonPickedLabel"></span>
                                                <button type="button" class="btn btn-sm btn-light" id="relPersonClear" title="Clear"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback" data-err="related_personal_info_id"></div>
                                    </div>
                                </div>
                                <div class="pv-actions" style="margin-top:1rem;">
                                    <button type="button" class="btn btn-primary pv-save" id="relSave" @disabled(! $isEdit)>
                                        <span class="txt"><i class="bi bi-check-lg me-1"></i>Save Relation</span>
                                        <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                                    </button>
                                    <button type="button" class="btn btn-light d-none" id="relCancel">Cancel edit</button>
                                </div>
                            </div>
                        </div>

                        <div class="pv-sec-label">Identity</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Aadhaar number <span class="text-danger">*</span></label>
                                @php
                                    $aadhaarVal = old('aadhaar_number', $isEdit ? ($member->aadhaar_number ?? '') : '');
                                    $aadhaarVal = \App\Models\Parivar\PersonalInfo::formatAadhaar($aadhaarVal);
                                @endphp
                                <div class="input-group">
                                    <input type="text"
                                           name="aadhaar_number"
                                           id="aadhaar_number"
                                           class="form-control"
                                           value="{{ $aadhaarVal }}"
                                           placeholder="XXXX-XXXX-XXXX"
                                           inputmode="numeric"
                                           autocomplete="off"
                                           maxlength="14">
                                    <button type="button"
                                            class="btn btn-light"
                                            id="btnGenAadhaar"
                                            title="Auto-generate (starts with 0000)"
                                            style="border:1.5px solid var(--border);border-left:none;font-size:.78rem;font-weight:600;color:var(--primary);white-space:nowrap;">
                                        <i class="bi bi-magic me-1"></i>Auto
                                    </button>
                                </div>
                                <div class="form-text" style="font-size:.74rem;">12 digits · unique · Auto uses 0000-XXXX-XXXX</div>
                                <div class="invalid-feedback" data-err="aadhaar_number"></div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Date of birth</label>
                                <input type="date"
                                       name="date_of_birth"
                                       id="date_of_birth"
                                       class="form-control"
                                       value="{{ old('date_of_birth', $isEdit && $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '') }}">
                                <div class="invalid-feedback" data-err="date_of_birth"></div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    @foreach($genders as $val => $label)
                                        <option value="{{ $val }}" @selected($old('gender') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Marital status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select</option>
                                    @foreach($maritals as $val => $label)
                                        <option value="{{ $val }}" @selected($old('marital_status') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="pv-sec-label">Contact</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Primary phone</label>
                                <div class="input-group">
                                    <select name="c_code_1" class="form-select" style="max-width:140px;">
                                        @foreach($codes as $code => $label)
                                            <option value="{{ $code }}" @selected($old('c_code_1', '+91') === $code)>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="phone_1" id="phone_1" class="form-control" value="{{ $old('phone_1') }}" placeholder="Phone number" maxlength="20" data-phone-check="1">
                                </div>
                                <div class="pv-phone-matches" id="phone1Matches" hidden></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secondary phone</label>
                                <div class="input-group">
                                    <select name="c_code_2" class="form-select" style="max-width:140px;">
                                        <option value="">—</option>
                                        @foreach($codes as $code => $label)
                                            <option value="{{ $code }}" @selected($old('c_code_2') === $code)>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="phone_2" id="phone_2" class="form-control" value="{{ $old('phone_2') }}" placeholder="Optional" maxlength="20" data-phone-check="1">
                                </div>
                                <div class="pv-phone-matches" id="phone2Matches" hidden></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Native place</label>
                                <input type="text" name="native_place" class="form-control" value="{{ $old('native_place') }}" placeholder="Village / city">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <label class="pv-switch w-100 mb-0">
                                    <span>
                                        <strong>Gurukul student</strong>
                                        <small>Was / is this person a Gurukul student?</small>
                                    </span>
                                    <input type="hidden" name="is_gurukul_student" value="0">
                                    <input type="checkbox" name="is_gurukul_student" value="1" class="form-check-input" @checked($old('is_gurukul_student', $isEdit ? $member->is_gurukul_student : false))>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Current address</label>
                                <textarea name="current_address" class="form-control" rows="3" placeholder="House / street, area, city, pin">{{ $old('current_address') }}</textarea>
                                <div class="invalid-feedback" data-err="current_address"></div>
                            </div>
                        </div>

                        <div class="pv-actions">
                            <button type="submit" class="btn btn-primary pv-save" id="personalSave">
                                <span class="txt"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Save Personal Info' : 'Create Member' }}</span>
                                <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                            </button>
                            @unless($isEdit)
                                <span class="pv-actions-note">Next: business &amp; gurukul unlock after this save.</span>
                            @endunless
                        </div>
                    </form>
                </div>
            </section>

            {{-- BUSINESS --}}
            <section class="pv-panel {{ $tab === 'business' ? 'is-open' : '' }}" data-panel="business">
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span class="pv-hl">
                            <span class="pv-head-icon"><i class="bi bi-briefcase"></i></span>
                            <span class="pv-head-title">Business Information</span>
                        </span>
                        <span class="pv-head-badge" id="bizCount">{{ $isEdit ? $member->businesses->count() : 0 }} record(s)</span>
                    </div>
                    <div class="pv-body">
                        <div id="bizList" class="pv-list mb-4">
                            @if($isEdit)
                                @forelse($member->businesses as $biz)
                                    <div class="pv-item" data-id="{{ $biz->id }}">
                                        <div>
                                            <div class="pv-item-title">{{ $biz->business_name }}</div>
                                            <div class="pv-item-sub">{{ $biz->business_address ?: 'No address' }}</div>
                                        </div>
                                        <div class="pv-item-actions">
                                            <button type="button" class="btn btn-sm btn-light btn-biz-edit" data-id="{{ $biz->id }}"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-light text-danger btn-biz-del" data-id="{{ $biz->id }}" data-name="{{ $biz->business_name }}"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="pv-empty" id="bizEmpty">No business records yet.</div>
                                @endforelse
                            @else
                                <div class="pv-empty" id="bizEmpty">No business records yet.</div>
                            @endif
                        </div>

                        <form id="businessForm" novalidate autocomplete="off">
                            @csrf
                            <input type="hidden" name="section" value="business">
                            <input type="hidden" name="record_id" id="bizRecordId" value="">
                            <div class="pv-sec-label" id="bizFormLabel">Add business</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Business name <span class="text-danger">*</span></label>
                                    <input type="text" name="business_name" id="bizName" class="form-control" placeholder="Company / shop name">
                                    <div class="invalid-feedback" data-err="business_name"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Business address</label>
                                    <input type="text" name="business_address" id="bizAddress" class="form-control" placeholder="Address">
                                </div>
                            </div>
                            <div class="pv-actions">
                                <button type="submit" class="btn btn-primary pv-save" id="bizSave">
                                    <span class="txt"><i class="bi bi-check-lg me-1"></i>Save Business</span>
                                    <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                                </button>
                                <button type="button" class="btn btn-light d-none" id="bizCancel">Cancel edit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            {{-- GURUKUL --}}
            <section class="pv-panel {{ $tab === 'gurukul' ? 'is-open' : '' }}" data-panel="gurukul">
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span class="pv-hl">
                            <span class="pv-head-icon"><i class="bi bi-mortarboard"></i></span>
                            <span class="pv-head-title">Gurukul Information</span>
                        </span>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-light btn-sm" id="btnManageBranches"><i class="bi bi-building me-1"></i>Branches</button>
                            <button type="button" class="btn btn-light btn-sm" id="btnManageSaints"><i class="bi bi-person-badge me-1"></i>Saints</button>
                            <span class="pv-head-badge" id="gkCount">{{ $isEdit ? $member->gurukulRecords->count() : 0 }} record(s)</span>
                        </div>
                    </div>
                    <div class="pv-body">
                        <div id="gkList" class="pv-list mb-4">
                            @if($isEdit)
                                @forelse($member->gurukulRecords as $gk)
                                    <div class="pv-item" data-id="{{ $gk->id }}">
                                        <div>
                                            <div class="pv-item-title">{{ $gk->branch?->branch_name ?: 'Branch' }}</div>
                                            <div class="pv-item-sub">
                                                @if($gk->start_year || $gk->end_year)
                                                    {{ $gk->start_year ?: '—' }}@if($gk->end_year) – {{ $gk->end_year }}@endif
                                                @else
                                                    —
                                                @endif
                                                @if($gk->total_year)
                                                    · {{ $gk->total_year }} yr
                                                @endif
                                                @if($gk->saints->isNotEmpty())
                                                    · {{ $gk->saints->map->displayName()->implode(', ') }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="pv-item-actions">
                                            <button type="button" class="btn btn-sm btn-light btn-gk-edit" data-id="{{ $gk->id }}"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-light text-danger btn-gk-del" data-id="{{ $gk->id }}" data-name="{{ $gk->branch?->branch_name }}"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="pv-empty" id="gkEmpty">No gurukul records yet.</div>
                                @endforelse
                            @else
                                <div class="pv-empty" id="gkEmpty">No gurukul records yet.</div>
                            @endif
                        </div>

                        <form id="gurukulForm" novalidate autocomplete="off">
                            @csrf
                            <input type="hidden" name="section" value="gurukul">
                            <input type="hidden" name="record_id" id="gkRecordId" value="">
                            <div class="pv-sec-label" id="gkFormLabel">Add gurukul stay</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select name="branch_id" id="gkBranch" class="form-select">
                                        <option value="">Select branch</option>
                                        @foreach($branches as $b)
                                            <option value="{{ $b->id }}" data-active="{{ $b->is_active ? 1 : 0 }}">
                                                {{ $b->branch_name }}{{ $b->is_active ? '' : ' (inactive)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" data-err="branch_id"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Start year</label>
                                    <select name="start_year" id="gkStart" class="form-select">
                                        <option value="">Year</option>
                                        @foreach($years as $y)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" data-err="start_year"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">End year</label>
                                    <select name="end_year" id="gkEnd" class="form-select">
                                        <option value="">Year</option>
                                        @foreach($years as $y)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Total years</label>
                                    <input type="number" name="total_year" id="gkTotal" class="form-control" min="0" max="80" placeholder="Auto">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Connected saints</label>
                                    <div class="pv-chips" id="gkSaints">
                                        @forelse($saints as $s)
                                            <label class="pv-chip {{ $s->is_active ? '' : 'is-inactive' }}">
                                                <input type="checkbox" name="saints[]" value="{{ $s->id }}" class="gk-saint" {{ $s->is_active ? '' : 'disabled' }}>
                                                {{ $s->displayName() }}
                                            </label>
                                        @empty
                                            <span class="text-muted small">No saints yet. Add them from Saints.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="pv-actions">
                                <button type="submit" class="btn btn-primary pv-save" id="gkSave">
                                    <span class="txt"><i class="bi bi-check-lg me-1"></i>Save Gurukul Info</span>
                                    <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                                </button>
                                <button type="button" class="btn btn-light d-none" id="gkCancel">Cancel edit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>

{{-- Branch master modal --}}
<div class="modal fade" id="branchModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-building me-2"></i>Gurukul Branches</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="branchList" class="pv-list mb-3">
                    @forelse($branches as $b)
                        <div class="pv-item" data-id="{{ $b->id }}">
                            <div>
                                <div class="pv-item-title">{{ $b->branch_name }}</div>
                                <div class="pv-item-sub">{{ $b->is_active ? 'Active' : 'Inactive' }}</div>
                            </div>
                            <div class="pv-item-actions">
                                <button type="button" class="btn btn-sm btn-light btn-br-edit" data-id="{{ $b->id }}" data-name="{{ $b->branch_name }}" data-active="{{ $b->is_active ? 1 : 0 }}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-light text-danger btn-br-del" data-id="{{ $b->id }}" data-name="{{ $b->branch_name }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <div class="pv-empty">No branches yet.</div>
                    @endforelse
                </div>
                <form id="branchForm" novalidate>
                    @csrf
                    <input type="hidden" name="section" value="branch">
                    <input type="hidden" name="record_id" id="brRecordId" value="">
                    <div class="mb-2">
                        <label class="form-label">Branch name</label>
                        <input type="text" name="branch_name" id="brName" class="form-control" placeholder="e.g. Rajkot">
                        <div class="invalid-feedback" data-err="branch_name"></div>
                    </div>
                    <label class="pv-switch mb-3">
                        <span><strong>Active</strong></span>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="brActive" class="form-check-input" checked>
                    </label>
                    <button type="submit" class="btn btn-primary w-100" id="brSave">
                        <span class="txt">Save Branch</span>
                        <span class="load d-none">Saving…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Saint master modal --}}
<div class="modal fade" id="saintModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Gurukul Saints</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="saintList" class="pv-list mb-3">
                    @forelse($saints as $s)
                        <div class="pv-item" data-id="{{ $s->id }}">
                            <div>
                                <div class="pv-item-title">{{ $s->name }}</div>
                                <div class="pv-item-sub">{{ $s->number ?: 'No number' }} · {{ $s->is_active ? 'Active' : 'Inactive' }}</div>
                            </div>
                            <div class="pv-item-actions">
                                <button type="button" class="btn btn-sm btn-light btn-st-edit" data-id="{{ $s->id }}" data-name="{{ $s->name }}" data-number="{{ $s->number }}" data-active="{{ $s->is_active ? 1 : 0 }}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-light text-danger btn-st-del" data-id="{{ $s->id }}" data-name="{{ $s->name }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <div class="pv-empty">No saints yet.</div>
                    @endforelse
                </div>
                <form id="saintForm" novalidate>
                    @csrf
                    <input type="hidden" name="section" value="saint">
                    <input type="hidden" name="record_id" id="stRecordId" value="">
                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="stName" class="form-control" placeholder="Saint name">
                        <div class="invalid-feedback" data-err="name"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Number</label>
                        <input type="text" name="number" id="stNumber" class="form-control" placeholder="Optional">
                    </div>
                    <label class="pv-switch mb-3">
                        <span><strong>Active</strong></span>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="stActive" class="form-check-input" checked>
                    </label>
                    <button type="submit" class="btn btn-primary w-100" id="stSave">
                        <span class="txt">Save Saint</span>
                        <span class="load d-none">Saving…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.gu-input { font-family: 'Noto Sans Gujarati', Inter, sans-serif; font-size: 1rem; }
.pv-sticky { position: sticky; top: 1.1rem; }
.pv-profile {
    display: flex; align-items: center; gap: .8rem;
    background: #fff; border: 1.5px solid var(--border-light);
    border-radius: var(--r-lg); padding: .9rem 1rem; margin-bottom: .75rem;
    box-shadow: 0 2px 12px rgba(139,26,26,.06);
}
.pv-avatar {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-mid));
    color: #fff; font-weight: 800; font-size: .9rem;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; position: relative;
}
.pv-avatar img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.pv-avatar.has-photo { background: #eee; }
.pv-photo-row { display: flex; align-items: flex-start; gap: 1.1rem; }
.pv-passport {
    width: 108px; height: 144px; flex-shrink: 0;
    border: 1.5px dashed var(--border); border-radius: 8px;
    background: var(--bg); overflow: hidden; position: relative;
}
.pv-passport.has-img { border-style: solid; }
.pv-passport img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pv-passport-empty {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: var(--text-muted); gap: .25rem; font-size: .68rem; text-align: center; padding: .4rem;
}
.pv-passport-empty i { font-size: 1.4rem; opacity: .55; }
.pv-photo-meta { min-width: 0; }
.pv-profile-name { font-weight: 700; font-size: .92rem; color: var(--text); line-height: 1.25; }
.pv-profile-meta { margin-top: .2rem; }
.pv-uid {
    font-family: ui-monospace, monospace; font-size: .72rem; font-weight: 700;
    color: var(--primary); background: rgba(139,26,26,.08);
    border-radius: .35rem; padding: .15em .5em;
}
.pv-uid-pending { color: var(--text-muted); background: var(--bg); font-weight: 600; }
.pv-nav {
    background: #fff; border: 1.5px solid var(--border-light);
    border-radius: var(--r-lg); overflow: hidden;
    box-shadow: 0 2px 12px rgba(139,26,26,.06);
}
.pv-nav-item {
    width: 100%; border: 0; background: transparent; text-align: left;
    display: flex; align-items: center; gap: .7rem;
    padding: .85rem 1rem; cursor: pointer;
    border-bottom: 1px solid var(--border-light);
    transition: background .15s;
}
.pv-nav-item:last-child { border-bottom: 0; }
.pv-nav-item:hover { background: var(--bg); }
.pv-nav-item.active {
    background: rgba(139,26,26,.07);
    box-shadow: inset 3px 0 0 var(--primary);
}
.pv-nav-ico {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: var(--bg); color: var(--primary);
    display: inline-flex; align-items: center; justify-content: center;
}
.pv-nav-item.active .pv-nav-ico { background: var(--primary); color: #fff; }
.pv-nav-title { display: block; font-size: .84rem; font-weight: 700; color: var(--text); }
.pv-nav-sub { display: block; font-size: .72rem; color: var(--text-muted); }
.pv-nav-state { margin-left: auto; color: var(--text-muted); }
.pv-nav-state.is-lock { color: var(--text-muted); }
.pv-nav-state.is-ready { color: var(--success); }
.pv-nav-item[data-requires-member="1"]:not(.is-unlocked) .pv-nav-title { color: var(--text-muted); }
.pv-hint {
    margin-top: .75rem; font-size: .76rem; color: var(--text-muted);
    background: #fff; border: 1.5px dashed var(--border);
    border-radius: var(--r); padding: .7rem .85rem;
}
.pv-hint i { color: var(--primary); margin-right: .25rem; }
.pv-card {
    background: #fff; border: 1.5px solid var(--border-light);
    border-radius: var(--r-lg); overflow: hidden;
    box-shadow: 0 2px 12px rgba(139,26,26,.06);
}
.pv-card-head {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
    padding: .85rem 1.2rem; border-bottom: 1.5px solid var(--border-light);
}
.pv-hl { display: flex; align-items: center; gap: .55rem; }
.pv-head-icon {
    width: 28px; height: 28px; border-radius: var(--r-sm);
    background: rgba(139,26,26,.09); color: var(--primary);
    display: inline-flex; align-items: center; justify-content: center;
}
.pv-head-title { font-size: .92rem; font-weight: 700; }
.pv-head-badge {
    background: rgba(139,26,26,.09); color: var(--primary);
    border-radius: 2rem; padding: .18em .72em; font-size: .71rem; font-weight: 700;
}
.pv-body { padding: 1.15rem 1.25rem 1.3rem; }
.pv-panel { display: none; }
.pv-panel.is-open { display: block; }
.pv-sec-label {
    font-size: .72rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
    color: var(--primary); margin: .2rem 0 .75rem;
    padding-bottom: .35rem; border-bottom: 1.5px solid rgba(139,26,26,.12);
}
.pv-sec-note { text-transform: none; letter-spacing: 0; font-weight: 600; color: var(--text-muted); margin-left: .35rem; }
.pv-actions { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin-top: 1.15rem; }
.pv-actions-note { font-size: .78rem; color: var(--text-muted); }
.pv-switch {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--r-sm);
    padding: .7rem .9rem; cursor: pointer;
}
.pv-switch strong { display: block; font-size: .85rem; }
.pv-switch small { display: block; font-size: .74rem; color: var(--text-muted); }
.pv-switch .form-check-input { width: 2.4em; height: 1.3em; cursor: pointer; }
.pv-switch .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
.pv-list { display: flex; flex-direction: column; gap: .55rem; }
.pv-item {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    border: 1.5px solid var(--border); border-radius: var(--r-sm);
    padding: .7rem .9rem; background: var(--bg);
}
.pv-item-title { font-weight: 700; font-size: .86rem; }
.pv-item-sub { font-size: .75rem; color: var(--text-muted); margin-top: .1rem; }
.pv-item-actions { display: flex; gap: .3rem; flex-shrink: 0; }
.pv-empty { text-align: center; color: var(--text-muted); font-size: .84rem; padding: .9rem; }
.pv-phone-matches {
    margin-top: .45rem;
    border: 1.5px solid rgba(139,26,26,.18);
    border-radius: var(--r-sm);
    background: #fff8f8;
    overflow: hidden;
}
.pv-phone-matches-title {
    font-size: .72rem;
    font-weight: 700;
    color: var(--primary);
    padding: .4rem .65rem .2rem;
}
.pv-phone-match {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    width: 100%;
    border: 0;
    border-top: 1px solid var(--border-light);
    background: transparent;
    text-align: left;
    padding: .5rem .65rem;
    cursor: pointer;
}
.pv-phone-match:hover { background: rgba(139,26,26,.06); }
.pv-phone-match-name { font-size: .82rem; font-weight: 700; color: var(--text); }
.pv-phone-match-meta { font-size: .72rem; color: var(--text-muted); }
.pv-phone-match i { color: var(--primary); font-size: .85rem; }
.pv-rel-box {
    border: 1.5px solid var(--border-light);
    border-radius: var(--r);
    padding: 1rem 1.05rem 1.05rem;
    background: linear-gradient(180deg, rgba(139,26,26,.03), #fff 40%);
}
.pv-rel-preview {
    border: 1.5px dashed rgba(139,26,26,.28);
    border-radius: .55rem;
    background: rgba(139,26,26,.05);
    padding: .7rem .9rem;
}
.pv-rel-preview-text {
    font-size: .92rem;
    font-weight: 700;
    color: var(--primary);
    line-height: 1.35;
}
.pv-rel-preview-text .pv-rel-blank {
    border-bottom: 1.5px solid rgba(139,26,26,.35);
    padding: 0 .15rem;
    color: var(--text-muted);
    font-weight: 600;
}
.pv-rel-search { position: relative; }
.pv-rel-results {
    position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 20;
    background: #fff; border: 1.5px solid var(--border); border-radius: .55rem;
    box-shadow: 0 10px 28px rgba(0,0,0,.08); max-height: 240px; overflow: auto;
}
.pv-rel-result {
    width: 100%; border: 0; background: transparent; text-align: left;
    padding: .55rem .75rem; cursor: pointer; display: block;
    border-bottom: 1px solid var(--border-light);
}
.pv-rel-result:last-child { border-bottom: 0; }
.pv-rel-result:hover { background: rgba(139,26,26,.06); }
.pv-rel-result-name { font-size: .82rem; font-weight: 700; color: var(--text); display: block; }
.pv-rel-result-meta { font-size: .72rem; color: var(--text-muted); }
.pv-rel-picked {
    margin-top: .45rem;
    display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    border: 1.5px solid rgba(139,26,26,.2); border-radius: .5rem;
    background: rgba(139,26,26,.05); padding: .4rem .55rem .4rem .7rem;
    font-size: .82rem; font-weight: 600; color: var(--text);
}
.pv-rel-suggest {
    border: 1.5px solid rgba(180, 120, 20, .35);
    border-radius: var(--r);
    background: linear-gradient(180deg, rgba(255, 214, 102, .18), #fff 55%);
    padding: .85rem 1rem;
}
.pv-rel-suggest-head {
    display: flex; align-items: center; gap: .45rem;
    font-size: .8rem; font-weight: 700; color: #8a5a00; margin-bottom: .65rem;
}
.pv-rel-suggest-list { display: flex; flex-direction: column; gap: .45rem; }
.pv-rel-suggest-item {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    background: #fff; border: 1px solid var(--border-light); border-radius: .5rem;
    padding: .55rem .7rem;
}
.pv-rel-suggest-title { font-size: .84rem; font-weight: 700; color: var(--text); line-height: 1.3; }
.pv-rel-suggest-meta { font-size: .72rem; color: var(--text-muted); margin-top: .15rem; }
.pv-rel-suggest-note { font-size: .72rem; color: var(--text-muted); margin-top: .55rem; }
.pv-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.pv-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    border: 1.5px solid var(--border); border-radius: 2rem;
    padding: .28em .75em; font-size: .78rem; cursor: pointer; background: #fff; margin: 0;
}
.pv-chip:has(input:checked) { border-color: var(--primary); background: rgba(139,26,26,.08); color: var(--primary); font-weight: 600; }
.pv-chip.is-inactive { opacity: .45; }
.pv-modal .modal-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
    color: #fff; border: 0;
}
.pv-modal .modal-content { border: 0; border-radius: var(--r-lg); overflow: hidden; }
.pv-modal .modal-title { font-size: .95rem; font-weight: 700; }
@media (max-width: 991.98px) {
    .pv-sticky { position: static; }
    .pv-nav { display: flex; overflow-x: auto; }
    .pv-nav-item { min-width: 210px; border-bottom: 0; border-right: 1px solid var(--border-light); }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var $app        = $('#pvApp');
    var hasMember   = $app.data('has-member') == 1;
    var memberId    = $app.data('member-id') || '';
    var createUrl   = $app.data('create-url');
    var updateUrl   = $app.data('update-url');
    var deleteUrl   = $app.data('delete-url');
    var genAadhaarUrl = $app.data('generate-aadhaar-url');
    var checkPhoneUrl = $app.data('check-phone-url');
    var searchMembersUrl = $app.data('search-members-url');
    var suggestRelationsUrl = $app.data('suggest-relations-url');
    var editBase    = $app.data('edit-base');
    var currentTab  = $app.data('tab') || 'personal';
    var relSuggestTimer = null;
    var dismissedSuggestions = {};

    var bizStore = {};
    var gkStore  = {};
    var relStore = {};

    @if($isEdit)
        @foreach($member->businesses as $biz)
            bizStore[{{ (int) $biz->id }}] = @json($biz->toFormArray());
        @endforeach
        @foreach($member->gurukulRecords as $gk)
            gkStore[{{ (int) $gk->id }}] = @json($gk->toFormArray());
        @endforeach
        @foreach($member->personRelations as $pr)
            relStore[{{ (int) $pr->id }}] = @json($pr->toFormArray());
        @endforeach
    @endif

    if (hasMember) {
        $('.pv-nav-item[data-requires-member]').addClass('is-unlocked');
    }

    function showTab(tab, push) {
        if (!hasMember && tab !== 'personal') {
            Swal.fire({
                icon: 'info',
                title: 'Create the member first',
                text: 'Personal information must be saved before business or gurukul details can be added.',
                confirmButtonColor: '#8B1A1A',
            });
            return;
        }
        currentTab = tab;
        $('.pv-nav-item').removeClass('active');
        $('.pv-nav-item[data-tab="' + tab + '"]').addClass('active');
        $('.pv-panel').removeClass('is-open');
        $('.pv-panel[data-panel="' + tab + '"]').addClass('is-open');
        if (push && hasMember && memberId) {
            var url = editBase + '/' + memberId + '/edit?tab=' + tab;
            history.replaceState({}, '', url);
        }
    }

    $('#pvNav').on('click', '.pv-nav-item', function () {
        showTab($(this).data('tab'), true);
    });

    function setSaving($btn, on) {
        $btn.prop('disabled', on);
        $btn.find('.txt').toggleClass('d-none', on);
        $btn.find('.load').toggleClass('d-none', !on);
    }

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    }

    function showErrors($form, errors) {
        $.each(errors || {}, function (field, msgs) {
            var $input = $form.find('[name="' + field + '"], [name="' + field + '[]"]').first();
            $input.addClass('is-invalid');
            $form.find('[data-err="' + field + '"]').text(msgs[0]).show();
        });
    }

    function toast(msg) {
        Swal.fire({ icon: 'success', title: 'Saved', text: msg, timer: 1800, showConfirmButton: false });
    }

    function saveUrl() {
        return hasMember && updateUrl ? updateUrl : createUrl;
    }

    // ── Personal ──────────────────────────────────────────
    $('#personalForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        clearErrors($form);
        setSaving($('#personalSave'), true);
        $.ajax({
            url: saveUrl(),
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (r) {
                if (r.redirect && !hasMember) {
                    window.location = r.redirect;
                    return;
                }
                if (r.member) {
                    $('#pvName').text(r.member.full_name);
                    $('#pvAvatarInit').text(r.member.initials);
                    $('#pvUid').text('#' + r.member.unique_id).removeClass('pv-uid-pending');
                    applyPhoto(r.member.photo_url || '');
                    $('#photoInput').val('');
                    $('#removePhoto').val('0');
                    $('#btnPickPhoto').html('<i class="bi bi-upload me-1"></i>' + (r.member.has_photo ? 'Replace photo' : 'Upload photo'));
                }
                toast(r.message);
                scheduleRelationSuggestions();
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) showErrors($form, res.errors);
                else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save.' });
            },
            complete: function () { setSaving($('#personalSave'), false); }
        });
    });

    $('#en_first_name, #en_last_name').on('input', function () {
        var f = $.trim($('#en_first_name').val());
        var l = $.trim($('#en_last_name').val());
        if (f || l) {
            $('#pvName').text($.trim(f + ' ' + l) || 'New member');
            $('#pvAvatarInit').text(((f.charAt(0) || '') + (l.charAt(0) || '')).toUpperCase() || '?');
        }
    });

    function applyPhoto(url) {
        if (url) {
            $('#photoPreviewImg').attr('src', url).removeClass('d-none');
            $('#photoPlaceholder').addClass('d-none');
            $('#photoFrame').addClass('has-img');
            $('#pvAvatarImg').attr('src', url).removeClass('d-none');
            $('#pvAvatarInit').addClass('d-none');
            $('#pvAvatar').addClass('has-photo');
            $('#btnClearPhoto').removeClass('d-none');
        } else {
            $('#photoPreviewImg').attr('src', '').addClass('d-none');
            $('#photoPlaceholder').removeClass('d-none');
            $('#photoFrame').removeClass('has-img');
            $('#pvAvatarImg').attr('src', '').addClass('d-none');
            $('#pvAvatarInit').removeClass('d-none');
            $('#pvAvatar').removeClass('has-photo');
            $('#btnClearPhoto').addClass('d-none');
            $('#btnPickPhoto').html('<i class="bi bi-upload me-1"></i>Upload photo');
        }
    }

    $('#btnPickPhoto').on('click', function () { $('#photoInput').trigger('click'); });

    $('#photoInput').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        $('#removePhoto').val('0');
        var url = URL.createObjectURL(file);
        applyPhoto(url);
        $('#btnPickPhoto').html('<i class="bi bi-upload me-1"></i>Replace photo');
        $('[data-err="photo"]').text('');
    });

    $('#btnClearPhoto').on('click', function () {
        $('#photoInput').val('');
        $('#removePhoto').val('1');
        applyPhoto('');
    });

    function formatAadhaarInput(raw) {
        var digits = String(raw || '').replace(/\D+/g, '').slice(0, 12);
        var parts = [];
        if (digits.length > 0) parts.push(digits.slice(0, 4));
        if (digits.length > 4) parts.push(digits.slice(4, 8));
        if (digits.length > 8) parts.push(digits.slice(8, 12));
        return parts.join('-');
    }

    $('#aadhaar_number').on('input', function () {
        var el = this;
        var start = el.selectionStart;
        var before = el.value;
        var formatted = formatAadhaarInput(before);
        if (before === formatted) return;
        el.value = formatted;
        // Keep caret roughly at end while typing
        var diff = formatted.length - before.length;
        var pos = Math.max(0, (start || 0) + diff);
        try { el.setSelectionRange(pos, pos); } catch (e) {}
        $(el).removeClass('is-invalid');
        $('[data-err="aadhaar_number"]').text('');
    });

    $('#aadhaar_number').on('blur', function () {
        this.value = formatAadhaarInput(this.value);
    });

    if ($('#aadhaar_number').val()) {
        $('#aadhaar_number').val(formatAadhaarInput($('#aadhaar_number').val()));
    }

    $('#btnGenAadhaar').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: genAadhaarUrl,
            type: 'POST',
            data: { _token: csrfToken, except_id: memberId || '' },
            success: function (r) {
                if (r.success && r.aadhaar_formatted) {
                    $('#aadhaar_number').val(r.aadhaar_formatted).removeClass('is-invalid');
                    $('[data-err="aadhaar_number"]').text('');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: r.message || 'Could not generate Aadhaar.' });
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Could not generate Aadhaar.' });
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    function renderPhoneMatches($box, matches) {
        if (!matches || !matches.length) {
            $box.attr('hidden', true).empty();
            return;
        }
        var html = '<div class="pv-phone-matches-title">Already registered with this number</div>';
        matches.forEach(function (m) {
            html += '<button type="button" class="pv-phone-match" data-url="' + escAttr(m.edit_url) + '">'
                + '<span><div class="pv-phone-match-name">' + esc(m.full_name || 'Member') + '</div>'
                + '<div class="pv-phone-match-meta">#' + esc(String(m.unique_id)) + (m.phone ? ' · ' + esc(m.phone) : '') + '</div></span>'
                + '<i class="bi bi-box-arrow-up-right"></i></button>';
        });
        $box.html(html).removeAttr('hidden');
    }

    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }
    function escAttr(s) {
        return esc(s).replace(/`/g, '');
    }

    $('[data-phone-check]').on('blur', function () {
        var $input = $(this);
        var phone = String($input.val() || '').replace(/\D+/g, '');
        var $box = $input.closest('.col-md-6').find('.pv-phone-matches');

        if (phone.length < 6) {
            renderPhoneMatches($box, []);
            return;
        }

        $.ajax({
            url: checkPhoneUrl,
            type: 'GET',
            data: { phone: phone, except_id: memberId || '' },
            success: function (r) {
                renderPhoneMatches($box, (r && r.matches) ? r.matches : []);
            },
            error: function () {
                renderPhoneMatches($box, []);
            }
        });
    });

    $(document).on('click', '.pv-phone-match', function () {
        var url = $(this).data('url');
        if (url) window.location.href = url;
    });

    // ── Relation name-pattern suggestions ─────────────────
    function fetchRelationSuggestions() {
        if (!suggestRelationsUrl) return;
        var surname = $.trim($('#en_last_name').val() || '');
        var first   = $.trim($('#en_first_name').val() || '');
        var middle  = $.trim($('#en_middle_name').val() || '');
        var grandpa = $.trim($('#en_grandfather_name').val() || '');
        if (!surname || (!first && !middle && !grandpa)) {
            $('#relSuggestBox').attr('hidden', true);
            $('#relSuggestList').empty();
            return;
        }

        $.getJSON(suggestRelationsUrl, {
            except_id: memberId || '',
            en_last_name: surname,
            en_first_name: first,
            en_middle_name: middle,
            en_grandfather_name: grandpa,
            gender: $('select[name="gender"]').val() || ''
        }).done(function (r) {
            renderRelationSuggestions(r.suggestions || []);
        }).fail(function () {
            $('#relSuggestBox').attr('hidden', true);
        });
    }

    function scheduleRelationSuggestions() {
        clearTimeout(relSuggestTimer);
        relSuggestTimer = setTimeout(fetchRelationSuggestions, 350);
    }

    function renderRelationSuggestions(rows) {
        rows = (rows || []).filter(function (s) {
            return !dismissedSuggestions[s.key];
        });
        var $box = $('#relSuggestBox');
        var $list = $('#relSuggestList');
        if (!rows.length) {
            $box.attr('hidden', true);
            $list.empty();
            return;
        }

        var html = rows.map(function (s) {
            var addBtn = hasMember
                ? '<button type="button" class="btn btn-sm btn-primary btn-rel-suggest-add" data-key="' + esc(s.key) + '"'
                    + ' data-relation-id="' + s.relation_id + '"'
                    + ' data-related-id="' + s.related_id + '"'
                    + ' data-related-name="' + esc(s.related_name) + '"'
                    + ' data-related-uid="' + esc(String(s.related_unique_id)) + '"'
                    + ' data-relation-name="' + esc(s.relation_name) + '">'
                    + '<i class="bi bi-plus-lg me-1"></i>Add</button>'
                : '<span class="pv-rel-suggest-meta">Save member first</span>';

            return '<div class="pv-rel-suggest-item" data-key="' + esc(s.key) + '">'
                + '<div>'
                + '<div class="pv-rel-suggest-title">' + esc(s.label) + '</div>'
                + (s.mirror_label
                    ? '<div class="pv-rel-suggest-meta"><i class="bi bi-arrow-left-right me-1"></i>' + esc(s.mirror_label) + '</div>'
                    : '')
                + '<div class="pv-rel-suggest-meta">#' + esc(String(s.related_unique_id))
                + ' · ' + esc(s.related_name)
                + (s.reason ? ' · ' + esc(s.reason) : '')
                + '</div></div>'
                + '<div class="d-flex gap-1 flex-shrink-0">'
                + addBtn
                + '<button type="button" class="btn btn-sm btn-light btn-rel-suggest-dismiss" data-key="' + esc(s.key) + '" title="Dismiss"><i class="bi bi-x-lg"></i></button>'
                + '</div></div>';
        }).join('');

        $list.html(html);
        $box.removeAttr('hidden');
    }

    $('#en_last_name, #en_first_name, #en_middle_name, #en_grandfather_name').on('blur', scheduleRelationSuggestions);
    $('select[name="gender"]').on('change', scheduleRelationSuggestions);

    $('#relSuggestList').on('click', '.btn-rel-suggest-dismiss', function () {
        var key = $(this).data('key');
        dismissedSuggestions[key] = true;
        $(this).closest('.pv-rel-suggest-item').remove();
        if (!$('#relSuggestList .pv-rel-suggest-item').length) {
            $('#relSuggestBox').attr('hidden', true);
        }
    });

    $('#relSuggestList').on('click', '.btn-rel-suggest-add', function () {
        if (!hasMember || !updateUrl) {
            Swal.fire({
                icon: 'info',
                title: 'Create the member first',
                text: 'Save personal information, then you can add this relation.',
                confirmButtonColor: '#8B1A1A',
            });
            return;
        }

        var $btn = $(this);
        var payload = {
            _token: csrfToken,
            section: 'relation',
            relation_id: $btn.data('relation-id'),
            related_personal_info_id: $btn.data('related-id')
        };

        $btn.prop('disabled', true);
        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: payload,
            success: function (r) {
                relStore[r.record.id] = r.record;
                upsertItem('#relList', '#relEmpty', r.record.id, relHtml(r.record));
                dismissedSuggestions[$btn.data('key')] = true;
                $btn.closest('.pv-rel-suggest-item').remove();
                if (!$('#relSuggestList .pv-rel-suggest-item').length) {
                    $('#relSuggestBox').attr('hidden', true);
                }
                toast(r.message || 'Relation added.');
                scheduleRelationSuggestions();
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not add relation.' });
                $btn.prop('disabled', false);
            }
        });
    });

    // Run once on load when names are already filled.
    setTimeout(fetchRelationSuggestions, 400);

    // ── Relations ─────────────────────────────────────────
    var relSearchTimer = null;
    var relPickedName = '';

    function currentMemberName() {
        return $.trim($('#pvName').text()) || 'This person';
    }

    function updateRelPreview() {
        var $opt = $('#relType option:selected');
        var relName = ($opt.data('name') || $opt.text() || '').toString().trim();
        if (!$('#relType').val() || !relName || relName === 'Select relation') {
            $('#relPreviewText').text('Select a relation to see the sentence.');
            return;
        }
        var blank = relPickedName
            ? esc(relPickedName)
            : '<span class="pv-rel-blank">______</span>';
        $('#relPreviewText').html(
            esc(currentMemberName()) + "'s " + esc(relName.toLowerCase()) + ' is ' + blank
        );
    }

    function setRelPerson(id, label, name) {
        $('#relRelatedId').val(id || '');
        relPickedName = name || '';
        if (id) {
            $('#relPersonPickedLabel').text(label || name);
            $('#relPersonPicked').removeClass('d-none');
            $('#relPersonSearch').addClass('d-none').val('');
            $('#relPersonResults').attr('hidden', true).empty();
        } else {
            $('#relPersonPicked').addClass('d-none');
            $('#relPersonPickedLabel').text('');
            $('#relPersonSearch').removeClass('d-none');
        }
        updateRelPreview();
    }

    function resetRel() {
        $('#relRecordId').val('');
        $('#relType').val('');
        $('#relFormLabel').text('Add relation');
        $('#relCancel').addClass('d-none');
        setRelPerson('', '', '');
        clearErrors($('#relationForm'));
        updateRelPreview();
    }

    function renderRelResults(rows) {
        var $box = $('#relPersonResults');
        if (!rows.length) {
            $box.html('<div class="pv-rel-result-meta" style="padding:.65rem .75rem;">No members found.</div>').removeAttr('hidden');
            return;
        }
        var html = rows.map(function (r) {
            return '<button type="button" class="pv-rel-result" data-id="' + r.id + '" data-name="' + esc(r.full_name) + '" data-label="' + esc(r.label) + '">'
                + '<span class="pv-rel-result-name">' + esc(r.full_name) + '</span>'
                + '<span class="pv-rel-result-meta">#' + esc(String(r.unique_id)) + (r.phone ? ' · ' + esc(r.phone) : '') + '</span>'
                + '</button>';
        }).join('');
        $box.html(html).removeAttr('hidden');
    }

    $('#relType').on('change', updateRelPreview);
    $('#en_first_name, #en_last_name, #display_name').on('input', updateRelPreview);

    $('#relPersonSearch').on('input', function () {
        if (!hasMember) return;
        var q = $.trim($(this).val());
        clearTimeout(relSearchTimer);
        if (q.length < 1) {
            $('#relPersonResults').attr('hidden', true).empty();
            return;
        }
        relSearchTimer = setTimeout(function () {
            $.getJSON(searchMembersUrl, { q: q, except_id: memberId || '' })
                .done(function (r) { renderRelResults(r.results || []); })
                .fail(function () { $('#relPersonResults').attr('hidden', true).empty(); });
        }, 250);
    });

    $('#relPersonResults').on('click', '.pv-rel-result', function () {
        setRelPerson($(this).data('id'), $(this).data('label'), $(this).data('name'));
    });

    $('#relPersonClear').on('click', function () {
        setRelPerson('', '', '');
        $('#relPersonSearch').focus();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.pv-rel-search').length) {
            $('#relPersonResults').attr('hidden', true);
        }
    });

    $('#relCancel').on('click', resetRel);

    $('#relSave').on('click', function () {
        if (!hasMember) {
            Swal.fire({
                icon: 'info',
                title: 'Create the member first',
                text: 'Personal information must be saved before relations can be added.',
                confirmButtonColor: '#8B1A1A',
            });
            return;
        }
        var $box = $('#relationForm');
        clearErrors($box);
        setSaving($('#relSave'), true);
        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: {
                _token: csrfToken,
                section: 'relation',
                record_id: $('#relRecordId').val() || '',
                relation_id: $('#relType').val() || '',
                related_personal_info_id: $('#relRelatedId').val() || ''
            },
            success: function (r) {
                relStore[r.record.id] = r.record;
                upsertItem('#relList', '#relEmpty', r.record.id, relHtml(r.record));
                resetRel();
                toast(r.message);
                scheduleRelationSuggestions();
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) {
                    showErrors($box, res.errors);
                    if (res.errors.relation_id) $('#relType').addClass('is-invalid');
                    if (res.errors.related_personal_info_id) $('#relPersonSearch').addClass('is-invalid');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save.' });
                }
            },
            complete: function () { setSaving($('#relSave'), false); }
        });
    });

    $('#relList').on('click', '.btn-rel-edit', function () {
        var id = $(this).data('id');
        var row = relStore[id] || relStore[String(id)];
        if (!row) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load this relation.' });
            return;
        }
        $('#relRecordId').val(row.id);
        $('#relType').val(String(row.relation_id));
        setRelPerson(
            row.related_personal_info_id,
            '#' + row.related_unique_id + ' · ' + row.related_name,
            row.related_name
        );
        $('#relFormLabel').text('Edit relation');
        $('#relCancel').removeClass('d-none');
        updateRelPreview();
    });

    $('#relList').on('click', '.btn-rel-del', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        delRecord('person_relation', id, name, function (deletedId) {
            delete relStore[deletedId];
            $('#relList .pv-item[data-id="' + deletedId + '"]').remove();
            ensureEmpty('#relList', '#relEmpty', 'No relations yet.');
        });
    });

    function relHtml(row) {
        return '<div class="pv-item" data-id="' + row.id + '">'
            + '<div><div class="pv-item-title">' + esc(row.label) + '</div>'
            + '<div class="pv-item-sub">#' + esc(String(row.related_unique_id || ''))
            + (row.relation_name ? ' · ' + esc(row.relation_name) : '')
            + '</div></div>'
            + '<div class="pv-item-actions">'
            + '<button type="button" class="btn btn-sm btn-light btn-rel-edit" data-id="' + row.id + '"><i class="bi bi-pencil"></i></button>'
            + '<button type="button" class="btn btn-sm btn-light text-danger btn-rel-del" data-id="' + row.id + '" data-name="' + esc(row.label) + '"><i class="bi bi-trash"></i></button>'
            + '</div></div>';
    }

    updateRelPreview();

    // ── Business ──────────────────────────────────────────
    function resetBiz() {
        $('#businessForm')[0].reset();
        $('#bizRecordId').val('');
        $('#bizFormLabel').text('Add business');
        $('#bizCancel').addClass('d-none');
        clearErrors($('#businessForm'));
    }

    $('#bizCancel').on('click', resetBiz);

    $('#businessForm').on('submit', function (e) {
        e.preventDefault();
        if (!hasMember) return showTab('business');
        var $form = $(this);
        clearErrors($form);
        setSaving($('#bizSave'), true);
        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: $form.serialize(),
            success: function (r) {
                bizStore[r.record.id] = r.record;
                upsertItem('#bizList', '#bizEmpty', r.record.id, bizHtml(r.record));
                updateCount('#bizCount', '#bizList .pv-item');
                resetBiz();
                toast(r.message);
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) showErrors($form, res.errors);
                else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save.' });
            },
            complete: function () { setSaving($('#bizSave'), false); }
        });
    });

    $('#bizList').on('click', '.btn-biz-edit', function () {
        var id  = $(this).data('id');
        var row = bizStore[id];
        if (!row) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load this business record.' });
            return;
        }
        $('#bizRecordId').val(row.id);
        $('#bizName').val(row.business_name);
        $('#bizAddress').val(row.business_address || '');
        $('#bizFormLabel').text('Edit business');
        $('#bizCancel').removeClass('d-none');
    });

    $('#bizList').on('click', '.btn-biz-del', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        delRecord('business', id, name, function (deletedId) {
            delete bizStore[deletedId];
            $('#bizList .pv-item[data-id="' + deletedId + '"]').remove();
            ensureEmpty('#bizList', '#bizEmpty', 'No business records yet.');
            updateCount('#bizCount', '#bizList .pv-item');
        });
    });

    function bizHtml(row) {
        return '<div class="pv-item" data-id="' + row.id + '">'
            + '<div><div class="pv-item-title">' + esc(row.business_name) + '</div>'
            + '<div class="pv-item-sub">' + esc(row.business_address || 'No address') + '</div></div>'
            + '<div class="pv-item-actions">'
            + '<button type="button" class="btn btn-sm btn-light btn-biz-edit" data-id="' + row.id + '"><i class="bi bi-pencil"></i></button>'
            + '<button type="button" class="btn btn-sm btn-light text-danger btn-biz-del" data-id="' + row.id + '" data-name="' + esc(row.business_name) + '"><i class="bi bi-trash"></i></button>'
            + '</div></div>';
    }

    // ── Gurukul ───────────────────────────────────────────
    $('#gkStart, #gkEnd').on('change', function () {
        var s = parseInt($('#gkStart').val(), 10);
        var e = parseInt($('#gkEnd').val(), 10);
        if (s && e && e >= s && !$('#gkTotal').val()) {
            $('#gkTotal').attr('placeholder', String(e - s));
        }
    });

    function resetGk() {
        $('#gurukulForm')[0].reset();
        $('#gkRecordId').val('');
        $('.gk-saint').prop('checked', false);
        $('#gkFormLabel').text('Add gurukul stay');
        $('#gkCancel').addClass('d-none');
        clearErrors($('#gurukulForm'));
    }

    $('#gkCancel').on('click', resetGk);

    $('#gurukulForm').on('submit', function (e) {
        e.preventDefault();
        if (!hasMember) return showTab('gurukul');
        var $form = $(this);
        clearErrors($form);
        setSaving($('#gkSave'), true);
        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: $form.serialize(),
            success: function (r) {
                gkStore[r.record.id] = r.record;
                upsertItem('#gkList', '#gkEmpty', r.record.id, gkHtml(r.record));
                updateCount('#gkCount', '#gkList .pv-item');
                resetGk();
                toast(r.message);
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) showErrors($form, res.errors);
                else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save.' });
            },
            complete: function () { setSaving($('#gkSave'), false); }
        });
    });

    $('#gkList').on('click', '.btn-gk-edit', function () {
        var id  = $(this).data('id');
        var row = gkStore[id] || gkStore[String(id)];
        if (!row || !row.id) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load this gurukul record.' });
            return;
        }
        resetGk();
        $('#gkRecordId').val(row.id);
        var branchId = row.branch_id != null ? String(row.branch_id) : '';
        if (branchId && !$('#gkBranch option[value="' + branchId + '"]').length && row.branch_name) {
            $('#gkBranch').append(
                $('<option>', { value: branchId, text: row.branch_name + ' (missing)', 'data-active': 0 })
            );
        }
        $('#gkBranch').val(branchId);
        $('#gkStart').val(row.start_year != null ? String(row.start_year) : '');
        $('#gkEnd').val(row.end_year != null ? String(row.end_year) : '');
        $('#gkTotal').val(row.total_year != null ? String(row.total_year) : '');
        (row.saint_ids || []).forEach(function (sid) {
            $('.gk-saint[value="' + sid + '"]').prop('checked', true);
        });
        $('#gkFormLabel').text('Edit gurukul stay');
        $('#gkCancel').removeClass('d-none');
        $('html, body').animate({ scrollTop: $('#gurukulForm').offset().top - 80 }, 200);
    });

    $('#gkList').on('click', '.btn-gk-del', function () {
        var id = $(this).data('id');
        delRecord('gurukul', id, $(this).data('name') || 'record', function (deletedId) {
            delete gkStore[deletedId];
            $('#gkList .pv-item[data-id="' + deletedId + '"]').remove();
            ensureEmpty('#gkList', '#gkEmpty', 'No gurukul records yet.');
            updateCount('#gkCount', '#gkList .pv-item');
        });
    });

    function gkHtml(row) {
        var years = '';
        if (row.start_year || row.end_year) {
            years = (row.start_year || '—');
            if (row.end_year) years += ' – ' + row.end_year;
        } else {
            years = '—';
        }
        if (row.total_year) years += ' · ' + row.total_year + ' yr';
        if (row.saint_names && row.saint_names.length) years += ' · ' + row.saint_names.join(', ');
        return '<div class="pv-item" data-id="' + row.id + '">'
            + '<div><div class="pv-item-title">' + esc(row.branch_name || 'Branch') + '</div>'
            + '<div class="pv-item-sub">' + esc(years) + '</div></div>'
            + '<div class="pv-item-actions">'
            + '<button type="button" class="btn btn-sm btn-light btn-gk-edit" data-id="' + row.id + '"><i class="bi bi-pencil"></i></button>'
            + '<button type="button" class="btn btn-sm btn-light text-danger btn-gk-del" data-id="' + row.id + '" data-name="' + esc(row.branch_name || '') + '"><i class="bi bi-trash"></i></button>'
            + '</div></div>';
    }

    // ── Masters ───────────────────────────────────────────
    $('#btnManageBranches').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).show();
    });
    $('#btnManageSaints').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('saintModal')).show();
    });

    $('#branchForm').on('submit', function (e) {
        e.preventDefault();
        saveMaster($(this), $('#brSave'), function (r) {
            upsertItem('#branchList', null, r.record.id, branchItem(r.record));
            ensureBranchOption(r.record);
            $('#brRecordId').val('');
            $('#brName').val('');
            $('#brActive').prop('checked', true);
        });
    });

    $('#saintForm').on('submit', function (e) {
        e.preventDefault();
        saveMaster($(this), $('#stSave'), function (r) {
            upsertItem('#saintList', null, r.record.id, saintItem(r.record));
            ensureSaintChip(r.record);
            $('#stRecordId').val('');
            $('#stName').val('');
            $('#stNumber').val('');
            $('#stActive').prop('checked', true);
        });
    });

    $(document).on('click', '.btn-br-edit', function () {
        $('#brRecordId').val($(this).data('id'));
        $('#brName').val($(this).data('name'));
        $('#brActive').prop('checked', $(this).data('active') == 1);
    });
    $(document).on('click', '.btn-st-edit', function () {
        $('#stRecordId').val($(this).data('id'));
        $('#stName').val($(this).data('name'));
        $('#stNumber').val($(this).data('number') || '');
        $('#stActive').prop('checked', $(this).data('active') == 1);
    });
    $(document).on('click', '.btn-br-del', function () {
        var id = $(this).data('id');
        delRecord('branch', id, $(this).data('name'), function () {
            $('#branchList .pv-item[data-id="' + id + '"]').remove();
            $('#gkBranch option[value="' + id + '"]').remove();
        });
    });
    $(document).on('click', '.btn-st-del', function () {
        var id = $(this).data('id');
        delRecord('saint', id, $(this).data('name'), function () {
            $('#saintList .pv-item[data-id="' + id + '"]').remove();
            $('#gkSaints .pv-chip').has('input[value="' + id + '"]').remove();
        });
    });

    function saveMaster($form, $btn, onOk) {
        clearErrors($form);
        setSaving($btn, true);
        $.ajax({
            url: hasMember ? updateUrl : createUrl,
            type: 'POST',
            data: $form.serialize(),
            success: function (r) { onOk(r); toast(r.message); },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) showErrors($form, res.errors);
                else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save.' });
            },
            complete: function () { setSaving($btn, false); }
        });
    }

    function branchItem(row) {
        return '<div class="pv-item" data-id="' + row.id + '"><div><div class="pv-item-title">' + esc(row.branch_name) + '</div>'
            + '<div class="pv-item-sub">' + (row.is_active ? 'Active' : 'Inactive') + '</div></div>'
            + '<div class="pv-item-actions">'
            + '<button type="button" class="btn btn-sm btn-light btn-br-edit" data-id="' + row.id + '" data-name="' + esc(row.branch_name) + '" data-active="' + (row.is_active ? 1 : 0) + '"><i class="bi bi-pencil"></i></button>'
            + '<button type="button" class="btn btn-sm btn-light text-danger btn-br-del" data-id="' + row.id + '" data-name="' + esc(row.branch_name) + '"><i class="bi bi-trash"></i></button>'
            + '</div></div>';
    }

    function saintItem(row) {
        return '<div class="pv-item" data-id="' + row.id + '"><div><div class="pv-item-title">' + esc(row.name) + '</div>'
            + '<div class="pv-item-sub">' + esc(row.number || 'No number') + ' · ' + (row.is_active ? 'Active' : 'Inactive') + '</div></div>'
            + '<div class="pv-item-actions">'
            + '<button type="button" class="btn btn-sm btn-light btn-st-edit" data-id="' + row.id + '" data-name="' + esc(row.name) + '" data-number="' + esc(row.number || '') + '" data-active="' + (row.is_active ? 1 : 0) + '"><i class="bi bi-pencil"></i></button>'
            + '<button type="button" class="btn btn-sm btn-light text-danger btn-st-del" data-id="' + row.id + '" data-name="' + esc(row.name) + '"><i class="bi bi-trash"></i></button>'
            + '</div></div>';
    }

    function ensureBranchOption(row) {
        var $opt = $('#gkBranch option[value="' + row.id + '"]');
        var label = row.branch_name + (row.is_active ? '' : ' (inactive)');
        if ($opt.length) {
            $opt.text(label).attr('data-active', row.is_active ? 1 : 0);
        } else {
            $('#gkBranch').append(
                $('<option>', { value: row.id, text: label, 'data-active': row.is_active ? 1 : 0 })
            );
        }
    }

    function ensureSaintChip(row) {
        var $chip = $('#gkSaints .pv-chip').has('input[value="' + row.id + '"]');
        var label = row.label || row.name;
        if ($chip.length) {
            $chip.toggleClass('is-inactive', !row.is_active);
            $chip.find('input').prop('disabled', !row.is_active);
            $chip.contents().filter(function () { return this.nodeType === 3; }).remove();
            $chip.append(document.createTextNode(' ' + label));
        } else {
            $('#gkSaints').append(
                '<label class="pv-chip' + (row.is_active ? '' : ' is-inactive') + '">'
                + '<input type="checkbox" name="saints[]" value="' + row.id + '" class="gk-saint"' + (row.is_active ? '' : ' disabled') + '> '
                + esc(label) + '</label>'
            );
        }
    }

    function delRecord(type, id, name, onOk) {
        Swal.fire({
            title: 'Delete "' + name + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor: '#9B7575',
            confirmButtonText: 'Yes, delete',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url: deleteUrl,
                type: 'POST',
                data: { _token: csrfToken, ids: [id], record_type: type },
                success: function (r) {
                    if (r.success) { onOk(id); Swal.fire({ icon: 'success', title: 'Deleted', text: r.message, timer: 1400, showConfirmButton: false }); }
                    else Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Delete failed.' });
                }
            });
        });
    }

    function upsertItem(list, empty, id, html) {
        var $list = $(list);
        var $existing = $list.find('.pv-item[data-id="' + id + '"]');
        if ($existing.length) $existing.replaceWith(html);
        else {
            $list.find(empty || '.pv-empty').remove();
            $list.prepend(html);
        }
    }

    function ensureEmpty(list, emptyId, text) {
        if (!$(list).find('.pv-item').length && !$(emptyId).length) {
            $(list).append('<div class="pv-empty" id="' + emptyId.replace('#','') + '">' + text + '</div>');
        }
    }

    function updateCount(sel, itemSel) {
        var n = $(itemSel).length;
        $(sel).text(n + ' record' + (n === 1 ? '' : 's'));
    }

    function esc(str) {
        return $('<span>').text(str == null ? '' : String(str)).html();
    }
})();
</script>
@endpush
