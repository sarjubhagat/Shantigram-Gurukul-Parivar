@extends('errors.layout')

@section('title', '500 — Server Error')

@section('body')
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
        <a href="{{ url('/admin') }}" class="btn-primary-err">
            <i class="bi bi-house-door-fill"></i> Dashboard
        </a>
    </div>
@endsection
