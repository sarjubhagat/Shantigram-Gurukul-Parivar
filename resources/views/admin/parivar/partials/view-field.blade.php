@php
    $empty = $value === null || trim((string) $value) === '';
@endphp
<div class="pv-view-field{{ ! empty($full) ? ' full' : '' }}">
    <div class="pv-view-label">{{ $label }}</div>
    <div class="pv-view-val{{ $empty ? ' muted' : '' }}">{{ $empty ? '—' : $value }}</div>
</div>
