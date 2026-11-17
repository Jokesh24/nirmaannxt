<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Stock Inward</title>
    @include('admin.includes.header_links')

    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
        .filter-box {
            background: #f8fafc;
            border: 1px solid #e9eef4;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .table-img {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }

        .variant-row {
            transition: background 0.2s;
        }

        .variant-row:hover {
            background: #f8fafc;
        }

        .category-header {
            background-color: #f1f5f9;
            border-top: 2px solid #e2e8f0;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Vendor Stock Inward</h1>
                <div class="section-title">Add inventory mapped to vendor's supplied categories</div>
            </div>
            <div class="action-group">
                <a href="{{ route('admin.vendor_stock.manage') }}" class="btn-action btn-manage">Manage Stock</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('admin.vendor_stock.store') }}" method="POST" autocomplete="off">
            @csrf

            <!-- Filter Selection Area -->
            <div class="filter-box shadow-sm">
                <h5 class="mb-4 font-weight-bold text-dark">Step 1: Select Vendor</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Vendor <span class="req">*</span></label>
                        <select class="form-select" name="vendor_id" id="vendor_id" required
                            onchange="fetchVariants(this.value)">
                            <option value="">Select Vendor to auto-fetch products</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->business_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Variants Data Entry Grid -->
            <div class="card border-0 shadow-sm rounded-4" style="background:#fff;">
                <div class="card-body p-4">
                    <h5 class="mb-4 font-weight-bold text-dark">Step 2: Enter Batch & Stock Specifications</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Detail</th>
                                    <th>Current Stock</th>
                                    <th style="width: 120px;">Qty to Add</th>
                                    <th>Batch No.</th>
                                    <th>Received Dt.</th>
                                    <th>Expiry Dt. <small>(Opt)</small></th>
                                    <th>Invoice <small>(Opt)</small></th>
                                </tr>
                            </thead>
                            <tbody id="variants-tbody">
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="bi bi-box-seam fs-1 mb-2 d-block"></i>
                                            Select a vendor above to populate products here.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="form-actions mt-4 d-flex gap-3 justify-content-end">
                <button type="button" class="btn btn-secondary px-4 py-2" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-success px-5 py-2" id="submitBtn" disabled>Commit Inventory to
                    Ledger</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')

    <!-- jQuery UI JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <script>
        function fetchVariants(vendor_id) {
            const tbody = document.getElementById('variants-tbody');
            const submitBtn = document.getElementById('submitBtn');

            if (!vendor_id) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="bi bi-box-seam fs-1 mb-2 d-block"></i>Select a vendor above to populate products here.</div></td></tr>`;
                submitBtn.disabled = true;
                return;
            }

            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';

            $.ajax({
                url: "{{ route('admin.vendor_stock.get_variants') }}",
                type: "GET",
                data: { vendor_id: vendor_id },
                success: function (res) {
                    if (res.success && res.data.length > 0) {
                        let rows = '';

                        // Get current date in DD-MM-YYYY format
                        let dateObj = new Date();
                        let dd = String(dateObj.getDate()).padStart(2, '0');
                        let mm = String(dateObj.getMonth() + 1).padStart(2, '0');
                        let yyyy = dateObj.getFullYear();
                        let todayStr = dd + '-' + mm + '-' + yyyy;

                        // Grouping logic: Create an object holding variants mapped by their Category Name
                        let groupedVariants = {};
                        res.data.forEach(variant => {
                            let categoryName = (variant.product && variant.product.category && variant.product.category.name)
                                ? variant.product.category.name
                                : 'Other / Uncategorized';

                            if (!groupedVariants[categoryName]) {
                                groupedVariants[categoryName] = [];
                            }
                            groupedVariants[categoryName].push(variant);
                        });

                        // Render grouped data
                        for (const [category, variants] of Object.entries(groupedVariants)) {

                            // 1. Inject Category Header Row
                            rows += `
                            <tr class="category-header">
                                <td colspan="7" class="fw-bold text-dark py-2">
                                    <i class="bi bi-tags-fill me-2 text-primary"></i> ${category}
                                </td>
                            </tr>
                            `;

                            // 2. Inject Variants for this Category
                            variants.forEach(variant => {
                                let imgUrl = variant.image ? `{{ asset('uploads/products') }}/${variant.image}` : `{{ asset('images/no-image.png') }}`;
                                let productName = variant.product ? variant.product.name : 'Unknown Product';

                                rows += `
                                <tr class="variant-row border-bottom">
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="${imgUrl}" class="table-img" alt="IMG">
                                            <div>
                                                <div class="font-weight-bold text-dark" style="font-size:14px;">${productName}</div>
                                                <div class="text-primary fw-semibold" style="font-size:13px;">${variant.variant_name}</div>
                                                <div class="text-muted" style="font-size:12px;">SKU: ${variant.sku || 'N/A'}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div>
                                                <div>Current Stock: <span class="badge bg-secondary">${variant.stock}</span></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" name="stock_data[${variant.id}][quantity]" placeholder="Qty" min="1" oninput="this.value = Math.abs(this.value)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="stock_data[${variant.id}][batch_number]" placeholder="Batch/Lot">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm datepicker" name="stock_data[${variant.id}][received_date]" value="${todayStr}" placeholder="DD-MM-YYYY">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm datepicker" name="stock_data[${variant.id}][expiry_date]" placeholder="DD-MM-YYYY">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="stock_data[${variant.id}][invoice_number]" placeholder="Invoice No.">
                                    </td>
                                </tr>
                                `;
                            });
                        }

                        tbody.innerHTML = rows;
                        submitBtn.disabled = false;

                        // Re-initialize Datepicker for newly added rows with DD-MM-YYYY format
                        $('.datepicker').datepicker({
                            dateFormat: 'dd-mm-yy',
                            changeMonth: true,
                            changeYear: true
                        });

                    } else {
                        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state text-danger"><i class="bi bi-x-circle fs-2 mb-2 d-block"></i>No products found matching the categories supplied by this vendor.</div></td></tr>`;
                        submitBtn.disabled = true;
                    }
                },
                error: function (xhr) {
                    alert('Error fetching variants. Please try again.');
                    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">Error fetching data.</div></td></tr>`;
                }
            });
        }
    </script>
</body>

</html>