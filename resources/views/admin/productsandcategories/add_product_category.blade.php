<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product Category</title>

    @include('admin.includes.header_links')

    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="page-head">
            <div>
                <h1 class="page-title">Add Product Category</h1>
                <div class="section-title">Category Information</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('admin.product_categories.manage') }}" class="btn-action btn-manage">Manage
                    Categories</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('admin.product_category.store') }}" class="property-form" method="post"
            enctype="multipart/form-data" autocomplete="off">
            @csrf

            <div class="row g-4">

                <div class="col-md-4">
                    <label class="form-label">Category Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="category_name" value="{{ old('category_name') }}"
                        placeholder="Enter category name" required pattern="[A-Za-z\s]+"
                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                        title="Only letters and spaces are allowed">
                    @error('category_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Display Order</label>
                    <input type="number" class="form-control" name="display_order" value="{{ old('display_order', 0) }}"
                        min="0" oninput="this.value = Math.abs(this.value)" placeholder="Enter display order">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category Image</label>
                    <input type="file" class="form-control" name="category_image" accept="image/*">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="6"
                        placeholder="Enter category description">{{ old('description') }}</textarea>
                </div>

            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Category</button>
            </div>

        </form>

    </div>

    @include('admin.includes.footer_links')

    <script>
        CKEDITOR.replace('description', {
            height: 220
        });
    </script>

</body>

</html>