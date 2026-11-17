<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Products</title>

    @include('admin.includes.header_links')

    <style>
        .note-box {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .upload-card {
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            padding: 40px;
            text-align: center;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Bulk Import Matrix</h1>
                <div class="section-title">Upload Multiple Products & Variants via CSV</div>
            </div>
            <div class="action-group">
                <a href="{{ route('admin.products.download_sample') }}" class="btn btn-dark text-white px-3 py-2"
                    style="border-radius: 10px;">
                    <i class="bi bi-download me-2"></i> Download CSV Template
                </a>
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="note-box">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Format Directive:</strong> Images cannot be embedded inside the CSV. Ensure you upload all required
            master and variant images to the <code>/public/uploads/products/</code> directory via FTP or File Manager
            prior to importing, and place the exact filename in the respective CSV columns. Group variants by using
            identical <code>product_name</code> values on consecutive rows.
        </div>

        <form action="{{ route('admin.products.process_bulk_upload') }}" method="post" enctype="multipart/form-data"
            class="property-form">
            @csrf

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Target Category <span class="req">*</span></label>
                    <select class="form-select" name="category_id" id="category_id" required
                        onchange="fetchSubcategories(this.value)">
                        <option value="">Select Category Pipeline</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Target Subcategory <span class="req">*</span></label>
                    <select class="form-select" name="subcategory_id" id="subcategory_id" required>
                        <option value="">Select Subcategory Pipeline First</option>
                    </select>
                </div>
            </div>

            <div class="upload-card mb-4 mt-2">
                <i class="bi bi-file-earmark-spreadsheet text-primary" style="font-size: 3rem;"></i>
                <h5 class="mt-3 font-weight-bold text-dark">Select Matrix File</h5>
                <p class="text-secondary mb-4">Accepts .csv format up to 20MB</p>
                <input type="file" name="csv_file" class="form-control w-50 mx-auto" accept=".csv, text/csv" required>
            </div>

            <div class="form-actions mt-4 d-flex gap-3 justify-content-end">
                <button type="button" class="btn btn-secondary px-4 py-2" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-success px-5 py-2">Execute Import Engine</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchSubcategories(categoryId) {
            const subcategorySelect = document.getElementById('subcategory_id');
            subcategorySelect.innerHTML = '<option value="">Loading mapping data...</option>';

            if (!categoryId) {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory Pipeline First</option>';
                return;
            }

            const url = `{{ url('admin/get-subcategories') }}/${categoryId}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">Select Target Subcategory</option>';
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subcategorySelect.appendChild(option);
                    });
                })
                .catch(err => {
                    subcategorySelect.innerHTML = '<option value="">Network Error: Cannot load subcategories</option>';
                });
        }
    </script>
</body>

</html>