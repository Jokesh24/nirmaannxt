<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Product Category</title>
    @include('admin.includes.header_links')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Edit Product Category</h1>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
            </div>
        </div>

        <form action="{{ route('admin.product_category.update', $category->id) }}" method="post"
            enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Category Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="category_name" value="{{ $category->name }}" required
                        pattern="[A-Za-z\s]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                        title="Only letters and spaces are allowed">
                    @error('category_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Display Order</label>
                    <input type="number" class="form-control" name="display_order"
                        value="{{ $category->display_order }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active" {{ $category->status == 1 ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ $category->status == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category Image</label>
                    <input type="file" class="form-control" name="category_image" accept="image/*">
                    @if($category->image)
                        <img src="{{ asset('uploads/category/' . $category->image) }}" width="50" class="mt-2">
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="description"
                        name="description">{{ $category->description }}</textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save">Update Category</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')
    <script>CKEDITOR.replace('description');</script>
</body>

</html>