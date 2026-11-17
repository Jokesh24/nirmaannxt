<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | NirmaanNXT Dashboard</title>
    @include('admin.includes.header_links')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

    <style>
        .image-preview-wrap {
            margin-top: 10px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .image-preview,
        .gallery-preview {
            width: 88px;
            height: 88px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #fff;
            display: none;
        }

        .gallery-preview {
            display: block;
        }

        .step-indicator {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-weight: 600;
            font-size: 15px;
            transition: 0.3s;
        }

        .step-item.active {
            color: #0f172a;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #64748b;
            transition: 0.3s;
        }

        .step-item.active .step-circle {
            background: #16a34a;
            color: #ffffff;
        }

        .step-section {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
        }

        .step-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .variant-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .delivery-pricing-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 15px;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="page-head">
            <div>
                <h1 class="page-title">Add Product</h1>
                <div class="section-title">Configure catalog and dual-tier pricing for NirmaanNXT Mobile App</div>
            </div>
            <div class="action-group">
                <a href="{{ route('admin.products.manage') }}" class="btn-action btn-manage">Manage Products</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form id="catalogForm" action="{{ route('admin.products.store') }}" class="property-form" method="post"
            enctype="multipart/form-data" autocomplete="off">
            @csrf

            <!-- STEP INDICATOR -->
            <div class="step-indicator">
                <div class="step-item active" id="ind-step-1">
                    <div class="step-circle">1</div>
                    Master Product & Pricing Details
                </div>
                <div class="step-item" id="ind-step-2">
                    <div class="step-circle">2</div>
                    Variant Configurations
                </div>
                <div class="step-item" id="ind-step-3">
                    <div class="step-circle">3</div>
                    Why You'll Like It
                </div>
            </div>

            <!-- STEP 1: MASTER PRODUCT DETAILS -->
            <div id="step-1" class="step-section active">
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">Product Name <span class="req">*</span></label>
                        <input type="text" id="master_name" class="form-control" name="product_name"
                            placeholder="e.g. UltraTech Cement" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Brand <small class="text-secondary">(Optional)</small></label>
                        <div class="input-group">
                            <select class="form-select" name="brand_id" id="brand_id">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#addBrandModal">+</button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <div class="input-group">
                            <select class="form-select" name="category_id" id="category_id" required
                                onchange="fetchSubcategories(this.value)">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#addCatModal">+</button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Subcategory <small class="text-secondary">(Optional)</small></label>
                        <div class="input-group">
                            <select class="form-select" name="subcategory_id" id="subcategory_id">
                                <option value="">Select Subcategory</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#addSubcatModal">+</button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Base SKU</label>
                        <input type="text" id="master_sku" class="form-control" name="sku"
                            placeholder="e.g. UTC-OPC-53">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Unit</label>
                        <select class="form-select" name="unit" id="master_unit">
                            <option value="">Select Unit</option>
                            <option value="Bag">Bag</option>
                            <option value="Ton">Ton</option>
                            <option value="Pcs">Pcs</option>
                            <option value="kg">kg</option>
                            <option value="ltr">ltr</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Unit Qty / Value <small class="text-secondary">(e.g.
                                50)</small></label>
                        <input type="number" step="0.01" id="master_qty" class="form-control" name="qty"
                            placeholder="e.g. 50" min="0">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Royalty Reward (% Points) <span class="req">*</span></label>
                        <input type="number" step="0.01" id="master_royalty_percentage" class="form-control"
                            name="royalty_percentage" placeholder="e.g. 10" min="0" max="100" value="0">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Delivery SLA (Days)</label>
                        <input type="number" id="master_sla_days" class="form-control" name="sla_days"
                            placeholder="e.g. 2" min="0">
                    </div>
                </div>
                <div class="row mt-4 mb-4">

                    <!-- DUAL DELIVERY PRICING TIERS -->
                    <div class="col-md-6 mb-4">
                        <div class="delivery-pricing-box">
                            <h6 class="text-primary font-weight-bold mb-3"><i class="bi bi-truck me-2"></i> Standard
                                Delivery (24-48 Hours)</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Standard Price <span class="req">*</span></label>
                                    <input type="number" step="0.01" id="master_price" class="form-control" name="price"
                                        placeholder="Base price" min="0" oninput="this.value = Math.abs(this.value)"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Standard Offer Price</label>
                                    <input type="number" step="0.01" id="master_offer_price" class="form-control"
                                        name="offer_price" placeholder="Offer price" min="0"
                                        oninput="this.value = Math.abs(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="delivery-pricing-box" style="background:#f0fdf4; border-color:#86efac;">
                            <h6 class="text-success font-weight-bold mb-3"><i class="bi bi-lightning-charge me-2"></i>
                                Instant Delivery (24/48 Hours Express)</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Instant Price</label>
                                    <input type="number" step="0.01" id="master_instant_price" class="form-control"
                                        name="instant_price" placeholder="Instant price" min="0"
                                        oninput="this.value = Math.abs(this.value)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Instant Offer Price</label>
                                    <input type="number" step="0.01" id="master_instant_offer_price"
                                        class="form-control" name="instant_offer_price"
                                        placeholder="Instant offer price" min="0"
                                        oninput="this.value = Math.abs(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Trending List?</label>
                        <select class="form-select" name="is_trending">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Master Display Image <span class="req">*</span></label>
                        <input type="file" class="form-control" id="productImage" name="product_image" accept="image/*"
                            required>
                        <div class="image-preview-wrap">
                            <img id="productPreview" class="image-preview" alt="Preview">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Global Gallery Images</label>
                        <input type="file" class="form-control" id="galleryImages" name="gallery_images[]"
                            accept="image/*" multiple>
                        <div class="image-preview-wrap" id="galleryPreviewWrap"></div>
                    </div>

                    <div class="col-12">
                        <div class="mb-2 mt-3">
                            <label class="form-label font-weight-bold mb-0">Master Product Description</label>
                        </div>
                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                    </div>
                </div>

                <div class="form-actions mt-5 d-flex gap-3 justify-content-end border-top pt-4">
                    <button type="button" class="btn btn-dark px-5 py-2 d-flex align-items-center gap-2"
                        onclick="goToStep(2)">
                        Next: Configure Variants <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 2: VARIANTS SECTION -->
            <div id="step-2" class="step-section">
                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded border">
                    <div>
                        <h5 style="font-weight:700; color:#0f172a; margin-bottom: 5px;">Variant Configurations</h5>
                        <p class="text-secondary mb-0" style="font-size:14px;">Manage dynamic packaging, sizes, and
                            pricing options.</p>
                    </div>
                    <button type="button" class="btn btn-outline-dark" style="border-radius:8px;"
                        onclick="addVariant(false)">+ Add Additional Variant Row</button>
                </div>

                <div id="variant-container"></div>

                <div class="form-actions mt-5 d-flex gap-3 justify-content-between border-top pt-4">
                    <button type="button" class="btn btn-secondary px-4 py-2" onclick="goToStep(1)">
                        <i class="bi bi-arrow-left me-2"></i> Back to Details
                    </button>
                    <button type="button" class="btn btn-dark px-5 py-2 d-flex align-items-center gap-2"
                        onclick="goToStep(3)">
                        Next: Features & Benefits <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 3: WHY YOU'LL LIKE IT -->
            <div id="step-3" class="step-section">
                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded border">
                    <div>
                        <h5 style="font-weight:700; color:#0f172a; margin-bottom: 5px;">Why You'll Like It</h5>
                        <p class="text-secondary mb-0" style="font-size:14px;">Highlight key features and product
                            benefits.</p>
                    </div>
                    <button type="button" class="btn btn-outline-dark" style="border-radius:8px;"
                        onclick="addReason()">+ Add Benefit Row</button>
                </div>

                <div id="reasons-container"></div>

                <div class="form-actions mt-5 d-flex gap-3 justify-content-between border-top pt-4">
                    <button type="button" class="btn btn-secondary px-4 py-2" onclick="goToStep(2)">
                        <i class="bi bi-arrow-left me-2"></i> Back to Variants
                    </button>
                    <button type="submit" class="btn btn-success px-5 py-2 font-weight-bold">
                        <i class="bi bi-check-circle me-2"></i> Complete & Save Product
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Quick Add Brand Modal -->
    <div class="modal fade" id="addBrandModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="quickBrandForm" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" class="form-control" placeholder="E.g., UltraTech, Ambuja"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Brand Logo <small class="text-secondary">(Optional)</small></label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <input type="hidden" name="status" value="Active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Brand</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div class="modal fade" id="addCatModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="quickCatForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="text" name="category_name" class="form-control mb-2" placeholder="Category Name"
                        required>
                    <select name="status" class="form-select">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Subcategory Modal -->
    <div class="modal fade" id="addSubcatModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="quickSubcatForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <select name="category_id" id="modal_cat_select" class="form-select mb-2" required>
                        @foreach($categories as $cat)
                            <option value="{{$cat->id}}">{{$cat->name}}</option>
                        @endforeach
                    </select>
                    <input type="text" name="subcategory_name[]" class="form-control mb-2"
                        placeholder="Subcategory Name" required>
                    <input type="hidden" name="status[]" value="Active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Subcategory</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function goToStep(step) {
            if (step > 1) {
                let isValid = true;
                $(`#step-${step - 1} [required]`).each(function () {
                    if (!this.checkValidity()) {
                        this.reportValidity();
                        isValid = false;
                        return false;
                    }
                });
                if (!isValid) return;
            }

            $('.step-section').removeClass('active');
            $(`#step-${step}`).addClass('active');

            $('.step-item').removeClass('active');
            $(`#ind-step-${step}`).addClass('active');

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        $(document).ready(function () {
            CKEDITOR.replace('description', { height: 200 });
            addVariant(true);
            addReason(); // Initialize first reason empty row
            setupDataMirroring();
        });

        // ------------------ VARIANTS LOGIC ------------------
        let variantCount = 0;

        function addVariant(isFirstRow = false) {
            const container = document.getElementById('variant-container');

            const template = `
            <div class="variant-card shadow-sm" id="variant_${variantCount}">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h6 class="mb-0 font-weight-bold ${isFirstRow ? 'text-success' : 'text-primary'}">
                        ${isFirstRow ? 'Primary Variant (Inherits Data from Master Product)' : 'Variant Option #' + (variantCount + 1)}
                    </h6>
                    ${isFirstRow ? '' : `<button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariantBlock(${variantCount})">Remove Variant</button>`}
                </div>
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Variant Name</label>
                        <input type="text" name="variant_name[]" id="v_name_${variantCount}" class="form-control" placeholder="e.g., 50 Kg Bag" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Variant SKU</label>
                        <input type="text" name="variant_sku[]" id="v_sku_${variantCount}" class="form-control" placeholder="Unique SKU" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Standard Price</label>
                        <input type="number" step="0.01" name="variant_price[]" id="v_price_${variantCount}" class="form-control" min="0" oninput="this.value = Math.abs(this.value)" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Standard Offer Price</label>
                        <input type="number" step="0.01" name="variant_offer_price[]" id="v_oprice_${variantCount}" class="form-control" min="0" oninput="this.value = Math.abs(this.value)" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Instant Price</label>
                        <input type="number" step="0.01" name="variant_instant_price[]" id="v_iprice_${variantCount}" class="form-control" min="0" oninput="this.value = Math.abs(this.value)" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Instant Offer Price</label>
                        <input type="number" step="0.01" name="variant_instant_offer_price[]" id="v_ioprice_${variantCount}" class="form-control" min="0" oninput="this.value = Math.abs(this.value)" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Royalty %</label>
                        <input type="number" step="0.01" name="variant_royalty_percentage[]" id="v_royalty_${variantCount}" class="form-control" min="0" max="100" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit</label>
                        <select name="variant_unit[]" id="v_unit_${variantCount}" class="form-select" ${isFirstRow ? 'disabled style="background:#e2e8f0;"' : ''}>
                            <option value="">Inherit Base</option>
                            <option value="Bag">Bag</option>
                            <option value="Ton">Ton</option>
                            <option value="Pcs">Pcs</option>
                            <option value="kg">kg</option>
                            <option value="ltr">ltr</option>
                        </select>
                    </div>

                    <div class="col-md-2">
    <label class="form-label">Unit Value / Qty</label>
    <input type="number" step="0.01" name="variant_qty[]" id="v_qty_${variantCount}" class="form-control" placeholder="e.g. 50" min="0" ${isFirstRow ? 'readonly style="background:#e2e8f0;"' : ''}>
</div>

                    <div class="col-md-3">
                        <label class="form-label">Size Option</label>
                        <select name="variant_size[]" class="form-select">
                            <option value="">Select Size / N/A</option>
                            @foreach($sizes as $size)
                                <option value="{{ $size->name }}">{{ $size->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Color Option</label>
                        <select name="variant_color[]" class="form-select">
                            <option value="">Select Color / N/A</option>
                            @foreach($colours as $colour)
                                <option value="{{ $colour->name }}">{{ $colour->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="variant_status[]" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="form-check form-switch bg-light border p-2 px-3 rounded-pill d-inline-block">
                            <input class="form-check-input custom-image-toggle" type="checkbox" id="custom_img_toggle_${variantCount}" data-target="custom_img_container_${variantCount}" ${isFirstRow ? 'disabled' : ''}>
                            <label class="form-check-label font-weight-bold text-dark ms-2" for="custom_img_toggle_${variantCount}" style="font-size: 13px; cursor:${isFirstRow ? 'not-allowed' : 'pointer'};">
                                ${isFirstRow ? 'Primary Variant Integrates Master Images Automatically' : 'Upload Custom Images for this Variant'}
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-3" id="custom_img_container_${variantCount}" style="display: none;">
                        <div class="row g-3 p-3 bg-white border rounded">
                            <div class="col-md-6">
                                <label class="form-label">Variant Custom Image</label>
                                <input type="file" name="variant_image[${variantCount}]" class="form-control" accept="image/*" ${isFirstRow ? 'disabled' : ''}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Variant Custom Gallery</label>
                                <input type="file" name="variant_gallery[${variantCount}][]" class="form-control" accept="image/*" multiple ${isFirstRow ? 'disabled' : ''}>
                            </div>
                        </div>
                    </div>

                </div>
            </div>`;

            container.insertAdjacentHTML('beforeend', template);
            variantCount++;
        }

        $(document).on('change', '.custom-image-toggle', function () {
            let targetId = $(this).data('target');
            if ($(this).is(':checked')) {
                $('#' + targetId).slideDown(200);
            } else {
                $('#' + targetId).slideUp(200);
                $('#' + targetId).find('input[type="file"]').val('');
            }
        });

        function removeVariantBlock(idx) {
            document.getElementById(`variant_${idx}`).remove();
        }

        function setupDataMirroring() {
            const mappings = [
                { source: '#master_name', target: '#v_name_0' },
                { source: '#master_sku', target: '#v_sku_0' },
                { source: '#master_price', target: '#v_price_0' },
                { source: '#master_offer_price', target: '#v_oprice_0' },
                { source: '#master_instant_price', target: '#v_iprice_0' },
                { source: '#master_instant_offer_price', target: '#v_ioprice_0' },
                { source: '#master_royalty_percentage', target: '#v_royalty_0' },
                { source: '#master_unit', target: '#v_unit_0' },
                { source: '#master_qty', target: '#v_qty_0' }
            ];

            mappings.forEach(map => {
                $(document).on('input change', map.source, function () {
                    $(map.target).val($(this).val());
                });
            });
        }

        $('#catalogForm').on('submit', function () {
            $('#v_unit_0, #variant_0 input[type="file"]').removeAttr('disabled');
        });

        // ------------------ REASONS LOGIC ------------------
        let reasonCount = 0;

        function addReason() {
            const container = document.getElementById('reasons-container');
            const template = `
            <div class="variant-card shadow-sm mb-3" id="reason_row_${reasonCount}">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h6 class="mb-0 font-weight-bold text-dark">Benefit Option #${reasonCount + 1}</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeReasonBlock(${reasonCount})">Remove</button>
                </div>
                
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Icon (Image)</label>
                        <input type="file" name="reason_icon[${reasonCount}]" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Title <span class="req">*</span></label>
                        <input type="text" name="reason_title[${reasonCount}]" class="form-control" placeholder="e.g., Ultra Durable">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Tagline / Description</label>
                        <input type="text" name="reason_tagline[${reasonCount}]" class="form-control" placeholder="e.g., Built to last for generations">
                    </div>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', template);
            reasonCount++;
        }

        function removeReasonBlock(idx) {
            document.getElementById(`reason_row_${idx}`).remove();
        }

        // ------------------ IMAGE PREVIEWS ------------------
        document.getElementById('productImage').addEventListener('change', function () {
            const preview = document.getElementById('productPreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        document.getElementById('galleryImages').addEventListener('change', function () {
            const previewWrap = document.getElementById('galleryPreviewWrap');
            previewWrap.innerHTML = '';
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('gallery-preview');
                        previewWrap.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });

        // ------------------ AJAX DROPDOWNS ------------------
        function fetchSubcategories(categoryId) {
            const subcategorySelect = document.getElementById('subcategory_id');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            if (!categoryId) return;
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
                });
        }

        $('#quickBrandForm').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: "{{ route('store_brand') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        $('#addBrandModal').modal('hide');
                        $('#brand_id').append('<option value="' + res.id + '" selected>' + res.name + '</option>');
                        $('#quickBrandForm')[0].reset();
                    } else {
                        alert('Something went wrong!');
                    }
                },
                error: function (xhr) {
                    alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Upload failed'));
                },
                complete: function () {
                    submitBtn.prop('disabled', false).text('Save Brand');
                }
            });
        });

        $('#quickCatForm').on('submit', function (e) {
            e.preventDefault();
            $.post("{{ route('admin.product_category.store') }}", $(this).serialize(), function (res) {
                location.reload();
            });
        });

        $('#quickSubcatForm').on('submit', function (e) {
            e.preventDefault();
            $.post("{{ route('admin.product_subcategory.store') }}", $(this).serialize(), function (res) {
                $('#addSubcatModal').modal('hide');
                fetchSubcategories($('#category_id').val());
            });
        });
    </script>
</body>

</html>