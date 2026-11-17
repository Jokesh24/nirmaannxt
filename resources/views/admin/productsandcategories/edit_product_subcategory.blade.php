<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product Subcategory</title>
    @include('admin.includes.header_links')
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Edit Product Subcategory</h1>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
            </div>
        </div>

        <form action="{{ route('admin.product_subcategory.update', $subcategory->id) }}" class="property-form"
            method="post" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Category <span class="req">*</span></label>
                    <select class="form-select" name="category_id" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $subcategory->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Subcategory Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="subcategory_name" value="{{ $subcategory->name }}"
                        required pattern="[A-Za-z\s]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                        title="Only letters and spaces are allowed">
                    @error('subcategory_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Display Order</label>
                    <input type="number" class="form-control" name="display_order"
                        value="{{ $subcategory->display_order }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active" {{ $subcategory->status == 1 ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ $subcategory->status == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Image</label>
                    <input type="file" class="form-control" name="subcategory_image" accept="image/*">
                    @if($subcategory->image)
                        <div class="mt-2">
                            <img src="{{ asset('uploads/subcategory/' . $subcategory->image) }}" width="80"
                                style="border-radius: 10px;">
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Update Subcategory</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')
</body>

</html>