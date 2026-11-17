<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product Subcategories</title>

    @include('admin.includes.header_links')

    <style>
        .subcat-block {
            background: #f8fafc;
            border: 1px solid #e9eef4;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
            position: relative;
        }

        .subcat-block-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .remove-subcat-btn {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 12px;
            background: #fee2e2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
            cursor: pointer;
        }

        .remove-subcat-btn:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        .add-more-subcat {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: #0f172a;
            color: #fff;
            padding: 12px 18px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .add-more-subcat:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .multi-note {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .image-preview-wrap {
            margin-top: 10px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .image-preview {
            width: 88px;
            height: 88px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #fff;
            display: none;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="page-head">
            <div>
                <h1 class="page-title">Add Product Subcategories</h1>
                <div class="section-title">One Category - Multiple Subcategories</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('admin.product_subcategories.manage') }}" class="btn-action btn-manage">Manage
                    Subcategories</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') ?? 'Please correct the highlighted errors below.' }}
                @if($errors->any())
                    <ul class="mt-2 mb-0">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form action="{{ route('admin.product_subcategory.store') }}" class="property-form" method="post"
            enctype="multipart/form-data" autocomplete="off">
            @csrf

            <div class="row g-4">

                <div class="col-md-4">
                    <label class="form-label">Category <span class="req">*</span></label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <div class="multi-note">
                        <i class="bi bi-info-circle me-2"></i>
                        Select <strong>one category</strong> and add <strong>multiple subcategories</strong> under it.
                    </div>
                </div>

            </div>

            <div id="subcategory-wrapper">

                <div class="subcat-block">
                    <div class="subcat-block-title">
                        <span>Subcategory #1</span>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-3">
                            <label class="form-label">Subcategory Name <span class="req">*</span></label>
                            <!-- Inside your HTML and also inside the JS template string -->
                            <input type="text" class="form-control" name="subcategory_name[]"
                                placeholder="Enter subcategory name" required pattern="[A-Za-z\s]+"
                                oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                title="Only letters and spaces are allowed">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" name="display_order[]" value="0" min="0"
                                oninput="this.value = Math.abs(this.value)" placeholder="Enter display order">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select class="form-select" name="status[]" required>
                                <option value="">Select Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control subcat-image-input" name="subcategory_image[]"
                                accept="image/*">
                            <div class="image-preview-wrap">
                                <img class="image-preview" alt="Preview">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mb-4">
                <button type="button" class="add-more-subcat" id="addMoreSubcategory">
                    <i class="bi bi-plus-circle"></i> Add More Subcategory
                </button>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Subcategories</button>
            </div>

        </form>

    </div>

    @include('admin.includes.footer_links')

    <script>
        let subcategoryCount = 1;

        document.getElementById("addMoreSubcategory").addEventListener("click", function () {
            subcategoryCount++;

            const wrapper = document.getElementById("subcategory-wrapper");

            const block = document.createElement("div");
            block.classList.add("subcat-block");

            block.innerHTML = `
            <div class="subcat-block-title">
                <span>Subcategory #${subcategoryCount}</span>
                <button type="button" class="remove-subcat-btn" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Subcategory Name <span class="req">*</span></label>
                    <!-- Inside your HTML and also inside the JS template string -->
<input type="text" 
       class="form-control" 
       name="subcategory_name[]" 
       placeholder="Enter subcategory name" 
       required
       pattern="[A-Za-z\s]+"
       oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
       title="Only letters and spaces are allowed">
                </div>

                <div class="col-md-3">
    <label class="form-label">Display Order</label>
    <input type="number" class="form-control" name="display_order[]" 
        value="0" min="0" oninput="this.value = Math.abs(this.value)" 
        placeholder="Enter display order">
</div>
 
                <div class="col-md-3">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status[]" required>
                        <option value="">Select Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Image</label>
                    <input type="file" class="form-control subcat-image-input" name="subcategory_image[]" accept="image/*">
                    <div class="image-preview-wrap">
                        <img class="image-preview" alt="Preview">
                    </div>
                </div>
            </div>
        `;

            wrapper.appendChild(block);

            block.querySelector(".remove-subcat-btn").addEventListener("click", function () {
                block.remove();
                updateSubcategoryTitles();
            });

            bindImagePreview(block);
        });

        function updateSubcategoryTitles() {
            const blocks = document.querySelectorAll(".subcat-block");
            blocks.forEach((block, index) => {
                const title = block.querySelector(".subcat-block-title span");
                title.textContent = `Subcategory #${index + 1}`;
            });
            subcategoryCount = blocks.length;
        }

        function bindImagePreview(scope = document) {
            const imageInputs = scope.querySelectorAll(".subcat-image-input");

            imageInputs.forEach(input => {
                if (input.dataset.bound === "true") return;

                input.addEventListener("change", function () {
                    const preview = this.closest(".col-md-2").querySelector(".image-preview");

                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            preview.src = e.target.result;
                            preview.style.display = "block";
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        preview.src = "";
                        preview.style.display = "none";
                    }
                });

                input.dataset.bound = "true";
            });
        }

        bindImagePreview();
    </script>

</body>

</html>