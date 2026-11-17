<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Banners</title>
    @include('admin.includes.header_links')
    <style>
        .add-type-btn {
            cursor: pointer;
            color: #0d6efd;
            text-decoration: underline;
            font-size: 13px;
        }

        .table-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        .modal-table-container {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Banners Management</h1>
                <div class="section-title">Create & Manage Banners</div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <div class="property-form mb-5">
            <form action="{{ route('admin.banners.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">Banner Name <span class="req">*</span></label>
                        <input type="text" class="form-control" name="banner_name" required pattern="[a-zA-Z\s]+"
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            title="Only letters and spaces are allowed">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Banner Image <span class="req">*</span></label>
                        <input type="file" class="form-control" name="image" accept="image/*" required>
                        @error('banner_name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- <div class="col-md-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label">Banner Type <span class="req">*</span></label>
                            <span class="add-type-btn" data-bs-toggle="modal" data-bs-target="#typeModal">+ Add
                                Type</span>
                        </div>
                        <select class="form-select banner_type_select" name="banner_type" required>
                            <option value="">Select Type</option>
                            @foreach($bannerTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                    </div> -->

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label">
                                Banner Type <span class="req">*</span>
                                <!-- <i class="bi bi-info-circle-fill text-info ms-1" style="cursor: pointer;"
                                    data-bs-toggle="modal" data-bs-target="#bannerTypeInfoModal"
                                    title="View Examples"></i> -->
                            </label>
                            <span class="add-type-btn" data-bs-toggle="modal" data-bs-target="#typeModal">+ Add
                                Type</span>
                        </div>
                        <select class="form-select banner_type_select" name="banner_type" required>
                            <option value="">Select Type</option>
                            @foreach($bannerTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions mt-3">
                    <button type="submit" class="btn-save">Save Banner</button>
                </div>
            </form>
        </div>

        <div class="property-form">
            <form action="{{ route('admin.banners.add') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Banner</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search by name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter Type</label>
                        <select name="banner_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($bannerTypes as $type)
                                <option value="{{ $type->id }}" {{ request('banner_type') == $type->id ? 'selected' : '' }}>
                                    {{ $type->type_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search"><i class="bi bi-search"></i></button>
                            <a href="{{ route('admin.banners.add') }}" class="icon-btn btn-refresh"><i
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
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banners as $banner)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><img src="{{ asset('uploads/banners/' . $banner->image) }}" class="table-img">
                                </td>
                                <td>{{ $banner->banner_name }}</td>
                                <td>{{ $banner->bannerType->type_name ?? 'N/A' }}</td>
                                <td>
                                    <span
                                        class="status-badge {{ $banner->status == 1 ? 'status-active' : 'status-blocked' }}">
                                        {{ $banner->status == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <button type="button" class="action-btn btn-edit edit-banner-btn"
                                        data-id="{{ $banner->id }}">Edit</button>
                                    <a href="{{ route('admin.banners.delete', $banner->id) }}" class="action-btn btn-delete"
                                        onclick="return confirm('Delete this banner?')">Delete</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No banners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-container">
                {{ $banners->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBannerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.banners.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="banner_id" id="edit_banner_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Banner</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Name</label><input type="text"
                                    name="banner_name" id="edit_banner_name" class="form-control" required
                                    pattern="[a-zA-Z\s]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    title="Only letters and spaces are allowed"></div>
                            <div class="col-md-6"><label class="form-label">Type</label>
                                <select name="banner_type" id="edit_banner_type" class="form-select banner_type_select"
                                    required></select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Image (Leave blank to keep
                                    current)</label><input type="file" name="image" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Update Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Banner Types</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3"><input type="text" id="new_type_name" class="form-control"
                            placeholder="Type Name"><button class="btn btn-primary" type="button"
                            id="saveTypeBtn">Add</button></div>
                    <div class="modal-table-container">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="typeTableBody">
                                @foreach($bannerTypes as $type) <tr>
                                    <td>{{ $type->type_name }}</td>
                                    <td>Active</td>
                                </tr> @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bannerTypeInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Banner Type Examples</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Use the visual examples below to understand where each banner type is
                        displayed in the application.</p>
                    <div class="row g-4 text-center">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Home</h6>
                            <img src="{{ asset('img/banner_type_example/Home.png') }}" alt="Home Banner Example"
                                class="img-fluid border rounded shadow-sm"
                                style="max-height: 400px; object-fit: contain;">
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">RBMKart</h6>
                            <img src="{{ asset('img/banner_type_example/RBMKart.png') }}"
                                alt="RBMKart Banner Example" class="img-fluid border rounded shadow-sm"
                                style="max-height: 400px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        $(document).ready(function () {
            // Edit Banner
            $('.edit-banner-btn').click(function () {
                let id = $(this).data('id');
                $.get("{{ url('admin/banners/edit') }}/" + id, function (data) {
                    $('#edit_banner_id').val(data.id);
                    $('#edit_banner_name').val(data.banner_name);
                    $('#edit_status').val(data.status == 1 ? 'Active' : 'Inactive');

                    // Sync type dropdown
                    let typeHtml = '';
                    @foreach($bannerTypes as $type)
                        typeHtml += `<option value="{{ $type->id }}" ${data.banner_type_id == {{ $type->id }} ? 'selected' : ''}>{{ $type->type_name }}</option>`;
                    @endforeach
                    $('.banner_type_select').html(typeHtml);

                    $('#editBannerModal').modal('show');
                });
            });

            // Save Type AJAX
            $('#saveTypeBtn').click(function () {
                let typeName = $('#new_type_name').val();
                if (!typeName) return alert('Enter type name');
                $.post("{{ route('admin.banner-types.store') }}", { _token: "{{ csrf_token() }}", type_name: typeName }, function (response) {
                    if (response.success) {
                        $('#new_type_name').val('');
                        let options = '<option value="">Select Type</option>';
                        let rows = '';
                        response.all.forEach(item => {
                            options += `<option value="${item.id}">${item.type_name}</option>`;
                            rows += `<tr><td>${item.type_name}</td><td>Active</td></tr>`;
                        });
                        $('.banner_type_select').html(options);
                        $('#typeTableBody').html(rows);
                    }
                });
            });
        });
    </script>
</body>

</html>