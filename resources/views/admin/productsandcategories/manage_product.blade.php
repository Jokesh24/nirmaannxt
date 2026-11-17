<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Products</title>
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

        .table-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .table-sub {
            font-size: 13px;
            color: #64748b;
        }

        .filter-card {
            background: #f8fafc;
            border: 1px solid #e9eef4;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .icon-btn {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
            font-size: 18px;
        }

        .btn-search {
            background: #0f172a;
            color: #fff;
        }

        .btn-search:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .btn-refresh {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-refresh:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
        }

        .btn-export-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .btn-export-icon:hover {
            background: #bbf7d0;
            transform: translateY(-1px);
        }

        .bulk-action-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding: 16px 18px;
            border: 1px solid #e9eef4;
            border-radius: 16px;
            background: #ffffff;
        }

        .bulk-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bulk-left .selected-text {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }

        .bulk-btn {
            border: none;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .bulk-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .bulk-delete:hover {
            background: #fecaca;
        }

        .bulk-active {
            background: #dcfce7;
            color: #15803d;
        }

        .bulk-active:hover {
            background: #bbf7d0;
        }

        .bulk-inactive {
            background: #fef3c7;
            color: #b45309;
        }

        .bulk-inactive:hover {
            background: #fde68a;
        }

        .price-main {
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
        }

        .price-offer {
            font-size: 13px;
            color: #16a34a;
            font-weight: 600;
        }

        .stock-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .stock-in {
            background: #dcfce7;
            color: #15803d;
        }

        .stock-low {
            background: #fef3c7;
            color: #b45309;
        }

        .stock-out {
            background: #fee2e2;
            color: #dc2626;
        }

        .tag-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-right: 6px;
            margin-bottom: 4px;
        }

        .tag-featured {
            background: #ede9fe;
            color: #7c3aed;
        }

        .tag-trending {
            background: #fce7f3;
            color: #be185d;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .pagination-info {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .bulk-action-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')
    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Manage Products</h1>
                <div class="section-title">View, filter and manage all products</div>
            </div>
            <div class="action-group">
                <a href="{{ route('admin.products.add') }}" class="btn-action btn-manage">+ Add Product</a>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="property-form">
            <form action="{{ route('admin.products.manage') }}" method="GET" class="filter-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search Product</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or SKU"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" id="filter_brand" class="form-select">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->brand_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="filter_category" class="form-select"
                            onchange="fetchFilterSubcategories(this.value)">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Subcategory</label>
                        <select name="subcategory_id" id="filter_subcategory" class="form-select">
                            <option value="">All Subcategories</option>
                            @foreach($subcategories_filtered as $sub)
                                <option value="{{ $sub->id }}" {{ request('subcategory_id') == $sub->id ? 'selected' : '' }}>
                                    {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search"><i class="bi bi-search"></i></button>
                            <a href="{{ route('admin.products.manage') }}" class="icon-btn btn-refresh"><i
                                    class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="bulk-action-bar">
                <div class="bulk-left">
                    <span class="selected-text"><span id="selectedCount">0</span> selected</span>
                    <button type="button" class="bulk-btn bulk-active">Mark Active</button>
                    <button type="button" class="bulk-btn bulk-inactive">Mark Inactive</button>
                    <button type="button" class="bulk-btn bulk-delete">Delete Selected</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:50px;"><input type="checkbox" id="selectAll"></th>
                            <th>#</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Tags</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th style="min-width:180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $key => $prod)
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="{{ $prod->id }}"></td>
                                <td>{{ $products->firstItem() + $key }}</td>
                                <td><img src="{{ asset('uploads/products/' . $prod->image) }}" class="table-img">
                                </td>
                                <td>
                                    <div class="table-title" title="{{ $prod->name }}">
                                        {{ \Illuminate\Support\Str::limit($prod->name, 10) }}
                                    </div>

                                    <div class="table-sub" title="SKU: {{ $prod->sku ?? 'N/A' }}">
                                        SKU: {{ \Illuminate\Support\Str::limit($prod->sku ?? 'N/A', 10) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="table-title">{{ $prod->brand->brand_name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="table-title">{{ $prod->category->name ?? 'N/A' }}</div>
                                    <div class="table-sub">{{ $prod->subcategory->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="price-main">₹{{ number_format($prod->price) }}</div>
                                    @if($prod->offer_price)
                                    <div class="price-offer">Offer: ₹{{ number_format($prod->offer_price) }}</div> @endif
                                </td>
                                <td>
                                    @if($prod->stock <= 0) <span class="stock-badge stock-out">Out of Stock</span>
                                    @elseif($prod->stock <= 5) <span class="stock-badge stock-low">Low Stock
                                        ({{ $prod->stock }})</span>
                                    @else <span class="stock-badge stock-in">In Stock ({{ $prod->stock }})</span> @endif
                                </td>
                                <td>
                                    @if($prod->is_featured) <span class="tag-badge tag-featured">Featured</span> @endif
                                    @if($prod->is_trending) <span class="tag-badge tag-trending">Trending</span> @endif
                                </td>
                                <td><span
                                        class="status-badge {{ $prod->status == 1 ? 'status-active' : 'status-inactive' }}">{{ $prod->status == 1 ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td>{{ $prod->created_at->format('d M Y') }}</td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.products.view', $prod->id) }}"
                                        class="action-btn btn-view">View</a>
                                    <a href="{{ route('admin.products.edit', $prod->id) }}"
                                        class="action-btn btn-edit">Edit</a>
                                    <a href="{{ route('admin.products.delete', $prod->id) }}" class="action-btn btn-delete"
                                        onclick="return confirm('Move to trash?')">Delete</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- <div class="pagination-container">
                <div class="pagination-info">Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of
                    {{ $products->total() }} products
                </div>
                {{ $products->appends(request()->query())->links() }}
            </div> -->

            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                <div class="pagination-info text-muted small">
                    Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of
                    {{ $products->total() }}
                    products
                </div>

                <nav>
                    {{ $products->appends(request()->query())->links('pagination::bootstrap-4') }}
                </nav>
            </div>

        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>

<script>
    function fetchFilterSubcategories(categoryId) {
        const subcategorySelect = document.getElementById('filter_subcategory');

        // Clear current options
        subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';

        if (!categoryId) return;

        // Use your existing AJAX route
        const url = `{{ url('admin/get-subcategories') }}/${categoryId}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                data.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.name;
                    subcategorySelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error:', error));
    }
</script>