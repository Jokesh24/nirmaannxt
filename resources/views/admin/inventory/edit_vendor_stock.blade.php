<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Stock Entry</title>
    @include('admin.includes.header_links')

    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
        .edit-box {
            background: #fff;
            border: 1px solid #e9eef4;
            border-radius: 16px;
            padding: 24px;
        }

        .product-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }

        .req {
            color: #dc2626;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Edit Stock Entry</h1>
                <div class="section-title">Modify stock quantity and details. Overall inventory will adjust
                    automatically.</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="edit-box shadow-sm mt-3">
            <div class="product-card d-flex align-items-center gap-3">
                @php
                    $imgUrl = $stock->variant && $stock->variant->image
                        ? asset('uploads/products/' . $stock->variant->image)
                        : ($stock->product && $stock->product->image ? asset('uploads/products/' . $stock->product->image) : asset('images/no-image.png'));
                @endphp
                <img src="{{ $imgUrl }}"
                    style="width:60px; height:60px; border-radius:10px; object-fit:cover; border:1px solid #cbd5e1;">
                <div>
                    <h5 class="mb-1 fw-bold text-dark">{{ $stock->product->name ?? 'Unknown Product' }}</h5>
                    <div class="text-muted small">
                        <span
                            class="badge bg-primary me-2">{{ $stock->variant->variant_name ?? 'Default Variant' }}</span>
                        Vendor: <span class="fw-semibold">{{ $stock->vendor->business_name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.vendor_stock.update', $stock->id) }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Inward Quantity <span class="req">*</span></label>
                        <input type="number" name="quantity" class="form-control form-control-lg" required min="0"
                            value="{{ $stock->quantity }}">
                        <small class="text-muted mt-1 d-block">Modifying this will update the total available
                            stock.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Batch / Lot Number</label>
                        <input type="text" name="batch_number" class="form-control" value="{{ $stock->batch_number }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" name="invoice_number" class="form-control"
                            value="{{ $stock->invoice_number }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Received Date</label>
                        <input type="text" name="received_date" class="form-control datepicker"
                            value="{{ $stock->received_date ? \Carbon\Carbon::parse($stock->received_date)->format('d-m-Y') : '' }}"
                            placeholder="DD-MM-YYYY">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Expiry Date (Optional)</label>
                        <input type="text" name="expiry_date" class="form-control datepicker"
                            value="{{ $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('d-m-Y') : '' }}"
                            placeholder="DD-MM-YYYY">
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('admin.vendor_stock.manage') }}" class="btn btn-light px-4 border">Cancel</a>
                    <button type="submit" class="btn btn-dark px-5">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <!-- jQuery UI JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <script>
        $(document).ready(function () {
            $('.datepicker').datepicker({
                dateFormat: 'dd-mm-yy',
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
</body>

</html>