<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Product Categories</title>
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
            <h1 class="page-title">Manage Product Categories</h1>
            <div class="action-group">
                <a href="{{ route('admin.product_categories.add') }}" class="btn-action btn-manage">+ Add Category</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="property-form">
            <form action="{{ route('admin.product_categories.manage') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Search Category</label>
                        <input type="text" name="search" class="form-control" placeholder="Category Name"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search"><i
                                    class="bi bi-search"></i></button>
                            <a href="{{ route('admin.product_categories.manage') }}" class="icon-btn btn-refresh"
                                title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category logo</th>
                            <th>Category Name</th>
                            <th>Display Order</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $key => $cat)
                            <tr>
                                <td>{{ $categories->firstItem() + $key }}</td>
                                <td><img src="{{ asset('uploads/category/' . $cat->image) }}" class="table-img"></td>
                                <td>{{ $cat->name }}</td>
                                <td>{{ $cat->display_order }}</td>
                                <td>
                                    <span
                                        class="status-badge {{ $cat->status == 1 ? 'status-active' : 'status-inactive' }}">
                                        {{ $cat->status == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $cat->created_at->format('d M Y') }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.product_category.edit', $cat->id) }}"
                                        class="action-btn btn-edit">Edit</a>
                                    <a href="{{ route('admin.product_category.delete', $cat->id) }}"
                                        class="action-btn btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this?')">Delete</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">
                    Showing {{ $categories->firstItem() }}–{{ $categories->lastItem() }} of {{ $categories->total() }}
                    categories
                </div>
                {{ $categories->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')
</body>

</html>