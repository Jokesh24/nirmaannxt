<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Cancel Reason | Nirmaan NXT Admin</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Add Cancel Reason</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_cancel_reason') }}" class="btn-action btn-manage">Manage Cancel Reasons</a>
            </div>
        </div>

        @if(Session::has('error') || $errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ Session::get('error') ?? 'Please check the form for errors.' }}
                @if($errors->any())
                    <ul class="mt-2 mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form class="property-form" action="{{ route('store_cancel_reason') }}" method="post" autocomplete="off">
            @csrf
            <div class="section-title">Cancel Reason Details</div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Cancel Reason Name <span class="req">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        value="{{ old('name') }}" placeholder="e.g. Out of Stock, Wrong Address" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="">Select Status</option>
                        <option value="Active" {{ old('status', 'Active') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Cancel Reason</button>
            </div>
        </form>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>