<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Vendors</title>
    @include('admin.includes.header_links')

    <style>
        .table-img {
            width: 58px;
            height: 58px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #fff;
        }
    </style>

</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="page-head">
            <h1 class="page-title">Manage Vendors</h1>

            <div class="action-group">
                <!-- <a href="{{ route('vendor_feedbacks') }}" class="btn-action btn-manage">Vendor Feedbacks</a> -->
                <a href="{{ route('add_vendor') }}" class="btn-action btn-manage">+ Add Vendor</a>
            </div>
        </div>

        <div class="property-form">

            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('manage_vendor') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label>Search Vendor</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search name, email...">
                    </div>

                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                            <option value="Blocked" {{ request('status') == 'Blocked' ? 'selected' : '' }}>Blocked
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search">
                                <i class="bi bi-search"></i>
                            </button>

                            <a href="{{ route('manage_vendor') }}" class="icon-btn btn-refresh" title="Refresh">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Logo</th>
                            <th>Business</th>
                            <th>Contact</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <!-- <th>Category</th>
                            <th>State</th>
                            <th>City</th> -->
                            <th>Status</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($vendors as $key => $vendor)
                            <tr>
                                <td>{{ $vendors->firstItem() + $key }}</td>
                                <!-- <td>
                                                                                                            @if($vendor->company_logo)
                                                                                                                <img src="{{ url('public/uploads/company_logos/' . $vendor->company_logo) }}"
                                                                                                                    class="vendor-logo"
                                                                                                                    style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                                                                                                            @else
                                                                                                                "N/A"
                                                                                                            @endif
                                                                                                        </td> -->
                                <td><img src="{{ asset('uploads/company_logos/' . $vendor->company_logo) }}"
                                        class="table-img"></td>
                                <td>{{ $vendor->business_name }}</td>
                                <td>{{ $vendor->contact_person }}</td>
                                <td>{{ $vendor->business_mobile }}</td>
                                <td>{{ $vendor->business_email }}</td>
                                <!-- <td>{{ $vendor->category->name ?? 'N/A' }}</td>
                                <td>{{ $vendor->state->name ?? 'N/A' }}</td>
                                <td>{{ $vendor->city->name ?? 'N/A' }}</td> -->
                                <td>
                                    @if($vendor->status == 1)
                                        <span class="status-badge status-active">Active</span>

                                    @elseif($vendor->status == 0)
                                        <span class="status-badge status-inactive">Inactive</span>

                                    @elseif($vendor->status == 2)
                                        <span class="status-badge bg-danger text-white px-2 py-1 rounded"
                                            style="font-size: 12px;">
                                            Blocked
                                        </span>

                                    @elseif($vendor->status == 3)
                                        <span class="status-badge bg-warning text-dark px-2 py-1 rounded"
                                            style="font-size: 12px;">
                                            Under Review
                                        </span>

                                    @else
                                        <span class="status-badge bg-secondary text-white px-2 py-1 rounded"
                                            style="font-size: 12px;">
                                            Unknown
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="openPositionModal({{ $vendor->id }}, {{ $vendor->position }})">
                                        {{ $vendor->position }}
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <div class="d-flex gap-1">

                                        <!-- New Enquiries Button -->
                                        <!-- <a href="{{ route('vendor_enquiries', $vendor->id) }}"
                                            class="action-btn btn-manage text-decoration-none d-inline-flex align-items-center justify-content-center"
                                            style="background-color: #6f42c1; color: white;">
                                            Enquiries
                                        </a> -->
                                        <a href="{{ route('view_vendor', $vendor->id) }}"
                                            class="action-btn btn-view text-decoration-none d-inline-flex align-items-center justify-content-center">
                                            View
                                        </a>

                                        <a href="{{ route('edit_vendor', $vendor->id) }}"
                                            class="action-btn btn-edit text-decoration-none d-inline-flex align-items-center justify-content-center">
                                            Edit
                                        </a>

                                        <a href="{{ route('delete_vendor', $vendor->id) }}"
                                            class="action-btn btn-delete text-decoration-none d-inline-flex align-items-center justify-content-center"
                                            onclick="return confirm('Are you sure you want to move this vendor to trash?')">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No vendors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vendors->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>

        </div>

    </div>

    @include('admin.includes.footer_links')

    <div class="modal fade" id="positionModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <form id="positionForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Vendor Position</h5>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="vendor_id" id="modal_vendor_id">
                    <input type="number" name="position" id="modal_position" class="form-control"
                        placeholder="Enter priority (higher = first)" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Position</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>

<script>
    function openPositionModal(id, pos) {
        $('#modal_vendor_id').val(id);
        $('#modal_position').val(pos);
        $('#positionModal').modal('show');
    }

    $('#positionForm').on('submit', function (e) {
        e.preventDefault();
        $.post("{{ route('update_vendor_position') }}", $(this).serialize(), function (res) {
            $('#positionModal').modal('hide');
            location.reload(); // Professional way to reflect the new sort order
        });
    });
</script>