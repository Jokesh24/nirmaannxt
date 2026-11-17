<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage {{ $title }}</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Manage {{ $title }}</h1>
            <div class="action-group">
                <a href="{{ route('add_content', $type) }}" class="btn-action btn-manage">+ Add New</a>
            </div>
        </div>

        <div class="property-form">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('manage_content', $type) }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('manage_content', $type) }}" class="icon-btn btn-refresh" title="Refresh">
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
                            <th width="5%">#</th>
                            <th width="55%">Description Snippet</th>
                            <th width="10%">Status</th>
                            <th width="15%">Last Updated</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $key => $record)
                            <tr>
                                <td>{{ $records->firstItem() + $key }}</td>
                                <td>{!! \Illuminate\Support\Str::limit(strip_tags($record->description), 80) !!}</td>
                                <td>
                                    @if($record->status == 1)
                                        <span class="status-badge status-active">Active</span>
                                    @else
                                        <span class="status-badge status-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $record->updated_at->format('d M Y, h:i A') }}</td>
                                <td class="action-cell">
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('edit_content', ['type' => $type, 'id' => $record->id]) }}"
                                            class="action-btn btn-edit text-decoration-none d-inline-flex align-items-center justify-content-center">Edit</a>
                                        <a href="{{ route('delete_content', ['type' => $type, 'id' => $record->id]) }}"
                                            class="action-btn btn-delete text-decoration-none d-inline-flex align-items-center justify-content-center"
                                            onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $records->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>