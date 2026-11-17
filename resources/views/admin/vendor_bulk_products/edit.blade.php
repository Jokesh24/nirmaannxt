<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configure Bulk Products - {{ $vendor->business_name }}</title>
    @include('admin.includes.header_links')
    <style>
        .category-header {
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            margin-top: 30px;
        }

        .product-group {
            border: 1px solid #e2e8f0;
            border-top: none;
            background: #fff;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }

        .variant-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f8fafc;
            transition: 0.2s;
        }

        .variant-row:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .img-thumb {
            width: 45px;
            height: 45px;
            border-radius: 6px;
            object-fit: cover;
        }

        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            cursor: pointer;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Configure Bulk Eligibility</h1>
                <div class="section-title">Vendor: <strong class="text-primary">{{ $vendor->business_name }}</strong>
                </div>
            </div>
            <a href="{{ route('admin.vendor_bulk.manage') }}" class="btn btn-outline-dark">Back to Vendors</a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($categories->isEmpty())
            <div class="alert alert-warning">
                This vendor does not have any categories configured in their "Products Supplied" profile. Please update the
                vendor profile first.
            </div>
        @else
            <form action="{{ route('admin.vendor_bulk.update', $vendor->id) }}" method="POST" class="property-form">
                @csrf

                @foreach($categories as $category)
                    <div class="category-header">
                        <i class="bi bi-box-seam me-2"></i> Category: {{ $category->name }}
                    </div>
                    <div class="product-group shadow-sm">
                        @if($category->products->isEmpty())
                            <p class="text-muted mb-0">No active products found in this category.</p>
                        @endif

                        @foreach($category->products as $product)
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <img src="{{ asset('uploads/products/' . $product->image) }}" class="img-thumb border">
                                    {{ $product->name }}
                                </h6>

                                <div class="ps-4 ms-2 border-start border-2 border-primary product-variant-container" data-product-id="{{ $product->id }}">
                                    @forelse($product->variants as $variant)
                                        @php
                                            $isAssigned = in_array($variant->id, $assigned_variants);
                                            $vImg = $variant->image ? $variant->image : $product->image;
                                            $variantQty = $variant->quantity ?? $variant->qty ?? null;
                                            $variantUnit = $variant->unit ?? ($variant->units ?? '');
                                        @endphp
                                        <div class="variant-row">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ asset('uploads/products/' . $vImg) }}" class="img-thumb border bg-white">
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $variant->variant_name }}</div>
                                                    <small class="text-muted">
                                                        @if($variantQty || $variantUnit)
                                                            <strong>Qty:</strong> {{ $variantQty ?? '' }} {{ $variantUnit }} |
                                                        @endif
                                                        SKU: {{ $variant->sku ?? 'N/A' }} | Standard Price: ₹{{ $variant->price }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input variant-toggle" type="checkbox"
                                                    name="variant_ids[{{ $product->id }}]"
                                                    value="{{ $variant->id }}"
                                                    id="var_{{ $variant->id }}"
                                                    data-product-id="{{ $product->id }}"
                                                    {{ $isAssigned ? 'checked' : '' }}>
                                                <label
                                                    class="form-check-label ms-2 fw-semibold {{ $isAssigned ? 'text-success' : 'text-secondary' }}"
                                                    for="var_{{ $variant->id }}">
                                                    Eligible for Bulk
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted small">No variants configured for this product.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <div class="sticky-bottom bg-white p-4 border-top mt-5 shadow-lg d-flex justify-content-end rounded gap-3">
                    <a href="{{ route('admin.vendor_bulk.manage') }}" class="btn btn-secondary px-4 py-2">Discard Changes</a>
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold"><i class="bi bi-check2-circle me-2"></i>
                        Save Configurations</button>
                </div>
            </form>
        @endif
    </div>

    @include('admin.includes.footer_links')

    <script>
        document.querySelectorAll('.variant-toggle').forEach(toggle => {
            toggle.addEventListener('change', function () {
                const productId = this.getAttribute('data-product-id');
                const isChecked = this.checked;

                if (isChecked) {
                    // Turn off all other toggles within the same product section
                    document.querySelectorAll(`.variant-toggle[data-product-id="${productId}"]`).forEach(siblingToggle => {
                        if (siblingToggle !== this) {
                            siblingToggle.checked = false;
                            const siblingLabel = siblingToggle.nextElementSibling;
                            if (siblingLabel) {
                                siblingLabel.classList.remove('text-success');
                                siblingLabel.classList.add('text-secondary');
                            }
                        }
                    });

                    // Update current label to active
                    const label = this.nextElementSibling;
                    if (label) {
                        label.classList.remove('text-secondary');
                        label.classList.add('text-success');
                    }
                } else {
                    // Current toggle unchecked
                    const label = this.nextElementSibling;
                    if (label) {
                        label.classList.remove('text-success');
                        label.classList.add('text-secondary');
                    }
                }
            });
        });
    </script>
</body>

</html>