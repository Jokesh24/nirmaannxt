<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Vendor Bulk Products</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Vendor Bulk Products</h1>
            <div class="section-title">Select a vendor to configure which products they can supply for bulk orders.
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="property-form">
            <form action="{{ route('admin.vendor_bulk.manage') }}" method="GET"
                class="filter-card bg-light p-3 rounded border mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Vendor</label>
                        <input type="text" name="search" class="form-control" placeholder="Business Name or Contact"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i> Search</button>
                        <a href="{{ route('admin.vendor_bulk.manage') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive bg-white border rounded">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vendor Business</th>
                            <th>Contact Person</th>
                            <th>Active Bulk Variants</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $key => $vendor)
                            <tr>
                                <td>{{ $vendors->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $vendor->business_name }}</td>
                                <td>{{ $vendor->contact_person }}<br><small
                                        class="text-muted">{{ $vendor->business_mobile }}</small></td>
                                <td>
                                    @if($vendor->bulk_products_count > 0)
                                        <span class="badge bg-success px-3 py-2">{{ $vendor->bulk_products_count }} Eligible
                                            Variants</span>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2">No Products Configured</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.vendor_bulk.edit', $vendor->id) }}"
                                        class="btn btn-sm btn-primary fw-bold px-3">
                                        Configure Bulk Products
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No vendors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $vendors->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>