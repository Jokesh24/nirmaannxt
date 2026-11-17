<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Orders</title>
    @include('admin.includes.header_links')
    <!-- jQuery UI CSS for Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">

    <style>
        /* Soft, Professional UI Styling */
        body {
            background-color: #f8f9fa;
        }

        .card-custom {
            border-radius: 10px;
            border: 1px solid #ebedf0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            background-color: #ffffff;
        }

        .table-custom th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #8792a2;
            border-bottom: 1px solid #ebedf0;
            background-color: #fcfcfd;
            padding: 1rem;
        }

        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }

        .table-custom tbody tr {
            transition: background-color 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: #f9fafb;
        }

        /* Soft Badges */
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

        /* Elegant Action Buttons */
        .btn-elegant {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            color: #4b5563;
            border-radius: 6px;
            font-size: 0.8rem;
            padding: 0.4rem 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.01);
        }

        .btn-elegant:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            color: #111827;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .btn-elegant i {
            color: #9ca3af;
            transition: color 0.2s;
        }

        .btn-elegant:hover i.icon-blue {
            color: #3b82f6;
        }

        .btn-elegant:hover i.icon-green {
            color: #10b981;
        }

        .btn-elegant:hover i.icon-dark {
            color: #374151;
        }

        .form-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Custom Datepicker Input Icon */
        .date-input-wrapper {
            position: relative;
        }

        .date-input-wrapper i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .datepicker {
            padding-right: 35px;
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-light">
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content py-4 px-3 px-md-4">
        <div class="page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title fw-bold mb-0 text-dark" style="font-size: 1.5rem;">Manage Orders</h2>
                <p class="text-muted small mb-0 mt-1">Track, manage, and assign customer orders effortlessly.</p>
            </div>
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
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4"
                style="background-color: #fef2f2; color: #dc2626;">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filter Section -->
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <form action="{{ route('manage_orders') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Search Order</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i
                                        class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light"
                                    value="{{ request('search') }}" placeholder="ID, Customer Name...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <div class="date-input-wrapper">
                                <input type="text" name="from_date" class="form-control datepicker bg-light"
                                    value="{{ request('from_date') }}" placeholder="YYYY-MM-DD" readonly>
                                <i class="bi bi-calendar3"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <div class="date-input-wrapper">
                                <input type="text" name="to_date" class="form-control datepicker bg-light"
                                    value="{{ request('to_date') }}" placeholder="YYYY-MM-DD" readonly>
                                <i class="bi bi-calendar3"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select bg-light">
                                <option value="">All Statuses</option>
                                @foreach($statusMap as $key => $val)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $val }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-secondary px-4 fw-medium border-0"
                                    style="background-color: #64748b;"><i class="bi bi-funnel me-1"></i> Filter</button>
                                <a href="{{ route('manage_orders') }}"
                                    class="btn btn-light border px-3 text-dark fw-medium"><i
                                        class="bi bi-arrow-clockwise me-1"></i> Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="card card-custom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Sl.No</th>
                                <th>Order Info</th>
                                <th>Customer Details</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Assignments</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $key => $order)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ $orders->firstItem() + $key }}</td>
                                    <td>
                                        <span class="d-block fw-bold text-dark">{{ $order->order_number }}</span>
                                        <span class="small text-muted"><i
                                                class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, h:i A') }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width: 32px; height: 32px; background-color: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.85rem;">
                                                {{ strtoupper(substr($order->user->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="d-block fw-semibold"
                                                    style="color: #374151;">{{ $order->user->name ?? 'N/A' }}</span>
                                                <span
                                                    class="small text-muted">{{ $order->user->mobile ?? 'No contact' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold"
                                            style="color: #374151;">₹{{ number_format($order->grand_total, 2) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = 'badge-soft-secondary';
                                            if (in_array($order->status, [2, 7, 4]))
                                                $badgeClass = 'badge-soft-success'; // Confirmed, Shipped, Out for delivery
                                            if ($order->status == 3)
                                                $badgeClass = 'badge-soft-primary'; // Completed
                                            if (in_array($order->status, [5, 6]))
                                                $badgeClass = 'badge-soft-danger'; // Cancelled, Rejected
                                            if ($order->status == 1)
                                                $badgeClass = 'badge-soft-warning'; // In-Review
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $statusMap[$order->status] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-2" style="max-width: 180px;">
                                            <button class="btn btn-elegant w-100 text-start"
                                                onclick="openEngineerModal({{ $order->id }}, '{{ $order->engineer_id }}', {{ $order->address->latitude ?? 'null' }}, {{ $order->address->longitude ?? 'null' }})">
                                                <i
                                                    class="bi bi-person-workspace me-2 icon-blue"></i>{{ $order->engineer_id ? 'Update Engineer' : 'Assign Engineer' }}
                                            </button>
                                            <button class="btn btn-elegant w-100 text-start"
                                                onclick="openVendorModal({{ $order->id }}, '{{ $order->vendor_id }}', {{ $order->address->latitude ?? 'null' }}, {{ $order->address->longitude ?? 'null' }})">
                                                <i
                                                    class="bi bi-shop me-2 icon-green"></i>{{ $order->vendor_id ? 'Update Vendor' : 'Assign Vendor' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex flex-column gap-2 align-items-end">
                                            <a href="{{ route('view_order', $order->id) }}"
                                                class="btn btn-elegant w-100 text-start" style="max-width: 140px;">
                                                <i class="bi bi-eye me-2 icon-dark"></i> View Details
                                            </a>
                                            <button class="btn btn-elegant w-100 text-start" style="max-width: 140px;"
                                                onclick="openStatusModal({{ $order->id }}, {{ $order->status }})">
                                                <i class="bi bi-arrow-repeat me-2 icon-dark"></i> Update Status
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                                        <span class="fw-medium">No orders found matching your criteria.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 pt-4 pb-3 px-4" style="border-radius: 0 0 10px 10px;">
                {{ $orders->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Status Modal with Delivery Partner Selection -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <form id="statusForm" action="{{ route('update_order_status') }}" method="POST"
                class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Update Order Status</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <input type="hidden" name="order_id" id="modal_status_order_id">
                    <div class="mb-3">
                        <label class="form-label">Select New Status</label>
                        <select name="status" id="modal_status_select" class="form-select bg-light" required
                            onchange="toggleDynamicFields()">
                            @foreach($statusMap as $key => $val)
                                <option value="{{ $key }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Delivery Partner Selection (Hidden by default) -->
                    <div class="mb-3" id="delivery_partner_container" style="display:none;">
                        <label class="form-label" style="color:#2563eb;">Assign Delivery Partner *</label>
                        <select name="delivery_partner_id" id="delivery_partner_select" class="form-select bg-light">
                            <option value="">-- Select Partner --</option>
                            @foreach($deliveryPartners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->driver_name }} ({{ $partner->driver_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rejection Reason (Hidden by default) -->
                    <div class="mb-2" id="reject_reason_container" style="display:none;">
                        <label class="form-label text-danger">Rejection Reason *</label>
                        <textarea name="reject_reason" id="reject_reason_input" class="form-control bg-light" rows="3"
                            placeholder="Explain why..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-medium px-4">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Engineer Assignment Modal -->
    <div class="modal fade" id="engineerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('assign_order_engineer') }}" method="POST"
                class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Assign Site Engineer</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <input type="hidden" name="order_id" id="modal_engineer_order_id">
                    <label class="form-label">Select Engineer (Distance from site)</label>
                    <select name="engineer_id" id="modal_engineer_select" class="form-select bg-light" required>
                        <option value="">-- Choose Engineer --</option>
                        @foreach($engineers as $engineer)
                            <option value="{{ $engineer->id }}" data-lat="{{ $engineer->latitude ?? '' }}"
                                data-lng="{{ $engineer->longitude ?? '' }}"
                                data-name="{{ $engineer->full_name }} ({{ $engineer->engineer_id }})">
                                {{ $engineer->full_name }} ({{ $engineer->engineer_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-medium px-4"
                        style="background-color: #3b82f6; border: none;">Assign Engineer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Vendor Assignment Modal -->
    <div class="modal fade" id="vendorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('assign_order_vendor') }}" method="POST"
                class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Assign Vendor</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <input type="hidden" name="order_id" id="modal_vendor_order_id">
                    <label class="form-label">Select Vendor (Distance from site)</label>
                    <select name="vendor_id" id="modal_vendor_select" class="form-select bg-light" required>
                        <option value="">-- Choose Vendor --</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" data-lat="{{ $vendor->latitude ?? '' }}"
                                data-lng="{{ $vendor->longitude ?? '' }}"
                                data-name="{{ $vendor->business_name }} ({{ $vendor->contact_person }})">
                                {{ $vendor->business_name }} ({{$vendor->contact_person }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-medium px-4"
                        style="background-color: #10b981; border: none;">Assign Vendor</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize jQuery UI Datepicker
            $(".datepicker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
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

        function openEngineerModal(orderId, currentEngineerId, orderLat, orderLng) {
            $('#modal_engineer_order_id').val(orderId);
            $('#modal_engineer_select').val(currentEngineerId);

            orderLat = parseFloat(orderLat);
            orderLng = parseFloat(orderLng);

            $('#modal_engineer_select option').each(function () {
                let opt = $(this);
                if (!opt.val()) return;

                let targetLat = parseFloat(opt.data('lat'));
                let targetLng = parseFloat(opt.data('lng'));
                let baseName = opt.data('name');

                if (orderLat && orderLng && targetLat && targetLng) {
                    let distance = calculateDistance(orderLat, orderLng, targetLat, targetLng);
                    opt.text(`${baseName} — ${distance} km away`);
                } else {
                    opt.text(`${baseName} — Distance unknown`);
                }
            });

            $('#engineerModal').modal('show');
        }

        function openVendorModal(orderId, currentVendorId, orderLat, orderLng) {
            $('#modal_vendor_order_id').val(orderId);
            $('#modal_vendor_select').val(currentVendorId);

            orderLat = parseFloat(orderLat);
            orderLng = parseFloat(orderLng);

            $('#modal_vendor_select option').each(function () {
                let opt = $(this);
                if (!opt.val()) return;

                let targetLat = parseFloat(opt.data('lat'));
                let targetLng = parseFloat(opt.data('lng'));
                let baseName = opt.data('name');

                if (orderLat && orderLng && targetLat && targetLng) {
                    let distance = calculateDistance(orderLat, orderLng, targetLat, targetLng);
                    opt.text(`${baseName} — ${distance} km away`);
                } else {
                    opt.text(`${baseName} — Distance unknown`);
                }
            });

            $('#vendorModal').modal('show');
        }
    </script>
</body>

</html>