<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @include('admin.includes.header_links')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <style>
        .cke_chrome {
            border-radius: 14px !important;
            border: 1px solid #dbe3ec !important;
            overflow: hidden;
        }

        .section-header {
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
            color: #333;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">{{ $title }}</h1>
                <div class="section-title">Manage Application Content</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_content', $type) }}" class="btn-action btn-manage">Manage Records</a>
            </div>
        </div>

        @if(Session::has('error') || $errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                {{ Session::get('error') ?? 'Please correct the highlighted errors below.' }}
                @if($errors->any())
                    <ul class="mt-2 mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form class="property-form"
            action="{{ $record ? route('update_content', ['type' => $type, 'id' => $record->id]) : route('store_content', $type) }}"
            method="post">
            @csrf

            <div class="section-header">1. Content Details</div>

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active" {{ old('status', isset($record) && $record->status == 1 ? 'Active' : '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', isset($record) && $record->status == 0 ? 'Inactive' : '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-12">
                    <label class="form-label">Main Content / Description <span class="req">*</span></label>
                    <textarea class="form-control" id="description" name="description"
                        required>{{ old('description', $record->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">{{ $record ? 'Update Record' : 'Save Record' }}</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')

    <script>
        // Apply CKEditor to the text area
        CKEDITOR.replace('description', {
            height: 400,
            removeButtons: 'Save,NewPage,ExportPdf,Preview,Print,Templates',
            uiColor: '#f8f9fa'
        });
    </script>
</body>

</html>