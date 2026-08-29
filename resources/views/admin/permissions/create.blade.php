@extends('admin.layouts.app')

@section('title', 'Create Permission')
@section('page-title', 'Create Permission')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-5 col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-key me-2"></i>New Permission</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.permissions.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Permission Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. users.view" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Convention: <code>module.action</code> — e.g. <code>users.create</code>, <code>roles.edit</code></div>
                    </div>

                    <div class="alert alert-light border mb-3" style="font-size:.82rem;">
                        <strong>Common actions:</strong>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach(['view','create','edit','delete','export'] as $a)
                                <code class="bg-white border rounded px-2 py-1 small">{{ $a }}</code>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create</button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
