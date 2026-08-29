@extends('admin.layouts.app')

@section('title', 'Edit Module')
@section('page-title', 'Edit Module')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Modules</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit — {{ $module->name }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.modules.update', $module) }}">
                    @csrf @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Module Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $module->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $module->slug) }}" required>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Controller Name</label>
                            <input type="text" name="controller_name" class="form-control @error('controller_name') is-invalid @enderror"
                                   value="{{ old('controller_name', $module->controller_name) }}" required>
                            @error('controller_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Controller Path</label>
                            <input type="text" name="controller_path" class="form-control @error('controller_path') is-invalid @enderror"
                                   value="{{ old('controller_path', $module->controller_path) }}" required>
                            @error('controller_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Route Prefix</label>
                            <div class="input-group">
                                <span class="input-group-text text-muted">/admin/</span>
                                <input type="text" name="prefix" class="form-control" value="{{ old('prefix', $module->prefix) }}">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $module->description) }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', $module->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                        <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
