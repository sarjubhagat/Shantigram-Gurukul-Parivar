<?php $__env->startSection('title', '500 — Server Error'); ?>

<?php $__env->startSection('body'); ?>
    <div class="err-icon-wrap" style="background:rgba(139,26,26,.1);">
        <i class="bi bi-exclamation-triangle" style="color:var(--primary-dark);"></i>
    </div>

    <div class="err-code">500</div>

    <h1 class="err-title">Internal Server Error</h1>

    <p class="err-desc">
        Something went wrong on our end. We're already working on fixing it.<br>
        Please try again in a moment.
    </p>

    <div class="err-actions">
        <a href="javascript:location.reload()" class="btn-ghost-err">
            <i class="bi bi-arrow-clockwise"></i> Try Again
        </a>
        <a href="<?php echo e(url('/admin')); ?>" class="btn-primary-err">
            <i class="bi bi-house-door-fill"></i> Dashboard
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\Gurukul Parivar\resources\views/errors/500.blade.php ENDPATH**/ ?>