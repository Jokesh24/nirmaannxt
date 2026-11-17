<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Discount Coupon</title>
    @include('admin.includes.header_links')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-input {
            background-color: #fff !important;
            cursor: pointer;
        }

        .dynamic-field {
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
                <h1 class="page-title">Add Discount Coupon</h1>
                <div class="section-title">E-Commerce Discount Rules</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_coupon') }}" class="btn-action btn-manage">Manage Coupons</a>
            </div>
        </div>

        @if(Session::has('error'))
            <div class="alert alert-danger">{{ Session::get('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $error)
                <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form class="property-form" action="{{ route('store_coupon') }}" method="post" enctype="multipart/form-data"
            autocomplete="off">
            @csrf
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Coupon Logo</label>
                    <input type="file" class="form-control" name="logo" accept="image/*">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Coupon Title <span class="req">*</span></label>
                    <input type="text" class="form-control" name="title" value="{{ old('title') }}"
                        placeholder="e.g., Summer Sale" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Coupon Code <span class="req">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="code" value="{{ old('code') }}"
                        placeholder="e.g., SUMMER50" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Coupon Type <span class="req">*</span></label>
                    <select class="form-select" name="coupon_type" id="coupon_type" required>
                        <option value="Normal" {{ old('coupon_type') == 'Normal' ? 'selected' : '' }}>Normal</option>
                        <option value="Bill Threshold" {{ old('coupon_type') == 'Bill Threshold' ? 'selected' : '' }}>Bill
                            Threshold</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Discount Type <span class="req">*</span></label>
                    <select class="form-select" name="discount_type" required>
                        <option value="Percentage" {{ old('discount_type') == 'Percentage' ? 'selected' : '' }}>Percentage
                            (%)</option>
                        <option value="Flat" {{ old('discount_type') == 'Flat' ? 'selected' : '' }}>Flat Amount (₹)
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Discount Value <span class="req">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="discount_value"
                        value="{{ old('discount_value') }}" required>
                </div>

                <div class="col-md-4 dynamic-field" id="field_min_order">
                    <label class="form-label">Minimum Order Amount</label>
                    <input type="number" step="0.01" class="form-control" name="min_order_amount"
                        value="{{ old('min_order_amount') }}" placeholder="For normal applicability">
                </div>

                <div class="col-md-4 dynamic-field" id="field_threshold">
                    <label class="form-label">Bill Threshold Amount <span class="req">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="threshold_amount" id="input_threshold"
                        value="{{ old('threshold_amount') }}" placeholder="Amount cart must exceed">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Maximum Discount Amount</label>
                    <input type="number" step="0.01" class="form-control" name="max_discount"
                        value="{{ old('max_discount') }}" placeholder="Leave blank for unlimited">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Usage Limit (Per User/Total)</label>
                    <input type="number" class="form-control" name="usage_limit" value="{{ old('usage_limit') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Start Date <span class="req">*</span></label>
                    <input type="text" class="form-control" id="start_date" name="start_date"
                        value="{{ old('start_date') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">End Date <span class="req">*</span></label>
                    <input type="text" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="Expired" {{ old('status') == 'Expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="description"
                        name="description">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Coupon</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        CKEDITOR.replace('description', {height: 180});

        const startPicker = flatpickr("#start_date", {dateFormat: "d-m-Y", minDate: "today", onChange: function (dates) {endPicker.set('minDate', dates[0]);}});
        const endPicker = flatpickr("#end_date", {dateFormat: "d-m-Y", minDate: "today"});

        const typeSelect = document.getElementById('coupon_type');
        const minOrderField = document.getElementById('field_min_order');
        const thresholdField = document.getElementById('field_threshold');
        const thresholdInput = document.getElementById('input_threshold');

        function toggleFields() {
            if (typeSelect.value === 'Normal') {
                minOrderField.style.display = 'block';
                thresholdField.style.display = 'none';
                thresholdInput.removeAttribute('required');
            } else {
                minOrderField.style.display = 'none';
                thresholdField.style.display = 'block';
                thresholdInput.setAttribute('required', 'required');
            }
        }
        typeSelect.addEventListener('change', toggleFields);
        toggleFields();
    </script>
</body>

</html>