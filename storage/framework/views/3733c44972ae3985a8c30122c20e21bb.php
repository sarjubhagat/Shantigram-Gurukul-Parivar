

<?php
    $isEdit   = isset($member) && $member;
    $fullName = $isEdit ? $member->englishFullName() : 'New Member';
    $tab      = $tab ?? 'personal';
    $codes    = config('parivar.country_codes');
    $genders  = config('parivar.genders');
    $maritals = config('parivar.marital_statuses');
    $old      = fn ($key, $fallback = '') => old($key, $isEdit ? ($member->{$key} ?? $fallback) : $fallback);
?>

<?php $__env->startSection('title', $isEdit ? 'Manage: ' . $fullName : 'Add Member'); ?>
<?php $__env->startSection('page-title', $isEdit ? 'Manage Member' : 'Add Member'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(url('/admin/parivar')); ?>">Parivar</a></li>
    <li class="breadcrumb-item active"><?php echo e($isEdit ? '#' . $member->unique_id : 'Create'); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <a href="<?php echo e(url('/admin/parivar')); ?>" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Members
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pv-wrap" id="pvApp"
     data-has-member="<?php echo e($isEdit ? '1' : '0'); ?>"
     data-member-id="<?php echo e($isEdit ? $member->id : ''); ?>"
     data-unique-id="<?php echo e($isEdit ? $member->unique_id : ''); ?>"
     data-tab="<?php echo e($tab); ?>"
     data-create-url="<?php echo e(route('admin.parivar.create')); ?>"
     data-update-url="<?php echo e($isEdit ? route('admin.parivar.update', $member->id) : ''); ?>"
     data-delete-url="<?php echo e(route('admin.parivar.delete')); ?>"
     data-generate-aadhaar-url="<?php echo e(route('admin.parivar.generate_aadhaar')); ?>"
     data-edit-base="<?php echo e(url('/admin/parivar')); ?>">

    <div class="row g-4 align-items-start">

        
        <div class="col-lg-3 col-xl-3">
            <div class="pv-sticky">

                <div class="pv-profile">
                    <?php $sidebarPhoto = $isEdit ? $member->photoUrl() : null; ?>
                    <div class="pv-avatar<?php echo e($sidebarPhoto ? ' has-photo' : ''); ?>" id="pvAvatar">
                        <img id="pvAvatarImg" src="<?php echo e($sidebarPhoto); ?>" alt="" <?php if(! $sidebarPhoto): ?> class="d-none" <?php endif; ?>>
                        <span id="pvAvatarInit" <?php if($sidebarPhoto): ?> class="d-none" <?php endif; ?>><?php echo e($isEdit ? $member->initials() : '?'); ?></span>
                    </div>
                    <div class="pv-profile-body">
                        <div class="pv-profile-name" id="pvName"><?php echo e($isEdit ? $fullName : 'New member'); ?></div>
                        <div class="pv-profile-meta">
                            <?php if($isEdit): ?>
                                <span class="pv-uid" id="pvUid">#<?php echo e($member->unique_id); ?></span>
                            <?php else: ?>
                                <span class="pv-uid pv-uid-pending" id="pvUid">ID after save</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <nav class="pv-nav" id="pvNav">
                    <button type="button" class="pv-nav-item <?php echo e($tab === 'personal' ? 'active' : ''); ?>" data-tab="personal">
                        <span class="pv-nav-ico"><i class="bi bi-person-vcard"></i></span>
                        <span class="pv-nav-text">
                            <span class="pv-nav-title">Personal Info</span>
                            <span class="pv-nav-sub">Name, phone, identity</span>
                        </span>
                        <span class="pv-nav-state is-ready"><i class="bi bi-check-circle-fill"></i></span>
                    </button>

                    <button type="button" class="pv-nav-item <?php echo e($tab === 'business' ? 'active' : ''); ?>" data-tab="business" data-requires-member="1">
                        <span class="pv-nav-ico"><i class="bi bi-briefcase"></i></span>
                        <span class="pv-nav-text">
                            <span class="pv-nav-title">Business Info</span>
                            <span class="pv-nav-sub">Work and address</span>
                        </span>
                        <span class="pv-nav-state <?php echo e($isEdit ? 'is-ready' : 'is-lock'); ?>">
                            <i class="bi <?php echo e($isEdit ? 'bi-unlock' : 'bi-lock-fill'); ?>"></i>
                        </span>
                    </button>

                    <button type="button" class="pv-nav-item <?php echo e($tab === 'gurukul' ? 'active' : ''); ?>" data-tab="gurukul" data-requires-member="1">
                        <span class="pv-nav-ico"><i class="bi bi-mortarboard"></i></span>
                        <span class="pv-nav-text">
                            <span class="pv-nav-title">Gurukul Info</span>
                            <span class="pv-nav-sub">Branch, years, saints</span>
                        </span>
                        <span class="pv-nav-state <?php echo e($isEdit ? 'is-ready' : 'is-lock'); ?>">
                            <i class="bi <?php echo e($isEdit ? 'bi-unlock' : 'bi-lock-fill'); ?>"></i>
                        </span>
                    </button>
                </nav>

                <div class="pv-hint" id="pvHint" style="<?php echo e($isEdit ? 'display:none;' : ''); ?>">
                    <i class="bi bi-info-circle"></i>
                    Save personal info first. Business and Gurukul stay locked until the member exists.
                </div>

            </div>
        </div>

        
        <div class="col-lg-9 col-xl-9">

            
            <section class="pv-panel <?php echo e($tab === 'personal' ? 'is-open' : ''); ?>" data-panel="personal">
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span class="pv-hl">
                            <span class="pv-head-icon"><i class="bi bi-person-vcard"></i></span>
                            <span class="pv-head-title">Personal Information</span>
                        </span>
                        <?php if($isEdit): ?>
                            <span class="pv-head-badge">Member #<?php echo e($member->unique_id); ?></span>
                        <?php endif; ?>
                    </div>

                    <form id="personalForm" class="pv-body" novalidate autocomplete="off" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="section" value="personal">
                        <input type="hidden" name="remove_photo" id="removePhoto" value="0">

                        <div class="pv-sec-label">Passport photo</div>
                        <?php $memberPhoto = $isEdit ? $member->photoUrl() : null; ?>
                        <div class="pv-photo-row mb-4">
                            <div class="pv-passport<?php echo e($memberPhoto ? ' has-img' : ''); ?>" id="photoFrame">
                                <img id="photoPreviewImg" src="<?php echo e($memberPhoto); ?>" alt="Passport photo" <?php if(! $memberPhoto): ?> class="d-none" <?php endif; ?>>
                                <div id="photoPlaceholder" class="pv-passport-empty<?php echo e($memberPhoto ? ' d-none' : ''); ?>">
                                    <i class="bi bi-person-bounding-box"></i>
                                    <span>3.5 × 4.5 cm</span>
                                </div>
                            </div>
                            <div class="pv-photo-meta">
                                <label class="form-label mb-1">Passport-size photo</label>
                                <div class="form-text mb-2" style="font-size:.74rem;">JPEG, PNG or WebP · max <?php echo e(round(((int) config('parivar.photo.max_kb', 2048)) / 1024, 1)); ?> MB · stored on R2</div>
                                <input type="file" name="photo" id="photoInput" class="d-none" accept="image/jpeg,image/jpg,image/png,image/webp">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-light btn-sm" id="btnPickPhoto">
                                        <i class="bi bi-upload me-1"></i><?php echo e($memberPhoto ? 'Replace photo' : 'Upload photo'); ?>

                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger<?php echo e($memberPhoto ? '' : ' d-none'); ?>" id="btnClearPhoto">
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
                                <input type="text" name="en_last_name" id="en_last_name" class="form-control" value="<?php echo e($old('en_last_name')); ?>" placeholder="Last name">
                                <div class="invalid-feedback" data-err="en_last_name"></div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="en_first_name" id="en_first_name" class="form-control" value="<?php echo e($old('en_first_name')); ?>" placeholder="First name">
                                <div class="invalid-feedback" data-err="en_first_name"></div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Father Name</label>
                                <input type="text" name="en_middle_name" class="form-control" value="<?php echo e($old('en_middle_name')); ?>" placeholder="Middle name">
                            </div>
                           
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">Grandfather name</label>
                                <input type="text" name="en_grandfather_name" class="form-control" value="<?php echo e($old('en_grandfather_name')); ?>" placeholder="Grandfather">
                            </div>
                        </div>

                        <div class="pv-sec-label">Gujarati name <span class="pv-sec-note">ગુજરાતી</span></div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">અટક</label>
                                <input type="text" name="gu_last_name" class="form-control gu-input" value="<?php echo e($old('gu_last_name')); ?>" placeholder="અટક" lang="gu">
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">નામ</label>
                                <input type="text" name="gu_first_name" class="form-control gu-input" value="<?php echo e($old('gu_first_name')); ?>" placeholder="નામ" lang="gu">
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">પિતાનું નામ</label>
                                <input type="text" name="gu_middle_name" class="form-control gu-input" value="<?php echo e($old('gu_middle_name')); ?>" placeholder="પિતાનું નામ" lang="gu">
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">દાદાનું નામ</label>
                                <input type="text" name="gu_grandfather_name" class="form-control gu-input" value="<?php echo e($old('gu_grandfather_name')); ?>" placeholder="દાદાનું નામ" lang="gu">
                            </div>
                        </div>

                        <div class="pv-sec-label">Identity</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Aadhaar number <span class="text-danger">*</span></label>
                                <?php
                                    $aadhaarVal = old('aadhaar_number', $isEdit ? ($member->aadhaar_number ?? '') : '');
                                    $aadhaarVal = \App\Models\Parivar\PersonalInfo::formatAadhaar($aadhaarVal);
                                ?>
                                <div class="input-group">
                                    <input type="text"
                                           name="aadhaar_number"
                                           id="aadhaar_number"
                                           class="form-control"
                                           value="<?php echo e($aadhaarVal); ?>"
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
                                       value="<?php echo e(old('date_of_birth', $isEdit && $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '')); ?>">
                                <div class="invalid-feedback" data-err="date_of_birth"></div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <?php $__currentLoopData = $genders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($val); ?>" <?php if($old('gender') === $val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label">Marital status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select</option>
                                    <?php $__currentLoopData = $maritals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($val); ?>" <?php if($old('marital_status') === $val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>

                        <div class="pv-sec-label">Contact</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Primary phone</label>
                                <div class="input-group">
                                    <select name="c_code_1" class="form-select" style="max-width:140px;">
                                        <?php $__currentLoopData = $codes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($code); ?>" <?php if($old('c_code_1', '+91') === $code): echo 'selected'; endif; ?>><?php echo e($code); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <input type="text" name="phone_1" class="form-control" value="<?php echo e($old('phone_1')); ?>" placeholder="Phone number" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secondary phone</label>
                                <div class="input-group">
                                    <select name="c_code_2" class="form-select" style="max-width:140px;">
                                        <option value="">—</option>
                                        <?php $__currentLoopData = $codes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($code); ?>" <?php if($old('c_code_2') === $code): echo 'selected'; endif; ?>><?php echo e($code); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <input type="text" name="phone_2" class="form-control" value="<?php echo e($old('phone_2')); ?>" placeholder="Optional" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Native place</label>
                                <input type="text" name="native_place" class="form-control" value="<?php echo e($old('native_place')); ?>" placeholder="Village / city">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <label class="pv-switch w-100 mb-0">
                                    <span>
                                        <strong>Gurukul student</strong>
                                        <small>Was / is this person a Gurukul student?</small>
                                    </span>
                                    <input type="hidden" name="is_gurukul_student" value="0">
                                    <input type="checkbox" name="is_gurukul_student" value="1" class="form-check-input" <?php if($old('is_gurukul_student', $isEdit ? $member->is_gurukul_student : false)): echo 'checked'; endif; ?>>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Current address</label>
                                <textarea name="current_address" class="form-control" rows="3" placeholder="House / street, area, city, pin"><?php echo e($old('current_address')); ?></textarea>
                                <div class="invalid-feedback" data-err="current_address"></div>
                            </div>
                        </div>

                        <div class="pv-actions">
                            <button type="submit" class="btn btn-primary pv-save" id="personalSave">
                                <span class="txt"><i class="bi bi-check-lg me-1"></i><?php echo e($isEdit ? 'Save Personal Info' : 'Create Member'); ?></span>
                                <span class="load d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                            </button>
                            <?php if (! ($isEdit)): ?>
                                <span class="pv-actions-note">Next: business &amp; gurukul unlock after this save.</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>

            
            <section class="pv-panel <?php echo e($tab === 'business' ? 'is-open' : ''); ?>" data-panel="business">
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span class="pv-hl">
                            <span class="pv-head-icon"><i class="bi bi-briefcase"></i></span>
                            <span class="pv-head-title">Business Information</span>
                        </span>
                        <span class="pv-head-badge" id="bizCount"><?php echo e($isEdit ? $member->businesses->count() : 0); ?> record(s)</span>
                    </div>
                    <div class="pv-body">
                        <div id="bizList" class="pv-list mb-4">
                            <?php if($isEdit): ?>
                                <?php $__empty_1 = true; $__currentLoopData = $member->businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $biz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="pv-item" data-id="<?php echo e($biz->id); ?>">
                                        <div>
                                            <div class="pv-item-title"><?php echo e($biz->business_name); ?></div>
                                            <div class="pv-item-sub"><?php echo e($biz->business_address ?: 'No address'); ?></div>
                                        </div>
                                        <div class="pv-item-actions">
                                            <button type="button" class="btn btn-sm btn-light btn-biz-edit" data-id="<?php echo e($biz->id); ?>"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-light text-danger btn-biz-del" data-id="<?php echo e($biz->id); ?>" data-name="<?php echo e($biz->business_name); ?>"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="pv-empty" id="bizEmpty">No business records yet.</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="pv-empty" id="bizEmpty">No business records yet.</div>
                            <?php endif; ?>
                        </div>

                        <form id="businessForm" novalidate autocomplete="off">
                            <?php echo csrf_field(); ?>
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

            
            <section class="pv-panel <?php echo e($tab === 'gurukul' ? 'is-open' : ''); ?>" data-panel="gurukul">
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span class="pv-hl">
                            <span class="pv-head-icon"><i class="bi bi-mortarboard"></i></span>
                            <span class="pv-head-title">Gurukul Information</span>
                        </span>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-light btn-sm" id="btnManageBranches"><i class="bi bi-building me-1"></i>Branches</button>
                            <button type="button" class="btn btn-light btn-sm" id="btnManageSaints"><i class="bi bi-person-badge me-1"></i>Saints</button>
                            <span class="pv-head-badge" id="gkCount"><?php echo e($isEdit ? $member->gurukulRecords->count() : 0); ?> record(s)</span>
                        </div>
                    </div>
                    <div class="pv-body">
                        <div id="gkList" class="pv-list mb-4">
                            <?php if($isEdit): ?>
                                <?php $__empty_1 = true; $__currentLoopData = $member->gurukulRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="pv-item" data-id="<?php echo e($gk->id); ?>">
                                        <div>
                                            <div class="pv-item-title"><?php echo e($gk->branch?->branch_name ?: 'Branch'); ?></div>
                                            <div class="pv-item-sub">
                                                <?php if($gk->start_year || $gk->end_year): ?>
                                                    <?php echo e($gk->start_year ?: '—'); ?><?php if($gk->end_year): ?> – <?php echo e($gk->end_year); ?><?php endif; ?>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                                <?php if($gk->total_year): ?>
                                                    · <?php echo e($gk->total_year); ?> yr
                                                <?php endif; ?>
                                                <?php if($gk->saints->isNotEmpty()): ?>
                                                    · <?php echo e($gk->saints->map->displayName()->implode(', ')); ?>

                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="pv-item-actions">
                                            <button type="button" class="btn btn-sm btn-light btn-gk-edit" data-id="<?php echo e($gk->id); ?>"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-light text-danger btn-gk-del" data-id="<?php echo e($gk->id); ?>" data-name="<?php echo e($gk->branch?->branch_name); ?>"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="pv-empty" id="gkEmpty">No gurukul records yet.</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="pv-empty" id="gkEmpty">No gurukul records yet.</div>
                            <?php endif; ?>
                        </div>

                        <form id="gurukulForm" novalidate autocomplete="off">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="section" value="gurukul">
                            <input type="hidden" name="record_id" id="gkRecordId" value="">
                            <div class="pv-sec-label" id="gkFormLabel">Add gurukul stay</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select name="branch_id" id="gkBranch" class="form-select">
                                        <option value="">Select branch</option>
                                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($b->id); ?>" data-active="<?php echo e($b->is_active ? 1 : 0); ?>">
                                                <?php echo e($b->branch_name); ?><?php echo e($b->is_active ? '' : ' (inactive)'); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <div class="invalid-feedback" data-err="branch_id"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Start year</label>
                                    <select name="start_year" id="gkStart" class="form-select">
                                        <option value="">Year</option>
                                        <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <div class="invalid-feedback" data-err="start_year"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">End year</label>
                                    <select name="end_year" id="gkEnd" class="form-select">
                                        <option value="">Year</option>
                                        <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Total years</label>
                                    <input type="number" name="total_year" id="gkTotal" class="form-control" min="0" max="80" placeholder="Auto">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Connected saints</label>
                                    <div class="pv-chips" id="gkSaints">
                                        <?php $__empty_1 = true; $__currentLoopData = $saints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <label class="pv-chip <?php echo e($s->is_active ? '' : 'is-inactive'); ?>">
                                                <input type="checkbox" name="saints[]" value="<?php echo e($s->id); ?>" class="gk-saint" <?php echo e($s->is_active ? '' : 'disabled'); ?>>
                                                <?php echo e($s->displayName()); ?>

                                            </label>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <span class="text-muted small">No saints yet. Add them from Saints.</span>
                                        <?php endif; ?>
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


<div class="modal fade" id="branchModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-building me-2"></i>Gurukul Branches</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="branchList" class="pv-list mb-3">
                    <?php $__empty_1 = true; $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="pv-item" data-id="<?php echo e($b->id); ?>">
                            <div>
                                <div class="pv-item-title"><?php echo e($b->branch_name); ?></div>
                                <div class="pv-item-sub"><?php echo e($b->is_active ? 'Active' : 'Inactive'); ?></div>
                            </div>
                            <div class="pv-item-actions">
                                <button type="button" class="btn btn-sm btn-light btn-br-edit" data-id="<?php echo e($b->id); ?>" data-name="<?php echo e($b->branch_name); ?>" data-active="<?php echo e($b->is_active ? 1 : 0); ?>"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-light text-danger btn-br-del" data-id="<?php echo e($b->id); ?>" data-name="<?php echo e($b->branch_name); ?>"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="pv-empty">No branches yet.</div>
                    <?php endif; ?>
                </div>
                <form id="branchForm" novalidate>
                    <?php echo csrf_field(); ?>
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


<div class="modal fade" id="saintModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Gurukul Saints</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="saintList" class="pv-list mb-3">
                    <?php $__empty_1 = true; $__currentLoopData = $saints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="pv-item" data-id="<?php echo e($s->id); ?>">
                            <div>
                                <div class="pv-item-title"><?php echo e($s->name); ?></div>
                                <div class="pv-item-sub"><?php echo e($s->number ?: 'No number'); ?> · <?php echo e($s->is_active ? 'Active' : 'Inactive'); ?></div>
                            </div>
                            <div class="pv-item-actions">
                                <button type="button" class="btn btn-sm btn-light btn-st-edit" data-id="<?php echo e($s->id); ?>" data-name="<?php echo e($s->name); ?>" data-number="<?php echo e($s->number); ?>" data-active="<?php echo e($s->is_active ? 1 : 0); ?>"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-light text-danger btn-st-del" data-id="<?php echo e($s->id); ?>" data-name="<?php echo e($s->name); ?>"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="pv-empty">No saints yet.</div>
                    <?php endif; ?>
                </div>
                <form id="saintForm" novalidate>
                    <?php echo csrf_field(); ?>
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

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
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
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var $app        = $('#pvApp');
    var hasMember   = $app.data('has-member') == 1;
    var memberId    = $app.data('member-id') || '';
    var createUrl   = $app.data('create-url');
    var updateUrl   = $app.data('update-url');
    var deleteUrl   = $app.data('delete-url');
    var genAadhaarUrl = $app.data('generate-aadhaar-url');
    var editBase    = $app.data('edit-base');
    var currentTab  = $app.data('tab') || 'personal';

    var bizStore = {};
    var gkStore  = {};

    <?php if($isEdit): ?>
        <?php $__currentLoopData = $member->businesses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $biz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            bizStore[<?php echo e((int) $biz->id); ?>] = <?php echo json_encode($biz->toFormArray(), 15, 512) ?>;
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php $__currentLoopData = $member->gurukulRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            gkStore[<?php echo e((int) $gk->id); ?>] = <?php echo json_encode($gk->toFormArray(), 15, 512) ?>;
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\Gurukul Parivar\resources\views/admin/parivar/form.blade.php ENDPATH**/ ?>