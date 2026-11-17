<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Assign Products | Dashboard</title>
    @include('admin.includes.header_links')
    <style>
        .filter-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .product-table th {
            background: #0f172a;
            color: #fff;
        }

        .img-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Assign Products to Vendor (Bulk Orders)</h1>
                <div class="section-title">Select a vendor and filter products to enable bulk ordering capability.</div>
            </div>
            <a href="{{ route('admin.vendor_bulk.manage') }}" class="btn btn-dark">Manage Mapped Products</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="property-form">
            <div class="filter-box shadow-sm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Select Vendor <span class="text-danger">*</span></label>
                        <select id="vendor_id" class="form-select" onchange="fetchProducts()">
                            <option value="">-- Choose Vendor --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->business_name }}
                                    ({{ $vendor->contact_person }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Category</label>
                        <select id="category_id" class="form-select" onchange="fetchProducts()">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Brand</label>
                        <select id="brand_id" class="form-select" onchange="fetchProducts()">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" onclick="fetchProducts()">
                            <i class="bi bi-search me-2"></i> Fetch Products
                        </button>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.vendor_bulk.store') }}" method="POST">
                @csrf
                <input type="hidden" name="vendor_id" id="form_vendor_id" required>

                <div class="table-responsive bg-white rounded shadow-sm border" style="display:none;"
                    id="results-container">
                    <table class="table table-hover align-middle mb-0 product-table">
                        <thead>
                            <tr>
                                <th style="width:50px;"><input type="checkbox" id="selectAll"
                                        onchange="toggleAll(this)"></th>
                                <th>Variant Detail</th>
                                <th>Base Product</th>
                                <th>Category / Brand</th>
                                <th>SKU</th>
                                <th>Base Price</th>
                            </tr>
                        </thead>
                        <tbody id="product_list">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                    <div class="p-3 bg-light border-top text-end">
                        <button type="submit" class="btn btn-success px-5 py-2 fw-bold">Save Assignments</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        function fetchProducts() {
            let vendor = document.getElementById('vendor_id').value;
            let category = document.getElementById('category_id').value;
            let brand = document.getElementById('brand_id').value;

            document.getElementById('form_vendor_id').value = vendor;

            if (!vendor) {
                document.getElementById('results-container').style.display = 'none';
                return;
            }

            fetch(`{{ route('admin.vendor_bulk.fetch') }}?vendor_id=${vendor}&category_id=${category}&brand_id=${brand}`)
                .then(res => res.json())
                .then(data => {
                    let tbody = document.getElementById('product_list');
                    tbody.innerHTML = '';

                    if (data.variants.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No products found for selected filters.</td></tr>';
                    } else {
                        data.variants.forEach(variant => {
                            let isChecked = data.assigned.includes(variant.id) ? 'checked' : '';
                            let imgPath = variant.image ? `{{ asset('uploads/products') }}/${variant.image}` : `{{ asset('uploads/products') }}/${variant.product.image}`;

                            let html = `
                                <tr>
                                    <td>
                                        <input type="hidden" name="displayed_variants[]" value="${variant.id}">
                                        <input type="checkbox" name="variant_ids[]" value="${variant.id}" class="row-chk" ${isChecked} style="width:18px; height:18px;">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="${imgPath}" class="img-thumb border">
                                            <div>
                                                <h6 class="mb-0 fw-bold">${variant.variant_name}</h6>
                                                <small class="text-muted">Unit: ${variant.qty} ${variant.unit}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>${variant.product.name}</td>
                                    <td>
                                        <span class="badge bg-secondary mb-1">${variant.product.category ? variant.product.category.name : 'N/A'}</span><br>
                                        <small class="text-primary fw-bold">${variant.product.brand ? variant.product.brand.brand_name : 'N/A'}</small>
                                    </td>
                                    <td>${variant.sku || 'N/A'}</td>
                                    <td class="fw-bold text-success">₹${variant.price}</td>
                                </tr>
                            `;
                            tbody.insertAdjacentHTML('beforeend', html);
                        });
                    }
                    document.getElementById('results-container').style.display = 'block';
                })
                .catch(err => console.error(err));
        }

        function toggleAll(source) {
            checkboxes = document.querySelectorAll('.row-chk');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</body>

</html>