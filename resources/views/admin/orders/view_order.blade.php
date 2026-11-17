<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Order Detail: {{ $order->order_number }}</title>
    @include('admin.includes.header_links')
    <style>
        body { background-color: #f8f9fa; }
        .card-custom { 
            border-radius: 12px; 
            border: 1px solid #ebedf0; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); 
            background-color: #ffffff;
        }
        .info-list-item { 
            padding: 1rem 0; 
            border-bottom: 1px dashed #e9ecef; 
        }
        .info-list-item:last-child { border-bottom: none; }
        .icon-circle { 
            width: 48px; 
            height: 48px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.25rem; 
        }
        .badge-soft {
            font-weight: 500;
            padding: 0.5em 0.8em;
            border-radius: 6px;
        }
        .badge-soft-primary { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-soft-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-soft-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-soft-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-soft-secondary { background-color: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    </style>
</head>

<body class="bg-light">
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content py-4 px-3 px-md-4">
        <!-- Header -->
        <div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h2 class="page-title fw-bold mb-1 text-dark d-flex align-items-center">
                    Order {{ $order->order_number }}
                    @php
                        $badgeClass = 'badge-soft-secondary';
                        if (in_array($order->status, [2, 7, 4])) $badgeClass = 'badge-soft-success';
                        if ($order->status == 3) $badgeClass = 'badge-soft-primary';
                        if (in_array($order->status, [5, 6])) $badgeClass = 'badge-soft-danger';
                        if ($order->status == 1) $badgeClass = 'badge-soft-warning';
                    @endphp
                    <span class="badge {{ $badgeClass }} ms-3 fs-6 rounded-pill fw-normal">{{ $statusMap[$order->status] ?? 'Unknown' }}</span>
                </h2>
                <p class="text-muted small mb-0"><i class="bi bi-calendar3 me-1"></i> Placed on {{ \Carbon\Carbon::parse($order->created_at)->format('l, d M Y \a\t h:i A') }}</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="openStatusModal({{ $order->id }}, {{ $order->status }})" class="btn btn-dark rounded-pill px-4 fw-medium"><i class="bi bi-arrow-repeat me-1"></i> Update Status</button>
                <a href="{{ route('manage_orders') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-medium"><i class="bi bi-arrow-left me-1"></i> Back</a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Items & Billing -->
            <div class="col-lg-8">
                <!-- Order Items Card -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-cart3 text-primary me-2"></i>Ordered Items ({{ $order->items->count() }})</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive rounded border border-light">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small text-muted" style="background-color: #fcfcfd;">
                                    <tr>
                                        <th class="ps-3 py-3 border-bottom border-light">Product Details</th>
                                        <th class="py-3 border-bottom border-light">Qty</th>
                                        <th class="py-3 border-bottom border-light">Unit Price</th>
                                        <th class="text-end pe-3 py-3 border-bottom border-light">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td class="ps-3 py-3 border-bottom border-light">
                                                <div class="d-flex align-items-center">
                                                    @if($item->variant && $item->variant->image)
                                                        <img src="{{ asset('uploads/products/' . $item->variant->image) }}" alt="Product" class="rounded-3 shadow-sm me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-light rounded-3 shadow-sm me-3 d-flex align-items-center justify-content-center text-muted" style="width: 60px; height: 60px;">
                                                            <i class="bi bi-image fs-4"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark">{{ $item->product->name ?? 'Unknown Product' }}</h6>
                                                        <small class="text-muted bg-light px-2 py-1 rounded">
                                                            @if($item->variant)
                                                                {{ is_array($item->variant->attributes) ? implode(', ', $item->variant->attributes) : $item->variant->attributes }}
                                                                <span class="text-dark fw-medium ms-1">(Per {{ $item->variant->unit }})</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 fw-medium text-dark border-bottom border-light">{{ $item->qty }}</td>
                                            <td class="py-3 text-muted border-bottom border-light">₹{{ number_format($item->selling_price, 2) }}</td>
                                            <td class="text-end pe-3 py-3 fw-bold text-dark border-bottom border-light">₹{{ number_format($item->total_price, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Billing Summary -->
                    <div class="col-md-6">
                        <div class="card card-custom h-100">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt text-primary me-2"></i>Billing Summary</h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="info-list-item d-flex justify-content-between align-items-center pt-0">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-medium text-dark">₹{{ number_format($order->subtotal, 2) }}</span>
                                </div>
                                <div class="info-list-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Tax Amount</span>
                                    <span class="fw-medium text-dark">₹{{ number_format($order->tax_amount, 2) }}</span>
                                </div>
                                <div class="info-list-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Delivery Charges</span>
                                    <span class="fw-medium text-dark">₹{{ number_format($order->delivery_charges, 2) }}</span>
                                </div>
                                <div class="info-list-item d-flex justify-content-between align-items-center text-success">
                                    <span>Discount/Coupon</span>
                                    <span class="fw-bold">- ₹{{ number_format($order->discount_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top">
                                    <span class="fw-bold text-dark fs-5">Grand Total</span>
                                    <span class="fw-bolder text-primary fs-4">₹{{ number_format($order->grand_total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Setup -->
                    <div class="col-md-6">
                        <div class="card card-custom h-100">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-credit-card text-primary me-2"></i>Payment Details</h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="info-list-item d-flex justify-content-between align-items-center pt-0">
                                    <span class="text-muted">Payment Type</span>
                                    <span class="fw-bold text-dark">{{ $order->payment_type }}</span>
                                </div>
                                <div class="info-list-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Payment Status</span>
                                    <span class="badge bg-{{ $order->payment_status == 'Pending' ? 'warning' : 'success' }} bg-opacity-10 text-{{ $order->payment_status == 'Pending' ? 'warning' : 'success' }} rounded-pill px-3">{{ $order->payment_status }}</span>
                                </div>
                                <div class="info-list-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Initial Payment</span>
                                    <span class="fw-bold text-dark">₹{{ number_format($order->initial_payment, 2) }}</span>
                                </div>
                                <div class="info-list-item d-flex justify-content-between align-items-center border-0 pb-0">
                                    <span class="text-muted">Pending Balance</span>
                                    <span class="fw-bold text-danger">₹{{ number_format($order->pending_payment, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Customer & Assignments -->
            <div class="col-lg-4">
                <!-- Rejection Alert (if applicable) -->
                @if($order->status == 6 && $order->cancel_reason)
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-start bg-white" style="border-left: 4px solid #dc2626 !important;">
                        <i class="bi bi-exclamation-octagon-fill fs-4 text-danger me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Order Rejected</h6>
                            <p class="mb-0 small text-muted">{{ $order->cancel_reason }}</p>
                        </div>
                    </div>
                @endif

                <!-- Customer Details -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Customer Info</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary me-3 fw-bold fs-4">
                                {{ strtoupper(substr($order->user->name ?? 'C', 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark fs-5">{{ $order->user->name ?? 'N/A' }}</h6>
                                <p class="mb-0 text-muted small"><i class="bi bi-telephone-fill me-1"></i>{{ $order->user->mobile ?? 'No Contact' }}</p>
                            </div>
                        </div>
                        
                        <div class="bg-light rounded-3 p-3 border border-light">
                            <h6 class="fw-bold text-dark mb-2 fs-6"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Delivery Address</h6>
                            @if($order->address)
                                <p class="mb-1 fw-medium text-dark">{{ $order->address->site_name ?? $order->address->address_title }}</p>
                                <p class="mb-1 text-muted small lh-lg">{{ $order->address->delivery_address }}</p>
                                <p class="mb-0 text-muted small fw-bold">PIN: {{ $order->address->delivery_pincode }}</p>
                            @else
                                <p class="text-muted small mb-0 fst-italic">No delivery address provided.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Assignments -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Task Assignments</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Site Engineer -->
                        <div class="p-4 border-bottom border-light">
                            <h6 class="fw-bold mb-3 text-muted text-uppercase small" style="letter-spacing: 0.5px;">Assigned Engineer</h6>
                            @if($assignedEngineer)
                                <div class="d-flex align-items-center">
                                    <img src="{{ $assignedEngineer->profile_photo ? asset('uploads/engineer_profiles/' . $assignedEngineer->profile_photo) : 'https://via.placeholder.com/60' }}" class="rounded-circle shadow-sm me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <p class="mb-1 fw-bold text-dark">{{ $assignedEngineer->full_name }}</p>
                                        <p class="mb-0 text-muted small"><i class="bi bi-telephone me-1"></i>{{ $assignedEngineer->mobile_number }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center text-muted bg-light p-3 rounded-3">
                                    <i class="bi bi-person-x fs-4 me-3"></i>
                                    <span class="small fw-medium">No engineer assigned yet.</span>
                                </div>
                            @endif
                        </div>

                        <!-- Vendor -->
                        <div class="p-4 border-bottom border-light">
                            <h6 class="fw-bold mb-3 text-muted text-uppercase small" style="letter-spacing: 0.5px;">Assigned Vendor</h6>
                            @if($assignedVendor)
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-success bg-opacity-10 text-success me-3 shadow-sm border border-success border-opacity-25">
                                        <i class="bi bi-shop fs-4"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1 fw-bold text-dark">{{ $assignedVendor->business_name }}</p>
                                        <p class="mb-0 text-muted small"><i class="bi bi-person me-1"></i>{{ $assignedVendor->contact_person }} ({{ $assignedVendor->business_mobile }})</p>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center text-muted bg-light p-3 rounded-3">
                                    <i class="bi bi-shop-window fs-4 me-3"></i>
                                    <span class="small fw-medium">No vendor assigned yet.</span>
                                </div>
                            @endif
                        </div>

                        <!-- Delivery Partner -->
                        <div class="p-4">
                            <h6 class="fw-bold mb-3 text-muted text-uppercase small" style="letter-spacing: 0.5px;">Delivery Partner</h6>
                            @if($order->deliveryPartner)
                                <div class="d-flex align-items-center">
                                    <img src="{{ $order->deliveryPartner->profile_photo ? asset('uploads/delivery_partners/' . $order->deliveryPartner->profile_photo) : 'https://via.placeholder.com/60' }}" class="rounded-circle shadow-sm me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <p class="mb-1 fw-bold text-dark">{{ $order->deliveryPartner->driver_name }} <span class="badge bg-light text-dark border ms-1">{{ $order->deliveryPartner->driver_id }}</span></p>
                                        <p class="mb-0 text-muted small"><i class="bi bi-telephone me-1"></i>{{ $order->deliveryPartner->mobile_number }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center text-muted bg-light p-3 rounded-3">
                                    <i class="bi bi-truck fs-4 me-3"></i>
                                    <span class="small fw-medium">No delivery partner assigned yet.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Status Modal with Delivery Partner Selection -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <form id="statusForm" action="{{ route('update_order_status') }}" method="POST" class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Update Order Status</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <input type="hidden" name="order_id" id="modal_status_order_id">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Select New Status</label>
                        <select name="status" id="modal_status_select" class="form-select bg-light" required onchange="toggleDynamicFields()">
                            @foreach($statusMap as $key => $val)
                                <option value="{{ $key }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Delivery Partner Selection -->
                    <div class="mb-3" id="delivery_partner_container" style="display:none;">
                        <label class="form-label small fw-bold" style="color:#2563eb;">Assign Delivery Partner <span class="text-primary">*</span></label>
                        <select name="delivery_partner_id" id="delivery_partner_select" class="form-select bg-light">
                            <option value="">-- Select Partner --</option>
                            @foreach($deliveryPartners as $partner)
                                <option value="{{ $partner->id }}" {{ $order->delivery_partner_id == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->driver_name }} ({{ $partner->driver_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rejection Reason -->
                    <div class="mb-2" id="reject_reason_container" style="display:none;">
                        <label class="form-label text-danger small fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" id="reject_reason_input" class="form-control bg-light" rows="3" placeholder="Explain why the order is rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-medium px-4 rounded-pill">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function openStatusModal(orderId, currentStatus) {
            $('#modal_status_order_id').val(orderId);
            $('#modal_status_select').val(currentStatus);
            toggleDynamicFields();
            $('#statusModal').modal('show');
        }

        function toggleDynamicFields() {
            var statusVal = $('#modal_status_select').val();
            
            // Rejection reason logic (Status 6)
            if (statusVal == 6) { 
                $('#reject_reason_container').show();
                $('#reject_reason_input').prop('required', true);
            } else {
                $('#reject_reason_container').hide();
                $('#reject_reason_input').prop('required', false);
            }

            // Delivery Partner logic (Status 4 - Out for delivery)
            if (statusVal == 4) {
                $('#delivery_partner_container').show();
                $('#delivery_partner_select').prop('required', true);
            } else {
                $('#delivery_partner_container').hide();
                $('#delivery_partner_select').prop('required', false);
            }
        }
    </script>
</body>

</html>