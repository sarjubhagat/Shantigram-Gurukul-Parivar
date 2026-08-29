@extends('errors.layout')

@section('title', '419 — Session Expired')

@section('body')
    <div class="err-icon-wrap" style="background:rgba(184,134,11,.1);">
        <i class="bi bi-clock-history" style="color:var(--gold);"></i>
    </div>

    <div class="err-code" style="background:linear-gradient(135deg,#8B6914 0%,var(--gold) 100%);-webkit-background-clip:text;background-clip:text;">419</div>

    <h1 class="err-title">Session Expired</h1>

    <p class="err-desc">
        Your session has expired due to inactivity.<br>
        Please refresh the page or log in again to continue.
    </p>

    <div class="err-actions">
        <a href="javascript:location.reload()" class="btn-ghost-err">
            <i class="bi bi-arrow-clockwise"></i> Refresh Page
        </a>
        <a href="{{ route('login') }}" class="btn-primary-err">
            <i class="bi bi-box-arrow-in-right"></i> Log In Again
        </a>
    </div>
@endsection
