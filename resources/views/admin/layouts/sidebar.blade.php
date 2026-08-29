@php
    $navModules  = \App\Models\AccessCenter\Module::activeAdminModules();
    $sidebarUser = auth()->user();
    $navGrouped  = $navModules->groupBy(fn ($m) => $m->section?->section_name ?? '');

    $biIcon = function (?string $icon, string $default = 'bi-box-seam'): string {
        $icon = trim($icon ?: $default);

        return str_starts_with($icon, 'bi ') ? $icon : 'bi ' . $icon;
    };

    $navRoleName = $sidebarUser?->getRoleNames()->first();
    $navRoleName = $navRoleName ? ucwords(str_replace('-', ' ', $navRoleName)) : 'User';
@endphp

<aside class="admin-sidebar" id="adminSidebar">

    {{-- Brand --}}
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
        <div class="brand-emblem">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="brand-info">
            <span class="brand-name">{{ config('app.name') }}</span>
            <span class="brand-sub">Admin Panel</span>
        </div>
    </a>

    <nav class="sidebar-nav" aria-label="Admin navigation">

        <div class="nav-block">
            <div class="nav-group-label">
                <span class="nav-group-label-text">Main</span>
            </div>
            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="bi bi-grid-fill"></i></span>
                <span class="sidebar-link-text">Dashboard</span>
            </a>
        </div>

        @foreach($navGrouped as $sectionLabel => $mods)
            @php
                $visibleMods = $mods->filter(fn ($m) => $sidebarUser?->can($m->name . '-list'));
                if ($visibleMods->isEmpty()) {
                    continue;
                }

                $section     = $visibleMods->first()?->section;
                $secSlug     = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $sectionLabel ?: 'general'));
                $hasActive   = $visibleMods->contains(fn ($m) => request()->segment(2) === $m->name);
                $secIcon     = $biIcon($section?->icon, 'bi-collection');
            @endphp

            <div class="nav-section{{ $hasActive ? ' nav-section--active' : '' }}{{ $hasActive ? '' : ' collapsed' }}"
                 data-sec="{{ $secSlug }}">

                <button type="button"
                        class="nav-section-toggle"
                        aria-expanded="{{ $hasActive ? 'true' : 'false' }}">
                    <span class="nav-section-toggle-left">
                        <span class="nav-section-icon"><i class="{{ $secIcon }}"></i></span>
                        <span class="nav-section-title">{{ $sectionLabel ?: 'General' }}</span>
                    </span>
                    <i class="bi bi-chevron-down nav-sec-chevron" aria-hidden="true"></i>
                </button>

                <div class="nav-section-items">
                    <div class="nav-section-items-inner">
                    @foreach($visibleMods as $mod)
                        <a href="{{ url('/admin/' . $mod->name) }}"
                           class="sidebar-link sidebar-link--nested {{ request()->segment(2) === $mod->name ? 'active' : '' }}">
                            <span class="sidebar-link-icon"><i class="{{ $biIcon($mod->icon) }}"></i></span>
                            <span class="sidebar-link-text">{{ $mod->title }}</span>
                        </a>
                    @endforeach
                    </div>
                </div>
            </div>
        @endforeach

    </nav>

    <div class="sidebar-footer">
        <div class="sb-avatar" aria-hidden="true">
            {{ strtoupper(substr($sidebarUser?->name ?? 'A', 0, 1)) }}
        </div>
        <div class="sb-user-info">
            <span class="sb-user-name">{{ $sidebarUser?->name ?? 'Admin' }}</span>
            <span class="sb-user-role">{{ $navRoleName }}</span>
        </div>
        <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="sb-logout" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>

</aside>

@push('scripts')
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
@endpush
