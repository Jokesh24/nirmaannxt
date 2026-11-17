<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Units | RBM Admin</title>
    @include('admin.includes.header_links')
</head>
<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Manage Units</h1>
            <div class="action-group">
                <a href="{{ route('add_unit') }}" class="btn-action btn-manage">+ Add Unit</a>
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

            <form action="{{ route('manage_unit') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Search Unit</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Unit name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-save w-100">Apply</button>
                        <a href="{{ route('manage_unit') }}" class="btn btn-secondary w-100 d-flex align-items-center justify-content-center" style="border-radius: 12px; height: 48px;">
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
                            <th>Unit Name</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($units as $key => $unit)
                            <tr>
                                <td>{{ $units->firstItem() + $key }}</td>
                                <td><strong>{{ $unit->name }}</strong></td>
                                <td><span class="status-badge {{ $unit->status == 1 ? 'status-active' : 'status-inactive' }}">{{ $unit->status == 1 ? 'Active' : 'Inactive' }}</span></td>
                                <td>{{ $unit->created_at->format('d M Y') }}</td>
                                <td class="action-cell">
                                    <button type="button" class="action-btn btn-edit editUnitBtn" 
                                        data-id="{{ $unit->id }}" data-name="{{ $unit->name }}" 
                                        data-status="{{ $unit->status == 1 ? 'Active' : 'Inactive' }}">Edit</button>
                                    <a href="{{ route('delete_unit', $unit->id) }}" class="action-btn btn-delete" onclick="return confirm('Delete this unit?')">Delete</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $units->appends(request()->input())->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Units</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('update_unit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="unit_id" id="edit_unit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Unit Name <span class="req">*</span></label>
                            <input type="text" name="name" id="edit_unit_name" class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select name="status" id="edit_unit_status" class="form-select bg-light border-0 py-2" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" class="btn-save px-4" style="width:auto">Update ?add_unit?</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')
 
    <script>
        $(document).ready(function () {
            $(document).on('click', '.editUnitBtn', function () {
                $('#edit_unit_id').val($(this).data('id'));
                $('#edit_unit_name').val($(this).data('name'));
                $('#edit_unit_status').val($(this).data('status'));
                $('#editUnitModal').modal('show');
            });
        });
    </script>
</body>
</html>