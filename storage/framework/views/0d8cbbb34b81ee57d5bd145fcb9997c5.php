<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — <?php echo e(config('app.name')); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="<?php echo e(asset('assets/admin/css/admin.css')); ?>" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>


<script>
    var adminUrl    = "<?php echo e(url('/admin')); ?>";
    var moduleName  = "<?php echo e(config('module.name', '')); ?>";
    var moduleTitle = "<?php echo e(config('module.title', '')); ?>";
    var gridUrl     = moduleName ? adminUrl + '/' + moduleName + '/get_data' : null;
    var deleteUrl   = moduleName ? adminUrl + '/' + moduleName + '/delete'   : null;
    var exportUrl   = moduleName ? adminUrl + '/' + moduleName + '/export'   : null;
    var csrfToken   = "<?php echo e(csrf_token()); ?>";
</script>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-wrapper">
    <?php echo $__env->make('admin.layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="admin-main" id="adminMain">
        <?php echo $__env->make('admin.layouts.navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="page-wrapper">

            
            <div class="page-header">
                <div>
                    <h4 class="page-title"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?php echo e(route('admin.dashboard')); ?>"><i class="bi bi-house-door-fill"></i></a>
                            </li>
                            <?php echo $__env->yieldContent('breadcrumb'); ?>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php echo $__env->yieldContent('page-actions'); ?>
                </div>
            </div>

            
            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss mb-3" role="alert">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <span><?php echo e(session('success')); ?></span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                    <span><?php echo e(session('error')); ?></span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo e(asset('assets/admin/js/admin.js')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\projects\Gurukul Parivar\resources\views/admin/layouts/app.blade.php ENDPATH**/ ?>