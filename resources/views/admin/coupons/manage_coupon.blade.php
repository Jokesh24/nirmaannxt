<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Coupons</title>
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
            <h1 class="page-title">Manage Coupons</h1>
            <div class="action-group">
                <a href="{{ route('add_coupon') }}" class="btn-action btn-manage">+ Add Coupon</a>
            </div>
        </div>

        <div class="property-form">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('manage_coupon') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label>Search Coupon</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search title, code...">
                    </div>
                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                            <option value="Expired" {{ request('status') == 'Expired' ? 'selected' : '' }}>Expired
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search"><i
                                    class="bi bi-search"></i></button>
                            <a href="{{ route('manage_coupon') }}" class="icon-btn btn-refresh" title="Refresh"><i
                                    class="bi bi-arrow-clockwise"></i></a>
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
                            <th>Code</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Discount</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $key => $coupon)
                            <tr>
                                <td>{{ $coupons->firstItem() + $key }}</td>
                                <td>
                                    @if($coupon->logo)
                                        <img src="{{ asset('uploads/coupon_logos/' . $coupon->logo) }}" class="table-img">
                                    @else
                                        <img src="https://via.placeholder.com/58" class="table-img">
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $coupon->code }}</span></td>
                                <td>{{ $coupon->title }}</td>
                                <td>{{ $coupon->coupon_type }}</td>
                                <td>{{ $coupon->discount_type == 'Percentage' ? $coupon->discount_value . '%' : '₹' . $coupon->discount_value }}
                                </td>
                                <td>{{ date('d M, Y', strtotime($coupon->end_date)) }}</td>
                                <td>
                                    @if($coupon->status == 1)
                                        <span class="status-badge status-active">Active</span>
                                    @elseif($coupon->status == 0)
                                        <span class="status-badge status-inactive">Inactive</span>
                                    @elseif($coupon->status == 2)
                                        <span class="status-badge bg-danger text-white px-2 py-1 rounded"
                                            style="font-size: 12px;">Expired</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="openPositionModal({{ $coupon->id }}, {{ $coupon->position }})">
                                        {{ $coupon->position }}
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('view_coupon', $coupon->id) }}"
                                            class="action-btn btn-view text-decoration-none d-inline-flex align-items-center justify-content-center">View</a>
                                        <a href="{{ route('edit_coupon', $coupon->id) }}"
                                            class="action-btn btn-edit text-decoration-none d-inline-flex align-items-center justify-content-center">Edit</a>
                                        <a href="{{ route('delete_coupon', $coupon->id) }}"
                                            class="action-btn btn-delete text-decoration-none d-inline-flex align-items-center justify-content-center"
                                            onclick="return confirm('Are you sure you want to move this coupon to trash?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No coupons found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $coupons->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <div class="modal fade" id="positionModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <form id="positionForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Priority Position</h5>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="coupon_id" id="modal_coupon_id">
                    <input type="number" name="position" id="modal_position" class="form-control"
                        placeholder="Enter priority (higher = first)" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPositionModal(id, pos) {
            $('#modal_coupon_id').val(id);
            $('#modal_position').val(pos);
            $('#positionModal').modal('show');
        }
        $('#positionForm').on('submit', function (e) {
            e.preventDefault();
            $.post("{{ route('update_coupon_position') }}", $(this).serialize(), function (res) {
                $('#positionModal').modal('hide');
                location.reload();
            });
        });
    </script>
</body>

</html>