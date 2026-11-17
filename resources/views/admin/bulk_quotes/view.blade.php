<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Bulk Quote: #BQR{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</title>
    @include('admin.includes.header_links')
    <style>
        body { background-color: #f8f9fa; }
        .card-custom { 
            border-radius: 12px; border: 1px solid #ebedf0; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); background-color: #ffffff;
        }
        .info-list-item { padding: 1rem 0; border-bottom: 1px dashed #e9ecef; }
        .info-list-item:last-child { border-bottom: none; }
        .icon-circle { 
            width: 48px; height: 48px; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; font-size: 1.25rem; 
        }
        
        .badge-soft { font-weight: 500; padding: 0.5em 0.8em; border-radius: 6px; }
        .badge-soft-primary { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-soft-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-soft-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-soft-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-soft-secondary { background-color: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
        .badge-soft-info { background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
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
                    Quote #BQR{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}
                    @php
                        $badgeClass = 'badge-soft-secondary';
                        if (in_array($quote->status, ['Accepted', 'Completed', 'Shipped'])) $badgeClass = 'badge-soft-success';
                        if (in_array($quote->status, ['Rejected', 'Canceled'])) $badgeClass = 'badge-soft-danger';
                        if ($quote->status == 'Pending') $badgeClass = 'badge-soft-warning';
                        if ($quote->status == 'Vendor Assigned') $badgeClass = 'badge-soft-primary';
                        if ($quote->status == 'Out For Delivery') $badgeClass = 'badge-soft-info';
                    @endphp
                    <span class="badge {{ $badgeClass }} ms-3 fs-6 rounded-pill fw-normal">{{ $quote->status }}</span>
                </h2>
                <p class="text-muted small mb-0"><i class="bi bi-calendar3 me-1"></i> Requested on {{ \Carbon\Carbon::parse($quote->created_at)->format('l, d M Y \a\t h:i A') }}</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="openStatusModal('{{ $quote->status }}')" class="btn btn-dark rounded-pill px-4 fw-medium"><i class="bi bi-arrow-repeat me-1"></i> Update Status</button>
                <a href="{{ route('manage_bulk_quotes') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-medium"><i class="bi bi-arrow-left me-1"></i> Back</a>
            </div>
        </div>

        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" style="background-color: #f0fdf4; color: #16a34a;">
                <i class="bi bi-check-circle-fill me-2"></i>{{ Session::get('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(Session::has('error') || $errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" style="background-color: #fef2f2; color: #dc2626;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ Session::get('error') ?? 'Please correct the errors below.' }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Left Column: Items & Assignment -->
            <div class="col-lg-8">
                <!-- Quote Items Card -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-boxes text-primary me-2"></i>Requested Bulk Items ({{ $quote->items->count() }})</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive rounded border border-light">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small text-muted" style="background-color: #fcfcfd;">
                                    <tr>
                                        <th class="ps-3 py-3 border-bottom border-light">Product Details</th>
                                        <th class="py-3 border-bottom border-light">Category</th>
                                        <th class="text-end pe-3 py-3 border-bottom border-light">Requested Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($quote->items as $item)
                                        <tr>
                                            <td class="ps-3 py-3 border-bottom border-light">
                                                <div class="d-flex align-items-center">
                                                    @if($item->variant && $item->variant->image)
                                                        <img src="{{ asset('uploads/products/' . $item->variant->image) }}" class="rounded-3 shadow-sm me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-light rounded-3 shadow-sm me-3 d-flex align-items-center justify-content-center text-muted" style="width: 60px; height: 60px;">
                                                            <i class="bi bi-image fs-4"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark">{{ $item->variant->product->name ?? 'Unknown Product' }}</h6>
                                                        <small class="text-muted bg-light px-2 py-1 rounded">
                                                            @if($item->variant)
                                                                {{ $item->variant->variant_name }} 
                                                                <span class="text-dark fw-medium ms-1">(Unit: {{ $item->variant->unit }})</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 text-muted border-bottom border-light">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $item->variant->product->category->name ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-end pe-3 py-3 fw-bold text-dark fs-5 border-bottom border-light">{{ $item->qty }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Assignment Section -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-shop text-success me-2"></i>Vendor Assignment (Category Based)</h5>
                        <p class="small text-muted mt-1 mb-0">Vendors are filtered based on the categories of the requested items.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        @if($assignedVendor)
                            <div class="alert alert-success d-flex align-items-center rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 mb-4">
                                <i class="bi bi-check-circle-fill fs-3 text-success me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-1 text-success">Assigned Vendor: {{ $assignedVendor->business_name }}</h6>
                                    <span class="small">Contact: {{ $assignedVendor->contact_person }} ({{ $assignedVendor->business_mobile }})</span>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('assign_bulk_quote_vendor') }}" method="POST">
                            @csrf
                            <input type="hidden" name="quote_id" value="{{ $quote->id }}">
                            <div class="row align-items-end g-3">
                                <div class="col-md-9">
                                    <label class="form-label fw-bold">Select Eligible Vendor (Distance Calculated Automatically)</label>
                                    <select name="vendor_id" id="vendor_select" class="form-select bg-light py-2" required>
                                        <option value="">-- Choose Vendor from matching categories --</option>
                                        @foreach($eligibleVendors as $vendor)
                                            <option value="{{ $vendor->id }}" 
                                                data-lat="{{ $vendor->latitude }}" 
                                                data-lng="{{ $vendor->longitude }}" 
                                                data-name="{{ $vendor->business_name }} ({{ $vendor->contact_person }})"
                                                {{ ($assignedVendor && $assignedVendor->id == $vendor->id) ? 'selected' : '' }}>
                                                {{ $vendor->business_name }} ({{$vendor->contact_person }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-success w-100 fw-medium shadow-sm"><i class="bi bi-check2-circle me-1"></i> Assign Vendor</button>
                                </div>
                            </div>
                            @if($eligibleVendors->isEmpty())
                                <p class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>No active vendors found supplying the requested product categories.</p>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Client & Info -->
            <div class="col-lg-4">
                <!-- Rejection Alert -->
                @if(in_array($quote->status, ['Rejected', 'Canceled']) && $quote->cancel_reason)
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-start bg-white" style="border-left: 4px solid #dc2626 !important;">
                        <i class="bi bi-exclamation-octagon-fill fs-4 text-danger me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Quote {{ $quote->status }}</h6>
                            <p class="mb-0 small text-muted">{{ $quote->cancel_reason }}</p>
                        </div>
                    </div>
                @endif

                <!-- Customer Details -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Client Details</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary me-3 fw-bold fs-4">
                                {{ strtoupper(substr($quote->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark fs-5">{{ $quote->full_name }}</h6>
                                <p class="mb-0 text-muted small"><i class="bi bi-telephone-fill me-1"></i>{{ $quote->phone_number }}</p>
                            </div>
                        </div>
                        
                        <div class="info-list-item d-flex justify-content-between align-items-center pt-2">
                            <span class="text-muted small">GSTIN</span>
                            <span class="fw-bold text-dark">{{ $quote->gstin ?? 'Not Provided' }}</span>
                        </div>
                        <div class="info-list-item d-flex justify-content-between align-items-center pb-0 border-0">
                            <span class="text-muted small">Payment Pref.</span>
                            <span class="fw-bold text-primary">{{ $quote->payment_preference }}</span>
                        </div>

                        <div class="bg-light rounded-3 p-3 border border-light mt-4">
                            <h6 class="fw-bold text-dark mb-2 fs-6"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Delivery Location</h6>
                            <p class="mb-2 text-muted small lh-lg">{{ $quote->delivery_location }}</p>
                            <p class="mb-0 text-dark small fw-bold"><i class="bi bi-calendar-check me-1"></i>Required By: {{ \Carbon\Carbon::parse($quote->preferred_delivery_date)->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <form action="{{ route('update_bulk_quote_status') }}" method="POST" class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Update Quote Status</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <input type="hidden" name="quote_id" value="{{ $quote->id }}">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Select New Status</label>
                        <select name="status" id="modal_status_select" class="form-select bg-light" required onchange="toggleRejectReason()">
    @foreach($statusMap as $key => $val)
        <option value="{{ $key }}">{{ $val }}</option>
    @endforeach
</select>
                    </div>

                    <!-- Rejection Reason -->
                    <div class="mb-2" id="reject_reason_container" style="display:none;">
                        <label class="form-label text-danger small fw-bold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" id="reject_reason_input" class="form-control bg-light" rows="3" placeholder="Provide reason for rejection or cancellation..."></textarea>
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
        // Set coordinates from the quote
        const quoteLat = parseFloat('{{ $quote->latitude }}') || null;
        const quoteLng = parseFloat('{{ $quote->longitude }}') || null;

        $(document).ready(function() {
            calculateVendorDistances();
        });

        // Fixed Haversine formula for distance calculation
        function calculateDistance(lat1, lon1, lat2, lon2) {
            if (!lat1 || !lon1 || !lat2 || !lon2) return null;
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return (R * c).toFixed(1);
        }

        function calculateVendorDistances() {
            $('#vendor_select option').each(function () {
                let opt = $(this);
                if (!opt.val()) return;

                let targetLat = parseFloat(opt.data('lat'));
                let targetLng = parseFloat(opt.data('lng'));
                let baseName = opt.data('name');

                if (quoteLat && quoteLng && targetLat && targetLng) {
                    let distance = calculateDistance(quoteLat, quoteLng, targetLat, targetLng);
                    opt.text(`${baseName} — ${distance} km away`);
                } else {
                    opt.text(`${baseName} — Distance unknown`);
                }
            });
        }

        function openStatusModal(currentStatus) {
            $('#modal_status_select').val(currentStatus);
            toggleRejectReason();
            $('#statusModal').modal('show');
        }

        function toggleRejectReason() {
            var statusVal = $('#modal_status_select').val();
            if (statusVal === 'Rejected' || statusVal === 'Canceled') { 
                $('#reject_reason_container').show();
                $('#reject_reason_input').prop('required', true);
            } else {
                $('#reject_reason_container').hide();
                $('#reject_reason_input').prop('required', false);
            }
        }
    </script>
</body>
</html>