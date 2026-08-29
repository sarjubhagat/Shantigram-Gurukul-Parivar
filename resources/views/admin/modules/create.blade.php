@extends('admin.layouts.app')

@section('title', 'Create Module')
@section('page-title', 'Create Module')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Modules</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">
        <div class="card">
            <div class="card-header"><i class="bi bi-box me-2"></i>New Module</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.modules.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Module Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Blog" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}" placeholder="e.g. blog" required>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Used as permission prefix: <code>blog.view</code></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Controller Name</label>
                            <input type="text" name="controller_name" class="form-control @error('controller_name') is-invalid @enderror"
                                   value="{{ old('controller_name') }}" placeholder="BlogController" required>
                            @error('controller_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Controller Path (namespace)</label>
                            <input type="text" name="controller_path" class="form-control @error('controller_path') is-invalid @enderror"
                                   value="{{ old('controller_path') }}" placeholder="App\Http\Controllers\Admin" required>
                            @error('controller_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Route Prefix <span class="text-muted fw-normal">(optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-muted">/admin/</span>
                                <input type="text" name="prefix" class="form-control" value="{{ old('prefix') }}" placeholder="blog">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Short description">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Module</button>
                        <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[name="name"]').addEventListener('input', function () {
    const slug = document.querySelector('[name="slug"]');
    if (!slug.dataset.edited) {
        slug.value = this.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    }
});
document.querySelector('[name="slug"]').addEventListener('input', function () {
    this.dataset.edited = '1';
});
</script>
@endpush
