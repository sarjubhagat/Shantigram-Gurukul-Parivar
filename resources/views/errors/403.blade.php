@extends('errors.layout')

@section('title', '403 — Access Denied')

@section('body')
    <div class="err-icon-wrap" style="background:rgba(192,57,43,.1);">
        <i class="bi bi-shield-x" style="color:var(--accent);"></i>
    </div>

    <div class="err-code">403</div>

    <h1 class="err-title">Access Denied</h1>

    <p class="err-desc">
        You don't have permission to view this page.<br>
        If you think this is a mistake, please contact your administrator.
    </p>

    <div class="err-actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}" class="btn-ghost-err">
            <i class="bi bi-arrow-left"></i> Go Back
        </a>
        <a href="{{ url('/admin') }}" class="btn-primary-err">
            <i class="bi bi-house-door-fill"></i> Dashboard
        </a>
    </div>
@endsection
