@extends('admin.layouts.app')

@php
    $display = $member['display_name'] ?: ($member['full_name'] ?? 'Member');
    $uid = $member['unique_id'] ?? '—';
    $score = $profileScore ?? ['score' => 0, 'level' => 'low', 'filled' => 0, 'total' => 0, 'missing' => [], 'items' => []];
    $tree = $familyTree ?? ['center' => [], 'parents' => [], 'spouses' => [], 'siblings' => [], 'children' => [], 'grandparents' => [], 'grandchildren' => [], 'others' => [], 'edge_count' => 0];
    $dups = $duplicates ?? [];
    $timeline = $audits ?? [];
@endphp

@section('title', $display . ' · #' . $uid)
@section('page-title', 'Member details')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/admin/parivar') }}">Parivar</a></li>
    <li class="breadcrumb-item active">#{{ $uid }}</li>
@endsection

@section('page-actions')
    <a href="{{ url('/admin/parivar') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Members
    </a>
    @can('parivar-edit')
        <a href="{{ $editUrl }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil-square me-1"></i> Edit
        </a>
    @endcan
@endsection

@section('content')
<div class="pv-view w-100">
    <div class="pv-view-hero">
        @if(! empty($member['photo_url']))
            <img src="{{ $member['photo_url'] }}" alt="" class="pv-view-photo">
        @else
            <div class="pv-view-photo-empty">{{ $member['initials'] ?? '?' }}</div>
        @endif
        <div class="min-w-0 flex-grow-1">
            <div class="pv-view-name">{{ $display }}</div>
            @if(! empty($member['gu_full_name']))
                <div class="pv-view-gu">{{ $member['gu_full_name'] }}</div>
            @endif
            <div class="pv-view-meta">
                <span class="uid-badge">#{{ $uid }}</span>
                @if(! empty($member['aadhaar_formatted']))
                    <span>· Aadhaar {{ $member['aadhaar_formatted'] }}</span>
                @endif
                @if(! empty($member['primary_phone']))
                    <span>· {{ $member['primary_phone'] }}</span>
                @endif
            </div>
        </div>
        <div class="pv-score pv-score-{{ $score['level'] }}" title="Profile completeness">
            <div class="pv-score-ring">
                <svg viewBox="0 0 36 36" aria-hidden="true">
                    <path class="pv-score-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="pv-score-fg" stroke-dasharray="{{ (int) $score['score'] }}, 100"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <span class="pv-score-num">{{ (int) $score['score'] }}%</span>
            </div>
            <div class="pv-score-meta">
                <div class="pv-score-label">Profile score</div>
                <div class="pv-score-sub">{{ $score['filled'] }}/{{ $score['total'] }} complete</div>
            </div>
        </div>
    </div>

    @if(! empty($score['missing']))
        <div class="pv-view-card pv-missing mb-3">
            <div class="pv-view-sec">Improve this profile</div>
            <div class="pv-missing-list">
                @foreach($score['missing'] as $m)
                    <span class="pv-missing-chip"><i class="bi bi-circle me-1"></i>{{ $m['label'] }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Family tree --}}
    <div class="pv-view-card mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="pv-view-sec mb-0">Family tree</div>
            <span class="pv-view-empty">{{ (int) ($tree['edge_count'] ?? 0) }} linked · click a person to open</span>
        </div>

        @if(($tree['edge_count'] ?? 0) < 1)
            <div class="pv-view-empty py-3">No family relations yet. Add relations on the edit page to build this tree.</div>
        @else
            <div class="ft-wrap">
                @if(! empty($tree['grandparents']))
                    <div class="ft-gen">
                        <div class="ft-gen-label">Grandparents</div>
                        <div class="ft-row">
                            @foreach($tree['grandparents'] as $n)
                                @include('admin.parivar.partials.tree-node', ['node' => $n])
                            @endforeach
                        </div>
                    </div>
                    <div class="ft-line"></div>
                @endif

                @if(! empty($tree['parents']))
                    <div class="ft-gen">
                        <div class="ft-gen-label">Parents</div>
                        <div class="ft-row">
                            @foreach($tree['parents'] as $n)
                                @include('admin.parivar.partials.tree-node', ['node' => $n])
                            @endforeach
                        </div>
                    </div>
                    <div class="ft-line"></div>
                @endif

                <div class="ft-gen">
                    <div class="ft-gen-label">This generation</div>
                    <div class="ft-row ft-row-main">
                        @foreach($tree['siblings'] as $n)
                            @include('admin.parivar.partials.tree-node', ['node' => $n])
                        @endforeach
                        @include('admin.parivar.partials.tree-node', ['node' => $tree['center'], 'center' => true])
                        @foreach($tree['spouses'] as $n)
                            @include('admin.parivar.partials.tree-node', ['node' => $n])
                        @endforeach
                    </div>
                </div>

                @if(! empty($tree['children']))
                    <div class="ft-line"></div>
                    <div class="ft-gen">
                        <div class="ft-gen-label">Children</div>
                        <div class="ft-row">
                            @foreach($tree['children'] as $n)
                                @include('admin.parivar.partials.tree-node', ['node' => $n])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(! empty($tree['grandchildren']))
                    <div class="ft-line"></div>
                    <div class="ft-gen">
                        <div class="ft-gen-label">Grandchildren</div>
                        <div class="ft-row">
                            @foreach($tree['grandchildren'] as $n)
                                @include('admin.parivar.partials.tree-node', ['node' => $n])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(! empty($tree['others']))
                    <div class="ft-line"></div>
                    <div class="ft-gen">
                        <div class="ft-gen-label">Other relations</div>
                        <div class="ft-row">
                            @foreach($tree['others'] as $n)
                                @include('admin.parivar.partials.tree-node', ['node' => $n])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if(count($dups) > 0)
        <div class="pv-view-card pv-dup mb-3">
            <div class="pv-view-sec">Possible duplicates <span class="pv-dup-count">{{ count($dups) }}</span></div>
            <div class="pv-dup-list">
                @foreach($dups as $d)
                    <div class="pv-dup-item">
                        <div class="min-w-0">
                            <div class="pv-view-row-title">{{ $d['full_name'] }} <span class="uid-badge">#{{ $d['unique_id'] }}</span></div>
                            <div class="pv-view-row-sub">
                                {{ implode(' · ', $d['reasons']) }}
                                @if(! empty($d['phone'])) · {{ $d['phone'] }} @endif
                            </div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <span class="pv-dup-score">{{ $d['score'] }}%</span>
                            <a href="{{ $d['view_url'] }}" class="btn btn-sm btn-light" target="_blank" rel="noopener" title="View"><i class="bi bi-eye"></i></a>
                            @can('parivar-edit')
                                <a href="{{ $d['edit_url'] }}" class="btn btn-sm btn-light" target="_blank" rel="noopener" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="pv-view-card">
                <div class="pv-view-sec">Personal</div>
                <div class="pv-view-grid">
                    @include('admin.parivar.partials.view-field', ['label' => 'Surname', 'value' => $member['en_last_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Name', 'value' => $member['en_first_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Father name', 'value' => $member['en_middle_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Grandfather', 'value' => $member['en_grandfather_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Display name', 'value' => $member['display_name'] ?? null, 'full' => true])
                    @include('admin.parivar.partials.view-field', ['label' => 'Date of birth', 'value' => $member['date_of_birth_fmt'] ?? ($member['date_of_birth'] ?? null)])
                    @include('admin.parivar.partials.view-field', ['label' => 'Gender', 'value' => $member['gender_label'] ?? ($member['gender'] ?? null)])
                    @include('admin.parivar.partials.view-field', ['label' => 'Marital status', 'value' => $member['marital_label'] ?? ($member['marital_status'] ?? null)])
                    @include('admin.parivar.partials.view-field', ['label' => 'Gurukul student', 'value' => ! empty($member['is_gurukul_student']) ? 'Yes' : 'No'])
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="pv-view-card h-100">
                <div class="pv-view-sec">Gujarati name</div>
                <div class="pv-view-grid">
                    @include('admin.parivar.partials.view-field', ['label' => 'અટક', 'value' => $member['gu_last_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'નામ', 'value' => $member['gu_first_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'પિતાનું નામ', 'value' => $member['gu_middle_name'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'દાદાનું નામ', 'value' => $member['gu_grandfather_name'] ?? null])
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="pv-view-card">
                <div class="pv-view-sec">Contact</div>
                <div class="pv-view-grid pv-view-grid-4">
                    @include('admin.parivar.partials.view-field', ['label' => 'Primary phone', 'value' => $member['primary_phone'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Secondary phone', 'value' => $member['secondary_phone'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Native place', 'value' => $member['native_place'] ?? null])
                    @include('admin.parivar.partials.view-field', ['label' => 'Current address', 'value' => $member['current_address'] ?? null, 'full' => true])
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="pv-view-card">
                <div class="pv-view-sec">Family relations</div>
                @forelse($relations as $rel)
                    <div class="pv-view-row">
                        <div class="min-w-0">
                            <div class="pv-view-row-title">{{ $rel['label'] ?? (($rel['relation_name'] ?? '') . ' · ' . ($rel['related_name'] ?? '')) }}</div>
                            <div class="pv-view-row-sub">
                                #{{ $rel['related_unique_id'] ?? '—' }}
                                @if(! empty($rel['relation_name'])) · {{ $rel['relation_name'] }} @endif
                            </div>
                        </div>
                        @if(! empty($rel['related_personal_info_id']))
                            <a href="{{ url('/admin/parivar/' . $rel['related_personal_info_id'] . '/view') }}"
                               class="btn btn-sm btn-light flex-shrink-0" target="_blank" rel="noopener" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="pv-view-empty">No relations linked.</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-6">
            <div class="pv-view-card">
                <div class="pv-view-sec">Business</div>
                @forelse($businesses as $biz)
                    <div class="pv-view-row">
                        <div class="min-w-0">
                            <div class="pv-view-row-title">{{ $biz['business_name'] ?? 'Business' }}</div>
                            <div class="pv-view-row-sub">{{ $biz['business_address'] ?: 'No address' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="pv-view-empty">No business records.</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-6">
            <div class="pv-view-card">
                <div class="pv-view-sec">Gurukul</div>
                @forelse($gurukul as $gk)
                    @php
                        $years = '';
                        if (! empty($gk['start_year']) || ! empty($gk['end_year'])) {
                            $years = ($gk['start_year'] ?? '—') . (! empty($gk['end_year']) ? ' – ' . $gk['end_year'] : '');
                        }
                        $saints = implode(', ', $gk['saint_names'] ?? []);
                    @endphp
                    <div class="pv-view-row">
                        <div class="min-w-0">
                            <div class="pv-view-row-title">{{ $gk['branch_name'] ?? 'Branch' }}</div>
                            <div class="pv-view-row-sub">
                                {{ $years ?: '—' }}
                                @if(! empty($gk['total_year'])) · {{ $gk['total_year'] }} yr @endif
                                @if($saints !== '') · {{ $saints }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="pv-view-empty">No gurukul records.</div>
                @endforelse
            </div>
        </div>

        <div class="col-12">
            <div class="pv-view-card">
                <div class="pv-view-sec">Audit timeline</div>
                @forelse($timeline as $ev)
                    <div class="pv-tl-item">
                        <div class="pv-tl-dot"></div>
                        <div class="min-w-0">
                            <div class="pv-view-row-title">{{ $ev['summary'] }}</div>
                            <div class="pv-view-row-sub">
                                {{ $ev['created_at'] ?? '—' }}
                                @if(! empty($ev['actor'])) · {{ $ev['actor'] }} @endif
                                @if(! empty($ev['field'])) · {{ str_replace('_', ' ', $ev['field']) }} @endif
                            </div>
                            @if(($ev['old_value'] ?? null) !== null || ($ev['new_value'] ?? null) !== null)
                                <div class="pv-tl-diff">
                                    @if(($ev['old_value'] ?? '') !== '')
                                        <span class="pv-tl-old">{{ \Illuminate\Support\Str::limit($ev['old_value'], 80) }}</span>
                                    @endif
                                    @if(($ev['old_value'] ?? '') !== '' && ($ev['new_value'] ?? '') !== '')
                                        <i class="bi bi-arrow-right mx-1"></i>
                                    @endif
                                    @if(($ev['new_value'] ?? '') !== '')
                                        <span class="pv-tl-new">{{ \Illuminate\Support\Str::limit($ev['new_value'], 80) }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pv-view-empty">No history yet. Changes from now on will appear here.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600&display=swap" rel="stylesheet">
<style>
.pv-view { width: 100%; max-width: none; }
.pv-view-hero {
    display: flex; gap: 1.15rem; align-items: flex-start; flex-wrap: wrap;
    background: #fff; border: 1.5px solid var(--border-light); border-radius: var(--r-lg);
    padding: 1.15rem 1.25rem; margin-bottom: 1rem;
    box-shadow: 0 2px 12px rgba(139,26,26,.06);
}
.pv-view-photo {
    width: 96px; height: 128px; object-fit: cover; border-radius: 10px;
    border: 1px solid var(--border); background: #f3f3f3; flex-shrink: 0;
}
.pv-view-photo-empty {
    width: 96px; height: 128px; border-radius: 10px; flex-shrink: 0;
    border: 1.5px dashed var(--border); background: var(--bg);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: var(--primary); font-size: 1.35rem;
}
.pv-view-name { font-size: 1.25rem; font-weight: 700; color: var(--text); line-height: 1.25; }
.pv-view-gu { font-size: .9rem; color: var(--text-muted); font-family: 'Noto Sans Gujarati', inherit; margin-top: .2rem; }
.pv-view-meta { font-size: .82rem; color: var(--text-muted); margin-top: .45rem; display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
.pv-view .uid-badge {
    font-family: ui-monospace, monospace; font-size: .78rem; font-weight: 700; color: var(--primary);
    background: rgba(139,26,26,.08); border-radius: .4rem; padding: .2em .55em;
}
.pv-score { display: flex; align-items: center; gap: .7rem; margin-left: auto; }
.pv-score-ring { position: relative; width: 64px; height: 64px; flex-shrink: 0; }
.pv-score-ring svg { width: 64px; height: 64px; transform: rotate(-90deg); }
.pv-score-bg { fill: none; stroke: #eee; stroke-width: 3.2; }
.pv-score-fg { fill: none; stroke: var(--primary); stroke-width: 3.2; stroke-linecap: round; }
.pv-score-excellent .pv-score-fg { stroke: #1f8a4c; }
.pv-score-good .pv-score-fg { stroke: #2f6fed; }
.pv-score-fair .pv-score-fg { stroke: #c47a00; }
.pv-score-low .pv-score-fg { stroke: #c0392b; }
.pv-score-num {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: .78rem; font-weight: 800; color: var(--text);
}
.pv-score-label { font-size: .78rem; font-weight: 700; color: var(--text); }
.pv-score-sub { font-size: .72rem; color: var(--text-muted); }
.pv-missing-list { display: flex; flex-wrap: wrap; gap: .4rem; }
.pv-missing-chip {
    display: inline-flex; align-items: center; font-size: .74rem; font-weight: 600;
    color: #8a5a00; background: rgba(255,214,102,.25); border: 1px solid rgba(180,120,20,.3);
    border-radius: 2rem; padding: .28em .7em;
}
.pv-view-card {
    background: #fff; border: 1.5px solid var(--border-light); border-radius: var(--r-lg);
    padding: 1.05rem 1.15rem; box-shadow: 0 1px 6px rgba(0,0,0,.03); overflow: hidden;
}
.pv-view-sec {
    font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: var(--primary); margin-bottom: .75rem;
}
.pv-view-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem 1.1rem; }
.pv-view-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.pv-view-grid-4 .pv-view-field.full, .pv-view-field.full { grid-column: 1 / -1; }
.pv-view-label { font-size: .68rem; color: var(--text-muted); font-weight: 600; margin-bottom: .12rem; }
.pv-view-val { font-size: .88rem; color: var(--text); font-weight: 600; word-break: break-word; line-height: 1.35; }
.pv-view-val.muted { color: var(--text-muted); font-weight: 500; }
.pv-view-row {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    border-top: 1px solid var(--border-light); padding: .6rem 0;
}
.pv-view-card > .pv-view-row:first-of-type,
.pv-view-card > .pv-view-empty { border-top: 0; padding-top: 0; }
.pv-view-row-title { font-size: .88rem; font-weight: 700; color: var(--text); }
.pv-view-row-sub { font-size: .76rem; color: var(--text-muted); margin-top: .12rem; }
.pv-view-empty { font-size: .84rem; color: var(--text-muted); }

/* Family tree */
.ft-wrap {
    background: linear-gradient(180deg, rgba(139,26,26,.03), #fff 30%);
    border: 1.5px dashed rgba(139,26,26,.18); border-radius: .85rem;
    padding: 1rem .75rem 1.15rem; overflow-x: auto;
}
.ft-gen { text-align: center; }
.ft-gen-label {
    font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    color: var(--text-muted); margin-bottom: .55rem;
}
.ft-row { display: flex; flex-wrap: wrap; justify-content: center; gap: .65rem; align-items: stretch; }
.ft-line {
    width: 2px; height: 18px; margin: .35rem auto;
    background: linear-gradient(180deg, rgba(139,26,26,.35), rgba(139,26,26,.12));
    border-radius: 2px;
}
.ft-node {
    width: 118px; text-decoration: none !important; color: inherit;
    background: #fff; border: 1.5px solid var(--border); border-radius: .75rem;
    padding: .55rem .45rem .5rem; display: flex; flex-direction: column; align-items: center;
    gap: .2rem; transition: transform .15s, box-shadow .15s, border-color .15s;
}
.ft-node:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(139,26,26,.12); border-color: var(--primary); }
.ft-node.is-center {
    width: 132px; border-color: var(--primary); background: rgba(139,26,26,.05);
    box-shadow: 0 4px 14px rgba(139,26,26,.12); cursor: default;
}
.ft-node.is-center:hover { transform: none; }
.ft-node.is-male { border-top: 3px solid #2f6fed; }
.ft-node.is-female { border-top: 3px solid #c2185b; }
.ft-photo { width: 44px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); }
.ft-initials {
    width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    background: rgba(139,26,26,.1); color: var(--primary); font-weight: 800; font-size: .85rem;
}
.ft-name { font-size: .76rem; font-weight: 700; text-align: center; line-height: 1.25; color: var(--text); max-width: 100%; }
.ft-rel { font-size: .64rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: .03em; }
.ft-uid { font-size: .62rem; color: var(--text-muted); font-family: ui-monospace, monospace; }

/* Duplicates */
.pv-dup { border-color: rgba(192,57,43,.28); background: linear-gradient(180deg, rgba(192,57,43,.05), #fff 40%); }
.pv-dup-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 1.4rem; height: 1.2rem; padding: 0 .4rem; margin-left: .35rem;
    border-radius: 2rem; background: rgba(192,57,43,.12); color: #c0392b; font-size: .68rem;
}
.pv-dup-item {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    border-top: 1px solid var(--border-light); padding: .55rem 0;
}
.pv-dup-list .pv-dup-item:first-child { border-top: 0; padding-top: 0; }
.pv-dup-score { font-size: .72rem; font-weight: 800; color: #c0392b; align-self: center; }

/* Timeline */
.pv-tl-item {
    position: relative; display: flex; gap: .75rem; padding: .55rem 0 .55rem .15rem;
    border-top: 1px solid var(--border-light);
}
.pv-view-card > .pv-tl-item:first-of-type { border-top: 0; padding-top: 0; }
.pv-tl-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem;
    background: var(--primary); box-shadow: 0 0 0 3px rgba(139,26,26,.12);
}
.pv-tl-diff { font-size: .74rem; margin-top: .25rem; display: flex; flex-wrap: wrap; align-items: center; }
.pv-tl-old { color: #c0392b; text-decoration: line-through; opacity: .85; }
.pv-tl-new { color: #1f8a4c; font-weight: 600; }

@media (max-width: 1199.98px) {
    .pv-view-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
    .pv-view-grid, .pv-view-grid-4 { grid-template-columns: 1fr; }
    .pv-score { margin-left: 0; width: 100%; }
}
</style>
@endpush
