<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Brands | RBM Admin</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Manage Brands</h1>
            <div class="action-group">
                <a href="{{ route('add_brand') }}" class="btn-action btn-manage">+ Add Brand</a>
            </div>
        </div>

        <div class="property-form">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    {{ Session::get('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('manage_brand') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Search Brand</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Brand name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-save w-100">Apply</button>
                        <a href="{{ route('manage_brand') }}"
                            class="btn btn-secondary w-100 d-flex align-items-center justify-content-center"
                            style="border-radius: 12px; height: 48px;">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Logo</th>
                            <th>Brand Name</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($brands as $key => $brand)
                            <tr>
                                <td>{{ $brands->firstItem() + $key }}</td>
                                <td>
                                    @if($brand->logo)
                                        <img src="{{ asset('uploads/brands/' . $brand->logo) }}"
                                            alt="{{ $brand->brand_name }}"
                                            style="width: 40px; height: 40px; object-fit: contain; border-radius: 4px; border: 1px solid #eaeaea;">
                                    @else
                                        <span class="text-muted" style="font-size: 12px;">No Logo</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $brand->brand_name }}</strong>
                                    @if($brand->brand_description)
                                        <div class="text-muted"
                                            style="font-size: 12px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $brand->brand_description }}
                                        </div>
                                    @endif
                                </td>
                                <td><span
                                        class="status-badge {{ $brand->status == 1 ? 'status-active' : 'status-inactive' }}">{{ $brand->status == 1 ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td>{{ $brand->created_at->format('d M Y') }}</td>
                                <td class="action-cell">
                                    <button type="button" class="action-btn btn-edit editBrandBtn"
                                        data-id="{{ $brand->id }}" data-name="{{ $brand->brand_name }}"
                                        data-desc="{{ $brand->brand_description }}"
                                        data-status="{{ $brand->status == 1 ? 'Active' : 'Inactive' }}">Edit</button>
                                    <a href="{{ route('delete_brand', $brand->id) }}" class="action-btn btn-delete"
                                        onclick="return confirm('Delete this brand?')">Delete</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $brands->appends(request()->input())->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <div class="modal fade" id="editBrandModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('update_brand') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Brand Name <span class="req">*</span></label>
                            <input type="text" name="brand_name" id="edit_brand_name"
                                class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Update Logo <small class="text-muted">(Leave blank to keep
                                    current)</small></label>
                            <input type="file" name="logo" class="form-control bg-light border-0 py-2" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand Description</label>
                            <textarea name="brand_description" id="edit_brand_desc"
                                class="form-control bg-light border-0 py-2" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select name="status" id="edit_brand_status" class="form-select bg-light border-0 py-2"
                                required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" class="btn-save px-4" style="width:auto">Update Brand</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        $(document).ready(function () {
            $(document).on('click', '.editBrandBtn', function () {
                $('#edit_brand_id').val($(this).data('id'));
                $('#edit_brand_name').val($(this).data('name'));
                $('#edit_brand_desc').val($(this).data('desc'));
                $('#edit_brand_status').val($(this).data('status'));
                $('#editBrandModal').modal('show');
            });
        });
    </script>
</body>

</html>