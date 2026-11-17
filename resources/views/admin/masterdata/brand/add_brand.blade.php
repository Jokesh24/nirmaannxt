<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Brand | RBM Admin</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Add Brand</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_brand') }}" class="btn-action btn-manage">Manage Brands</a>
            </div>
        </div>

        <form class="property-form" action="{{ route('store_brand') }}" method="post" enctype="multipart/form-data"
            autocomplete="off">
            @csrf
            <div class="section-title">Brand Details</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Brand Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="brand_name" placeholder="Enter brand name" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="">Select Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand Logo</label>
                    <input type="file" class="form-control" name="logo" accept="image/*">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Brand Description</label>
                    <textarea class="form-control" name="brand_description" rows="1"
                        placeholder="Enter description (optional)"></textarea>
                </div>
            </div>
            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Brand</button>
            </div>
        </form>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>