<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Cancel Reasons | Nirmaan NXT Admin</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Manage Cancel Reasons</h1>
            <div class="action-group">
                <a href="{{ route('add_cancel_reason') }}" class="btn-action btn-manage">+ Add Cancel Reason</a>
            </div>
        </div>

        <div class="property-form">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(Session::has('error') || $errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ Session::get('error') ?? 'Please correct the highlighted errors.' }}
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

            <form action="{{ route('manage_cancel_reason') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Search Cancel Reason</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search by cancel reason name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn-save w-100">Filter</button>
                        <a href="{{ route('manage_cancel_reason') }}"
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
                            <th style="width: 80px;">#</th>
                            <th>Cancel Reason Name</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 180px;">Added On</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cancelReasons as $key => $cancelReason)
                            <tr>
                                <td>{{ $cancelReasons->firstItem() + $key }}</td>
                                <td>
                                    <strong>{{ $cancelReason->name }}</strong>
                                </td>
                                <td>
                                    <span
                                        class="status-badge {{ $cancelReason->status == 1 ? 'status-active' : 'status-inactive' }}">
                                        {{ $cancelReason->status == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $cancelReason->created_at ? $cancelReason->created_at->format('d M Y') : 'N/A' }}
                                </td>
                                <td class="action-cell">
                                    <div class="d-flex gap-1">
                                        <button type="button" class="action-btn btn-edit editCancelReasonBtn"
                                            data-id="{{ $cancelReason->id }}" data-name="{{ $cancelReason->name }}"
                                            data-status="{{ $cancelReason->status == 1 ? 'Active' : 'Inactive' }}">Edit</button>

                                        <a href="{{ route('delete_cancel_reason', $cancelReason->id) }}"
                                            class="action-btn btn-delete text-decoration-none d-inline-flex align-items-center justify-content-center"
                                            onclick="return confirm('Are you sure you want to delete this cancel reason?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No cancel reasons found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $cancelReasons->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <!-- Edit Cancel Reason Modal -->
    <div class="modal fade" id="editCancelReasonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Cancel Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('update_cancel_reason') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cancel_reason_id" id="edit_cancel_reason_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Cancel Reason Name <span class="req">*</span></label>
                            <input type="text" name="name" id="edit_cancel_reason_name" class="form-control"
                                placeholder="Enter cancel reason name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select name="status" id="edit_cancel_reason_status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-save px-4" style="width:auto">Update Cancel Reason</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $(document).on('click', '.editCancelReasonBtn', function () {
                $('#edit_cancel_reason_id').val($(this).data('id'));
                $('#edit_cancel_reason_name').val($(this).data('name'));
                $('#edit_cancel_reason_status').val($(this).data('status'));
                $('#editCancelReasonModal').modal('show');
            });
        });
    </script>
</body>

</html>