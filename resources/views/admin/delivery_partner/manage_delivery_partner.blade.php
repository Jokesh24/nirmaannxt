<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Delivery Partners</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Manage Delivery Partners</h1>
            <div class="action-group">
                <a href="{{ route('add_delivery_partner') }}" class="btn-action btn-manage">+ Add Partner</a>
            </div>
        </div>

        <div class="property-form">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('manage_delivery_partner') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label>Search Partner</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search name, phone, vehicle...">
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                            <option value="Blocked" {{ request('status') == 'Blocked' ? 'selected' : '' }}>Blocked
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search"><i class="bi bi-search"></i></button>
                            <a href="{{ route('manage_delivery_partner') }}" class="icon-btn btn-refresh"><i
                                    class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>Driver ID</th>
                            <th>Driver Name</th>
                            <th>Vehicle Detail</th>
                            <th>Status</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partners as $key => $partner)
                            <tr>
                                <td>{{ $partners->firstItem() + $key }}</td>
                                <td>
                                    <img src="{{ $partner->profile_photo ? asset('uploads/delivery_partners/' . $partner->profile_photo) : 'https://via.placeholder.com/40' }}"
                                        style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                </td>
                                <td><span class="badge bg-secondary">{{ $partner->driver_id }}</span></td>
                                <td>
                                    <strong>{{ $partner->driver_name }}</strong><br>
                                    <small class="text-muted"><i class="bi bi-telephone"></i>
                                        {{ $partner->mobile_number }}</small>
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $partner->vehicle_reg_number }}</div>
                                    <div class="small text-muted">{{ $partner->vehicle_type }}
                                        ({{ $partner->vehicle_capacity }})</div>
                                </td>
                                <td>
                                    @if($partner->status == 1) <span class="status-badge status-active">Active</span>
                                    @elseif($partner->status == 0) <span
                                        class="status-badge status-inactive">Inactive</span>
                                    @else <span class="status-badge bg-danger text-white px-2 py-1 rounded">Blocked</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info"
                                        onclick="openPositionModal({{ $partner->id }}, {{ $partner->position }})">{{ $partner->position }}</button>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('view_delivery_partner', $partner->id) }}"
                                            class="action-btn btn-view">View</a>
                                        <a href="{{ route('edit_delivery_partner', $partner->id) }}"
                                            class="action-btn btn-edit">Edit</a>
                                        <a href="{{ route('delete_delivery_partner', $partner->id) }}"
                                            class="action-btn btn-delete"
                                            onclick="return confirm('Move to trash?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No partners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $partners->appends(request()->input())->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>

    <!-- Position Modal -->
    <div class="modal fade" id="positionModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <form id="positionForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Position</h5>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="partner_id" id="modal_partner_id">
                    <input type="number" name="position" id="modal_position" class="form-control" required>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openPositionModal(id, pos) {
            $('#modal_partner_id').val(id);
            $('#modal_position').val(pos);
            $('#positionModal').modal('show');
        }

        $('#positionForm').on('submit', function (e) {
            e.preventDefault();
            $.post("{{ route('update_delivery_partner_position') }}", $(this).serialize(), function () {
                location.reload();
            });
        });
    </script>
</body>

</html>