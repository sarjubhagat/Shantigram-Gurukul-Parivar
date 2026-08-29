@extends('errors.layout')

@section('title', '404 — Page Not Found')

@section('body')
    <div class="err-icon-wrap" style="background:rgba(173,57,57,.1);">
        <i class="bi bi-compass" style="color:var(--primary);"></i>
    </div>

    <div class="err-code">404</div>

    <h1 class="err-title">Page Not Found</h1>

    <p class="err-desc">
        The page you're looking for doesn't exist or may have been moved.<br>
        Double-check the URL or head back to the dashboard.
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
