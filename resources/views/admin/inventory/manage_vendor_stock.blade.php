<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Vendor Stock Inward</title>
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

        .action-cell {
            white-space: nowrap;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-right: 5px;
            display: inline-block;
            transition: 0.2s;
        }

        .btn-edit {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .btn-edit:hover {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        @media (max-width: 768px) {
            .pagination-container {
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
                <h1 class="page-title">Vendor Stock Ledger</h1>
                <div class="section-title">View and filter all inward inventory entries</div>
            </div>
            <div class="action-group">
                <a href="{{ route('admin.vendor_stock.add') }}" class="btn-action btn-manage">+ Add Stock Entry</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="property-form">
            <!-- Search & Filter Area -->
            <form action="{{ route('admin.vendor_stock.manage') }}" method="GET" class="filter-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Search Entry</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Vendor Name, Mobile, Product, Batch or Invoice"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->business_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search"><i
                                    class="bi bi-search"></i></button>
                            <a href="{{ route('admin.vendor_stock.manage') }}" class="icon-btn btn-refresh"
                                title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Data Table -->
            <div class="table-responsive bg-white rounded-4 shadow-sm border-0 mt-3 p-3">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Info</th>
                            <th>Vendor Info</th>
                            <th>Batch / Invoice</th>
                            <th>Qty Added</th>
                            <th>Current Stock</th>
                            <th>Dates</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $key => $stock)
                            <tr>
                                <td>{{ $stocks->firstItem() + $key }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @php
                                            $imgUrl = $stock->variant && $stock->variant->image
                                                ? asset('uploads/products/' . $stock->variant->image)
                                                : ($stock->product && $stock->product->image ? asset('uploads/products/' . $stock->product->image) : asset('images/no-image.png'));
                                        @endphp
                                        <img src="{{ $imgUrl }}" class="table-img" alt="IMG">
                                        <div>
                                            <div class="table-title">
                                                {{ \Illuminate\Support\Str::limit($stock->product->name ?? 'N/A', 30) }}
                                            </div>
                                            <div class="table-sub text-primary fw-semibold">
                                                {{ $stock->variant->variant_name ?? 'Default Variant' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-title">{{ $stock->vendor->business_name ?? 'N/A' }}</div>
                                    <div class="table-sub"><i class="bi bi-telephone-fill me-1"></i>
                                        {{ $stock->vendor->business_mobile ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="table-title">Batch: {{ $stock->batch_number ?? 'N/A' }}</div>
                                    <div class="table-sub">Inv: {{ $stock->invoice_number ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="stock-badge stock-in">+ {{ $stock->quantity }}</span>
                                </td>
                                <td>
                                    <div class="table-title" title="Current available stock for this variant">Var:
                                        {{ $stock->variant->stock ?? 0 }}</div>
                                    <div class="table-sub" title="Total overall stock for this product across all variants">
                                        Total: {{ $stock->product->stock ?? 0 }}</div>
                                </td>
                                <!-- ... Rest of the file remains exactly the same, update these specific <td> blocks ... -->

                                <td>
                                    <div class="table-title" style="font-size: 13px;">
                                        Rec:
                                        {{ $stock->received_date ? date('d-m-Y', strtotime($stock->received_date)) : 'N/A' }}
                                    </div>
                                    <div class="table-sub" style="font-size: 12px;">
                                        Exp:
                                        {{ $stock->expiry_date ? date('d-m-Y', strtotime($stock->expiry_date)) : 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="table-title" style="font-size: 13px;">
                                        {{ $stock->created_at->format('d-m-Y') }}
                                    </div>
                                </td>

                                <!-- ... Rest of the file remains exactly the same ... -->
                                <td class="action-cell">
                                    <a href="{{ route('admin.vendor_stock.edit', $stock->id) }}"
                                        class="action-btn btn-edit">Edit</a>
                                    <a href="{{ route('admin.vendor_stock.delete', $stock->id) }}"
                                        class="action-btn btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this inward entry? The overall stock will be reverted accordingly.')">Delete</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No stock inward records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                <div class="pagination-info text-muted small">
                    Showing {{ $stocks->firstItem() ?? 0 }}–{{ $stocks->lastItem() ?? 0 }} of {{ $stocks->total() }}
                    entries
                </div>
                <nav>
                    {{ $stocks->appends(request()->query())->links('pagination::bootstrap-4') }}
                </nav>
            </div>

        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>