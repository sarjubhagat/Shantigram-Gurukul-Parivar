@php
    $isCenter = ! empty($center);
    $name = $node['short_name'] ?? ($node['name'] ?? '—');
    $rel = $node['relation'] ?? null;
    $genderClass = ($node['gender'] ?? '') === 'female' ? ' is-female' : (($node['gender'] ?? '') === 'male' ? ' is-male' : '');
    $tag = $isCenter ? 'div' : 'a';
@endphp
<{{ $tag }}
   @if(! $isCenter) href="{{ $node['view_url'] ?? '#' }}" target="_blank" rel="noopener" @endif
   class="ft-node{{ $isCenter ? ' is-center' : '' }}{{ $genderClass }}"
   title="{{ $node['name'] ?? $name }}">
    @if(! empty($node['photo_url']))
        <img src="{{ $node['photo_url'] }}" alt="" class="ft-photo">
    @else
        <span class="ft-initials">{{ $node['initials'] ?? '?' }}</span>
    @endif
    <span class="ft-name">{{ $name }}</span>
    @if($rel)
        <span class="ft-rel">{{ $rel }}</span>
    @elseif($isCenter)
        <span class="ft-rel">You</span>
    @endif
    <span class="ft-uid">#{{ $node['unique_id'] ?? '—' }}</span>
</{{ $tag }}>
