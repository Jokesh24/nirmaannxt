<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bulk Quote #ORD{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</title>
    @include('admin.includes.header_links')

    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-custom {
            border-radius: 10px;
            border: 1px solid #ebedf0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            background-color: #ffffff;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8792a2;
            font-weight: 600;
        }

        .info-value {
            font-size: 0.95rem;
            color: #374151;
            font-weight: 500;
        }

        .table-custom th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #8792a2;
            border-bottom: 1px solid #ebedf0;
            background-color: #fcfcfd;
            padding: 0.85rem;
        }

        .table-custom td {
            padding: 0.85rem;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }

        .badge-soft {
            font-weight: 500;
            padding: 0.5em 0.8em;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .badge-soft-primary {
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .badge-soft-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .badge-soft-warning {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .badge-soft-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .badge-soft-secondary {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .btn-elegant {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            color: #4b5563;
            border-radius: 6px;
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-elegant:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            color: #111827;
        }

        .vendor-pick-row {
            border: 1px solid #ebedf0;
            border-radius: 8px;
            padding: 0.6rem 0.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vendor-pick-row .vendor-dist {
            font-size: 0.78rem;
            color: #6b7280;
        }

        .vendor-pick-row.already-sent {
            background-color: #fffbeb;
            border-color: #fde68a;
        }
    </style>
</head>

<body class="bg-light">
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content py-4 px-3 px-md-4">
        <div class="page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title fw-bold mb-0 text-dark" style="font-size: 1.5rem;">
                    Bulk Quote #ORD{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}
                </h2>
                <p class="text-muted small mb-0 mt-1">
                    Requested on {{ \Carbon\Carbon::parse($quote->created_at)->format('d M Y, h:i A') }}
                </p>
            </div>
            <a href="{{ route('manage_bulk_quotes') }}" class="btn btn-elegant">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>

        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4"
                style="background-color: #f0fdf4; color: #16a34a;">
                <i class="bi bi-check-circle-fill me-2"></i>{{ Session::get('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4"
                style="background-color: #fef2f2; color: #dc2626;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Quote Summary -->
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="info-label mb-1">Customer</div>
                        <div class="info-value">{{ $quote->full_name ?? ($quote->user->name ?? 'N/A') }}</div>
                        <div class="small text-muted">
                            {{ $quote->phone_number ?? ($quote->user->mobile ?? 'No contact') }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label mb-1">Delivery Location</div>
                        <div class="info-value">{{ $quote->delivery_location }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label mb-1">Preferred Delivery</div>
                        <div class="info-value">
                            {{ \Carbon\Carbon::parse($quote->preferred_delivery_date)->format('d M, Y') }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label mb-1">Payment Preference</div>
                        <div class="info-value">{{ $quote->payment_preference }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label mb-1">Status</div>
                        @php
                            $badgeClass = 'badge-soft-secondary';
                            if ($quote->status === 'Sent To Vendors')
                                $badgeClass = 'badge-soft-primary';
                            if ($quote->status === 'Quoted')
                                $badgeClass = 'badge-soft-success';
                            if ($quote->status === 'Quote Locked')
                                $badgeClass = 'badge-soft-primary';
                            if ($quote->status === 'Confirmed')
                                $badgeClass = 'badge-soft-success';
                            if ($quote->status === 'Completed')
                                $badgeClass = 'badge-soft-success';
                            if ($quote->status === 'Cancelled')
                                $badgeClass = 'badge-soft-danger';
                            if ($quote->status === 'Pending')
                                $badgeClass = 'badge-soft-warning';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $statusMap[$quote->status] ?? $quote->status }}</span>
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

        <!-- Items -->
        <div class="card card-custom mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Brand</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quote->items as $item)
                                @php
                                    $variant = $item->variant;
                                    $product = $variant ? $variant->product : null;
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="d-block fw-semibold" style="color:#374151;">
                                            {{ $product ? $product->name : 'Unknown Product' }}
                                        </span>
                                        <span
                                            class="small text-muted">{{ $variant ? $variant->variant_name : 'Unknown Variant' }}</span>
                                    </td>
                                    <td>{{ ($product && $product->brand) ? $product->brand->brand_name : '-' }}</td>
                                    <td>{{ (int) $item->qty }}</td>
                                    <td>{{ $variant ? $variant->unit : 'Units' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No items on this quote.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assigned Vendors -->
        <div class="card card-custom">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Vendors</h6>
                    <button class="btn btn-elegant" onclick="openVendorAssignModal(
                        {{ $quote->id }},
                        {{ $quote->latitude ?? 'null' }},
                        {{ $quote->longitude ?? 'null' }},
                        {{ json_encode($assignedVendorIds) }},
                        {{ json_encode($eligibleVendorIds) }}
                    )">
                        <i class="bi bi-send me-1"></i> Send to Vendors
                    </button>
                </div>

                @if($quote->assignedVendors->isEmpty())
                    <p class="text-muted small mb-0">This quote hasn't been sent to any vendor yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Status</th>
                                    <th>Quoted Amount</th>
                                    <th>Expected Delivery</th>
                                    <th>Sent On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quote->assignedVendors as $av)
                                    <tr>
                                        <td>
                                            <span class="d-block fw-semibold" style="color:#374151;">
                                                {{ $av->vendor->business_name ?? 'Unknown Vendor' }}
                                            </span>
                                            <span class="small text-muted">{{ $av->vendor->contact_person ?? '' }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $vBadge = 'badge-soft-warning';
                                                if ($av->status === 'Accepted')
                                                    $vBadge = 'badge-soft-success';
                                                if ($av->status === 'Declined')
                                                    $vBadge = 'badge-soft-danger';
                                                if ($av->status === 'Quoted')
                                                    $vBadge = 'badge-soft-primary';
                                            @endphp
                                            <span class="badge {{ $vBadge }}">{{ $av->status }}</span>
                                        </td>
                                        <td>{{ $av->quoted_amount ? '₹' . number_format($av->quoted_amount, 2) : '-' }}</td>
                                        <td>{{ $av->expected_delivery_date ? $av->expected_delivery_date->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $av->sent_at ? \Carbon\Carbon::parse($av->sent_at)->format('d M Y, h:i A') : '-' }}
                                        </td>
                                        <td>
                                            <a href="{{ route('bulk_quote_vendor_view', ['quoteId' => $quote->id, 'vendorId' => $av->vendor_id]) }}"
                                                class="btn btn-elegant btn-sm">
                                                <i class="bi bi-eye me-1"></i> View / Respond
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('admin.bulk_quotes.assign_vendors_modal')

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>