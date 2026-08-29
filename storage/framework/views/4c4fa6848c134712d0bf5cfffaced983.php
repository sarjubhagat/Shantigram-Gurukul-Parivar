<?php
    $navUser     = auth()->user();
    $navRoleName = $navUser?->getRoleNames()->first();
    $navRoleName = $navRoleName ? ucwords(str_replace('-', ' ', $navRoleName)) : null;
?>

<header class="admin-navbar">

    <button class="nb-toggler" id="sidebarToggler" title="Toggle Sidebar">
        <i class="bi bi-list" style="font-size:1.45rem;"></i>
    </button>

    <div class="nb-search ms-1">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search...">
    </div>

    <div class="nb-right">

        <a href="#" class="nb-icon-btn" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="nb-badge">3</span>
        </a>

        <a href="#" class="nb-icon-btn" id="fullscreenBtn" title="Fullscreen">
            <i class="bi bi-fullscreen"></i>
        </a>

        <div class="nb-divider"></div>

        <div class="dropdown">
            <a class="nb-user-pill dropdown-toggle" href="#"
               data-bs-toggle="dropdown" aria-expanded="false"
               style="text-decoration:none;"
               title="<?php echo e($navUser?->name ?? 'Admin'); ?><?php echo e($navRoleName ? ' · ' . $navRoleName : ''); ?>">
                <div class="nb-user-avatar">
                    <?php echo e(strtoupper(substr($navUser?->name ?? 'A', 0, 1))); ?>

                </div>
                <div class="d-none d-md-flex flex-column" style="line-height:1.2;">
                    <span class="nb-user-name"><?php echo e($navUser?->name ?? 'Admin'); ?></span>
                    <?php if($navRoleName): ?>
                        <span style="font-size:.68rem;color:var(--text-muted);font-weight:400;"><?php echo e($navRoleName); ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end nb-dropdown">
                <li>
                    <div class="nb-dropdown-head">
                        <div class="fw-700 small" style="color:var(--text);font-weight:700;"><?php echo e($navUser?->name ?? 'Admin'); ?></div>
                        <div style="font-size:.73rem;color:var(--text-muted);"><?php echo e($navUser?->email ?? ''); ?></div>
                    </div>
                </li>
                <li><a class="dropdown-item" href="<?php echo e(route('admin.profile')); ?>"><i class="bi bi-person-circle"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="<?php echo e(route('admin.logout')); ?>" class="m-0">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="dropdown-item logout-item" style="width:100%;text-align:left;background:none;border:none;color:var(--danger);">
                            <i class="bi bi-power"></i> Sign Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>
</header>

<script>
document.getElementById('fullscreenBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    const icon = this.querySelector('i');
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        icon.className = 'bi bi-fullscreen-exit';
    } else {
        document.exitFullscreen();
        icon.className = 'bi bi-fullscreen';
    }
});
</script>
<?php /**PATH C:\projects\Food Management\resources\views/admin/layouts/navbar.blade.php ENDPATH**/ ?>