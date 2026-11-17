<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Product Subcategories</title>
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
            <h1 class="page-title">Manage Product Subcategories</h1>
            <div class="action-group">
                <a href="{{ route('admin.product_subcategories.add') }}" class="btn-action btn-manage">+ Add
                    Subcategories</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="property-form">
            <form action="{{ route('admin.product_subcategories.manage') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Subcategory</label>
                        <input type="text" name="search" class="form-control" placeholder="Subcategory Name"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
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
                            <button type="submit" class="icon-btn btn-search" title="Search"><i
                                    class="bi bi-search"></i></button>
                            <a href="{{ route('admin.product_subcategories.manage') }}" class="icon-btn btn-refresh"
                                title="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox"></th>
                            <th>#</th>
                            <th>Subcategory Image</th>
                            <th>Subcategory Name</th>
                            <th>Category</th>
                            <th>Display Order</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subcategories as $key => $sub)
                            <tr>
                                <td><input type="checkbox"></td>
                                <td>{{ $subcategories->firstItem() + $key }}</td>
                                <td><img src="{{ asset('uploads/subcategory/' . $sub->image) }}" class="table-img">
                                </td>
                                <td>{{ $sub->name }}</td>
                                <td>{{ $sub->category->name ?? 'N/A' }}</td>
                                <td>{{ $sub->display_order }}</td>
                                <td>
                                    <span
                                        class="status-badge {{ $sub->status == 1 ? 'status-active' : 'status-inactive' }}">
                                        {{ $sub->status == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $sub->created_at->format('d M Y') }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.product_subcategory.edit', $sub->id) }}"
                                        class="action-btn btn-edit">Edit</a>
                                    <a href="{{ route('admin.product_subcategory.delete', $sub->id) }}"
                                        class="action-btn btn-delete"
                                        onclick="return confirm('Are you sure want to delete this sub-category?')">Delete</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- <div class="pagination-container">
                <div class="pagination-info">
                    Showing {{ $subcategories->firstItem() }}–{{ $subcategories->lastItem() }} of
                    {{ $subcategories->total() }} subcategories
                </div>
                {{ $subcategories->appends(request()->query())->links() }}
            </div> -->


            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                <div class="pagination-info text-muted small">
                    Showing {{ $subcategories->firstItem() ?? 0 }}–{{ $subcategories->lastItem() ?? 0 }} of
                    {{ $subcategories->total() }} subcategories
                </div>
                <nav>
                    {{ $subcategories->appends(request()->query())->links('pagination::bootstrap-4') }}
                </nav>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')
</body>

</html>