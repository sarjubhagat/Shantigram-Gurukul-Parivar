<?php
    $navModules  = \App\Models\AccessCenter\Module::activeAdminModules();
    $sidebarUser = auth()->user();
    $navGrouped  = $navModules->groupBy(fn ($m) => $m->section?->section_name ?? '');

    $biIcon = function (?string $icon, string $default = 'bi-box-seam'): string {
        $icon = trim($icon ?: $default);

        return str_starts_with($icon, 'bi ') ? $icon : 'bi ' . $icon;
    };

    $navRoleName = $sidebarUser?->getRoleNames()->first();
    $navRoleName = $navRoleName ? ucwords(str_replace('-', ' ', $navRoleName)) : 'User';
?>

<aside class="admin-sidebar" id="adminSidebar">

    
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="sidebar-brand">
        <div class="brand-emblem">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="brand-info">
            <span class="brand-name"><?php echo e(config('app.name')); ?></span>
            <span class="brand-sub">Admin Panel</span>
        </div>
    </a>

    <nav class="sidebar-nav" aria-label="Admin navigation">

        <div class="nav-block">
            <div class="nav-group-label">
                <span class="nav-group-label-text">Main</span>
            </div>
            <a href="<?php echo e(route('admin.dashboard')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
                <span class="sidebar-link-icon"><i class="bi bi-grid-fill"></i></span>
                <span class="sidebar-link-text">Dashboard</span>
            </a>
        </div>

        <?php $__currentLoopData = $navGrouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sectionLabel => $mods): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $visibleMods = $mods->filter(fn ($m) => $sidebarUser?->can($m->name . '-list'));
                if ($visibleMods->isEmpty()) {
                    continue;
                }

                $section     = $visibleMods->first()?->section;
                $secSlug     = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $sectionLabel ?: 'general'));
                $hasActive   = $visibleMods->contains(fn ($m) => request()->segment(2) === $m->name);
                $secIcon     = $biIcon($section?->icon, 'bi-collection');
            ?>

            <div class="nav-section<?php echo e($hasActive ? ' nav-section--active' : ''); ?><?php echo e($hasActive ? '' : ' collapsed'); ?>"
                 data-sec="<?php echo e($secSlug); ?>">

                <button type="button"
                        class="nav-section-toggle"
                        aria-expanded="<?php echo e($hasActive ? 'true' : 'false'); ?>">
                    <span class="nav-section-toggle-left">
                        <span class="nav-section-icon"><i class="<?php echo e($secIcon); ?>"></i></span>
                        <span class="nav-section-title"><?php echo e($sectionLabel ?: 'General'); ?></span>
                    </span>
                    <i class="bi bi-chevron-down nav-sec-chevron" aria-hidden="true"></i>
                </button>

                <div class="nav-section-items">
                    <div class="nav-section-items-inner">
                    <?php $__currentLoopData = $visibleMods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(url('/admin/' . $mod->name)); ?>"
                           class="sidebar-link sidebar-link--nested <?php echo e(request()->segment(2) === $mod->name ? 'active' : ''); ?>">
                            <span class="sidebar-link-icon"><i class="<?php echo e($biIcon($mod->icon)); ?>"></i></span>
                            <span class="sidebar-link-text"><?php echo e($mod->title); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    </nav>

    <div class="sidebar-footer">
        <div class="sb-avatar" aria-hidden="true">
            <?php echo e(strtoupper(substr($sidebarUser?->name ?? 'A', 0, 1))); ?>

        </div>
        <div class="sb-user-info">
            <span class="sb-user-name"><?php echo e($sidebarUser?->name ?? 'Admin'); ?></span>
            <span class="sb-user-role"><?php echo e($navRoleName); ?></span>
        </div>
        <form action="<?php echo e(route('admin.logout')); ?>" method="POST" class="m-0">
            <?php echo csrf_field(); ?>
            <button type="submit" class="sb-logout" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>

</aside>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    function setExpanded(sec, open) {
        sec.classList.toggle('collapsed', !open);
        var btn = sec.querySelector('.nav-section-toggle');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeAllExcept(target) {
        document.querySelectorAll('.nav-section').forEach(function (sec) {
            setExpanded(sec, sec === target);
        });
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('.nav-section-toggle');
        if (!toggle) return;

        var sec      = toggle.closest('.nav-section');
        var isOpen   = !sec.classList.contains('collapsed');

        if (isOpen) {
            setExpanded(sec, false);
        } else {
            closeAllExcept(sec);
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\projects\serve\resources\views/admin/layouts/sidebar.blade.php ENDPATH**/ ?>