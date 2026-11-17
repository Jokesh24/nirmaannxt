<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage App Users</title>
    @include('admin.includes.header_links')

    <style>
        .table-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            background: #f8fafc;
        }

        .action-cell {
            white-space: nowrap;
        }

        .form-select-status {
            min-width: 120px;
            font-size: 13px;
            border-radius: 6px;
            padding: 4px 10px;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="page-head">
            <h1 class="page-title">Manage App Users</h1>
        </div>

        <div class="property-form">

            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    {{ Session::get('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('manage_app_users') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label>Search Users</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                            placeholder="Search by name, email, mobile, or code...">
                    </div>

                    <div class="col-md-3">
                        <label>Filter by Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Blocked</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search">
                                <i class="bi bi-search"></i>
                            </button>

                            <a href="{{ route('manage_app_users') }}" class="icon-btn btn-refresh"
                                title="Clear Filters">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
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
                            <th>Code</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Change Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $key => $user)
                            <tr>
                                <td>{{ $users->firstItem() + $key }}</td>
                                <td>
                                    @if($user->profile_photo)
                                        <img src="{{ asset($user->profile_photo) }}" class="table-img" alt="User">
                                    @else
                                        <img src="{{ asset('public/assets/images/default-avatar.png') }}" class="table-img"
                                            alt="Default">
                                    @endif
                                </td>
                                <td><strong>{{ $user->customer_code }}</strong></td>
                                <td>{{ $user->name ?? 'N/A' }}</td>
                                <td>{{ $user->mobile }}</td>
                                <td>{{ $user->email ?? 'N/A' }}</td>
                                <td>
                                    @if($user->status == 1)
                                        <span class="status-badge status-active">Active</span>
                                    @elseif($user->status == 0)
                                        <span class="status-badge status-inactive">Inactive</span>
                                    @elseif($user->status == 2)
                                        <span class="status-badge bg-danger text-white px-2 py-1 rounded"
                                            style="font-size: 12px;">Blocked</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('update_app_user_status', $user->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <select name="status"
                                            class="form-select form-select-sm form-select-status d-inline w-auto"
                                            onchange="this.form.submit()">
                                            <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>Set Active</option>
                                            <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>Set Inactive</option>
                                            <option value="2" {{ $user->status == 2 ? 'selected' : '' }}>Set Blocked</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="action-cell text-center">
                                    <a href="{{ route('view_app_user', $user->id) }}"
                                        class="action-btn btn-view text-decoration-none d-inline-flex align-items-center justify-content-center">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No app users found matching your
                                    criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                {{ $users->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>

        </div>
    </div>

    @include('admin.includes.footer_links')

</body>

</html>