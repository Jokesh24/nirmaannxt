<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vendor Response - Bulk Quote #ORD{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</title>
    @include('admin.includes.header_links')

    <style>
        body { background-color: #f8f9fa; }
        .card-custom { border-radius: 10px; border: 1px solid #ebedf0; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); background-color: #ffffff; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #8792a2; font-weight: 600; }
        .info-value { font-size: 0.95rem; color: #374151; font-weight: 500; }
        .table-custom th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #8792a2; border-bottom: 1px solid #ebedf0; background-color: #fcfcfd; padding: 0.85rem; }
        .table-custom td { padding: 0.85rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; color: #4b5563; }
        .badge-soft { font-weight: 500; padding: 0.5em 0.8em; font-size: 0.75rem; border-radius: 6px; }
        .badge-soft-primary { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-soft-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-soft-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-soft-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-soft-secondary { background-color: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
        .btn-elegant { background-color: #ffffff; border: 1px solid #e5e7eb; color: #4b5563; border-radius: 6px; font-size: 0.8rem; padding: 0.5rem 1rem; font-weight: 500; }
        .btn-elegant:hover { background-color: #f9fafb; border-color: #d1d5db; color: #111827; }
        .form-label { color: #6b7280; font-weight: 500; font-size: 0.85rem; }
    </style>
</head>

<body class="bg-light">
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content py-4 px-3 px-md-4">
        <div class="page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title fw-bold mb-0 text-dark" style="font-size: 1.5rem;">
                    Vendor Response - Bulk Quote #ORD{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}
                </h2>
                <p class="text-muted small mb-0 mt-1">
                    Review the request on behalf of
                    <strong>{{ $assignment->vendor->business_name ?? 'the vendor' }}</strong> and accept or reject it.
                </p>
            </div>
            <a href="{{ route('view_bulk_quote', $quote->id) }}" class="btn btn-elegant">
                <i class="bi bi-arrow-left me-1"></i> Back to Quote
            </a>
        </div>

        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" style="background-color: #f0fdf4; color: #16a34a;">
                <i class="bi bi-check-circle-fill me-2"></i>{{ Session::get('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" style="background-color: #fef2f2; color: #dc2626;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" style="background-color: #fef2f2; color: #dc2626;">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="info-label mb-1">Customer</div>
                        <div class="info-value">{{ $quote->full_name ?? ($quote->user->name ?? 'N/A') }}</div>
                        <div class="small text-muted">{{ $quote->phone_number ?? ($quote->user->mobile ?? 'No contact') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label mb-1">Delivery Location</div>
                        <div class="info-value">{{ $quote->delivery_location }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label mb-1">Preferred Delivery</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($quote->preferred_delivery_date)->format('d M, Y') }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label mb-1">Payment Preference</div>
                        <div class="info-value">{{ $quote->payment_preference }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label mb-1">Status</div>
                        @php
                            $quoteBadge = 'badge-soft-secondary';
                            if ($quote->status === 'Sent To Vendors' || $quote->status === 'Quote Locked') $quoteBadge = 'badge-soft-primary';
                            if ($quote->status === 'Quoted' || $quote->status === 'Confirmed' || $quote->status === 'Completed') $quoteBadge = 'badge-soft-success';
                            if ($quote->status === 'Cancelled') $quoteBadge = 'badge-soft-danger';
                            if ($quote->status === 'Pending') $quoteBadge = 'badge-soft-warning';
                        @endphp
                        <span class="badge {{ $quoteBadge }}">{{ $statusMap[$quote->status] ?? $quote->status }}</span>
                    </div>
                    @if($quote->gstin)
                        <div class="col-md-3">
                            <div class="info-label mb-1">GSTIN</div>
                            <div class="info-value">{{ $quote->gstin }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card card-custom h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3">Vendor</h6>
                        <div class="info-label mb-1">Business Name</div>
                        <div class="info-value mb-3">{{ $assignment->vendor->business_name ?? 'Unknown Vendor' }}</div>
                        <div class="info-label mb-1">Contact Person</div>
                        <div class="info-value mb-3">{{ $assignment->vendor->contact_person ?? '-' }}</div>
                        <div class="info-label mb-1">Mobile</div>
                        <div class="info-value mb-3">{{ $assignment->vendor->business_mobile ?? '-' }}</div>
                        <div class="info-label mb-1">Email</div>
                        <div class="info-value mb-3">{{ $assignment->vendor->business_email ?? '-' }}</div>
                        <div class="info-label mb-1">Address</div>
                        <div class="info-value">{{ $assignment->vendor->address ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card card-custom h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Products Offered by This Vendor</h6>
                                <p class="small text-muted mb-0 mt-1">Provide pricing, tax, and delivery charges for each valid item.</p>
                            </div>
                            @php
                                $vBadge = 'badge-soft-warning';
                                if ($assignment->status === 'Accepted') $vBadge = 'badge-soft-success';
                                if ($assignment->status === 'Declined') $vBadge = 'badge-soft-danger';
                                if ($assignment->status === 'Quoted') $vBadge = 'badge-soft-primary';
                            @endphp
                            <span class="badge {{ $vBadge }}">{{ $assignment->status }}</span>
                        </div>

                        @if($suppliedVariants->isEmpty())
                            <p class="text-muted small mb-0 mt-3">This vendor does not offer bulk for any item in this quote.</p>
                        @else
                            <form action="{{ route('bulk_quote_vendor_response') }}" method="POST">
                                @csrf
                                <input type="hidden" name="bulk_quote_request_id" value="{{ $quote->id }}">
                                <input type="hidden" name="vendor_id" value="{{ $assignment->vendor_id }}">

                                <div class="table-responsive mt-3">
                                    <table class="table table-custom mb-3">
                                        <thead>
                                            <tr>
                                                <th class="ps-0">Product</th>
                                                <th>Qty</th>
                                                <th style="min-width: 140px;">Unit Price (₹)</th>
                                                <th style="min-width: 100px;">GST %</th>
                                                <th style="min-width: 130px;">Delivery (₹)</th>
                                                <th class="text-end pe-0">Line Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($suppliedVariants as $variantRow)
                                                @php
                                                    $variant = $variantRow['variant'];
                                                    $product = $variantRow['product'];
                                                    $variantId = $variantRow['variantId'];
                                                    
                                                    $defaultPrice = $variantQuotes[$variantId]['price'] ?? '';
                                                    $defaultGst = $variantQuotes[$variantId]['gst'] ?? 0;
                                                    $defaultDelivery = $variantQuotes[$variantId]['delivery'] ?? 0;

                                                    $unitPrice = old('quoted_unit_price.' . $variantId, $defaultPrice);
                                                    $gst = old('gst_percentage.' . $variantId, $defaultGst);
                                                    $delivery = old('delivery_charges.' . $variantId, $defaultDelivery);
                                                @endphp
                                                <tr>
                                                    <td class="ps-0">
                                                        <span class="d-block fw-semibold" style="color:#374151;">
                                                            {{ $product ? $product->name : 'Unknown Product' }}
                                                        </span>
                                                        <span class="d-block small text-muted">
                                                            {{ $variant ? $variant->variant_name : 'Unknown Variant' }}
                                                            @if($product && $product->category) · {{ $product->category->name }} @endif
                                                        </span>
                                                    </td>
                                                    <td>{{ $variantRow['totalQty'] }} {{ $variantRow['unit'] }}</td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" name="quoted_unit_price[{{ $variantId }}]"
                                                            class="form-control bg-light line-unit-price"
                                                            value="{{ $unitPrice !== '' && $unitPrice !== null ? $unitPrice : '' }}"
                                                            data-qty="{{ $variantRow['totalQty'] }}" placeholder="0.00">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" max="100" name="gst_percentage[{{ $variantId }}]"
                                                            class="form-control bg-light line-gst"
                                                            value="{{ $gst !== '' ? $gst : 0 }}" placeholder="0">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" name="delivery_charges[{{ $variantId }}]"
                                                            class="form-control bg-light line-delivery"
                                                            value="{{ $delivery !== '' ? $delivery : 0 }}" placeholder="0.00">
                                                    </td>
                                                    <td class="text-end pe-0">
                                                        <span class="line-total fw-semibold">₹0.00</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center border-top pt-3 mb-3">
                                    <span class="info-label">Vendor Quoted Grand Total (Incl. Taxes & Del)</span>
                                    <span class="fs-5 fw-bold" id="vendor_quote_total">₹0.00</span>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Expected Delivery Date <span class="text-danger">*</span>
                                            <span class="text-muted fw-normal small">(dd-mm-yyyy — required to accept)</span>
                                        </label>
                                        <input type="text" name="expected_delivery_date" id="expected_delivery_date"
                                            class="form-control bg-light datepicker"
                                            value="{{ old('expected_delivery_date', $assignment->expected_delivery_date ? $assignment->expected_delivery_date->format('d-m-Y') : '') }}"
                                            placeholder="dd-mm-yyyy" autocomplete="off">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="vendor_notes" class="form-control bg-light" rows="2"
                                        placeholder="Optional - pricing details, availability, or reason for rejection">{{ old('vendor_notes', $assignment->vendor_notes) }}</textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="reject" class="btn btn-outline-danger fw-medium px-4">
                                        <i class="bi bi-x-circle me-1"></i> Reject
                                    </button>
                                    <button type="submit" name="action" value="accept" class="btn btn-success fw-medium px-4 border-0">
                                        <i class="bi bi-check-circle me-1"></i> Accept
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        $(function () {
            $('.datepicker').datepicker({
                dateFormat: 'dd-mm-yy',
                changeMonth: true,
                changeYear: true
            });
        });

        (function () {
            function formatMoney(value) {
                return '₹' + (Math.round(value * 100) / 100).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function recalculate() {
                let total = 0;

                document.querySelectorAll('tbody tr').forEach(function (row) {
                    let priceInput = row.querySelector('.line-unit-price');
                    let gstInput = row.querySelector('.line-gst');
                    let delInput = row.querySelector('.line-delivery');

                    if (priceInput) {
                        let qty = parseFloat(priceInput.dataset.qty) || 0;
                        let price = parseFloat(priceInput.value);
                        let gst = parseFloat(gstInput ? gstInput.value : 0) || 0;
                        let del = parseFloat(delInput ? delInput.value : 0) || 0;

                        let lineTotal = 0;
                        if (!isNaN(price) && price > 0) {
                            let base = price * qty;
                            let gstAmount = base * (gst / 100);
                            lineTotal = base + gstAmount + del;
                        }

                        total += lineTotal;

                        let target = row.querySelector('.line-total');
                        if (target) {
                            target.textContent = formatMoney(lineTotal);
                        }
                    }
                });

                let totalEl = document.getElementById('vendor_quote_total');
                if (totalEl) {
                    totalEl.textContent = formatMoney(total);
                }
            }

            document.querySelectorAll('.line-unit-price, .line-gst, .line-delivery').forEach(function (input) {
                input.addEventListener('input', recalculate);
            });

            recalculate();
        })();
    </script>
</body>

</html>