<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Engineers</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Manage Engineers</h1>
            <div class="action-group">
                <a href="{{ route('add_engineer') }}" class="btn-action btn-manage">+ Add Engineer</a>
            </div>
        </div>

        <div class="property-form">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('manage_engineer') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label>Search Engineer</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search name, ID, email...">
                    </div>
                    <div class="col-md-3">
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
                            <button type="submit" class="icon-btn btn-search" title="Search"><i
                                    class="bi bi-search"></i></button>
                            <a href="{{ route('manage_engineer') }}" class="icon-btn btn-refresh" title="Refresh"><i
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
                            <th>Engineer ID</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Territory</th>
                            <th>Status</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($engineers as $key => $engineer)
                            <tr>
                                <td>{{ $engineers->firstItem() + $key }}</td>
                                <td>
                                    <img src="{{ $engineer->profile_photo ? asset('uploads/engineer_profiles/' . $engineer->profile_photo) : 'https://via.placeholder.com/40' }}"
                                        style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid #eaeaea;"
                                        alt="Profile">
                                </td>
                                <td><span class="badge bg-secondary">{{ $engineer->engineer_id }}</span></td>
                                <td><strong>{{ $engineer->full_name }}</strong></td>
                                <td>
                                    <div class="small"><i class="bi bi-telephone text-muted"></i>
                                        {{ $engineer->mobile_number }}
                                    </div>
                                    <div class="small"><i class="bi bi-envelope text-muted"></i> {{ $engineer->email }}
                                    </div>
                                </td>
                                <td>{{ $engineer->assigned_territory }}</td>
                                <td>
                                    @if($engineer->status == 1) <span class="status-badge status-active">Active</span>
                                    @elseif($engineer->status == 0) <span
                                        class="status-badge status-inactive">Inactive</span>
                                    @else <span class="status-badge bg-danger text-white px-2 py-1 rounded"
                                        style="font-size: 12px;">Blocked</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="openPositionModal({{ $engineer->id }}, {{ $engineer->position }})">
                                        {{ $engineer->position }}
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('view_engineer', $engineer->id) }}"
                                            class="action-btn btn-view d-inline-flex align-items-center justify-content-center">View</a>
                                        <a href="{{ route('edit_engineer', $engineer->id) }}"
                                            class="action-btn btn-edit d-inline-flex align-items-center justify-content-center">Edit</a>
                                        <a href="{{ route('delete_engineer', $engineer->id) }}"
                                            class="action-btn btn-delete d-inline-flex align-items-center justify-content-center"
                                            onclick="return confirm('Move this engineer to trash?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No engineers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $engineers->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
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
                    <input type="hidden" name="engineer_id" id="modal_engineer_id">
                    <input type="number" name="position" id="modal_position" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openPositionModal(id, pos) {
            $('#modal_engineer_id').val(id);
            $('#modal_position').val(pos);
            $('#positionModal').modal('show');
        }

        $('#positionForm').on('submit', function (e) {
            e.preventDefault();
            $.post("{{ route('update_engineer_position') }}", $(this).serialize(), function (res) {
                $('#positionModal').modal('hide');
                location.reload();
            });
        });
    </script>
</body>

</html>