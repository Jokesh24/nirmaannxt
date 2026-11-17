<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Unit | RBM Admin</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Add Unit</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_unit') }}" class="btn-action btn-manage">Manage Sizes</a>
            </div>
        </div>

        <form class="property-form" action="{{ route('store_unit') }}" method="post" autocomplete="off">
            @csrf
            <div class="section-title">Unit Details</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Unit Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="name" placeholder="Enter unit name (e.g. Ton, KG, Ltr etc)"
                        required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Size</button>
            </div>
        </form>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>