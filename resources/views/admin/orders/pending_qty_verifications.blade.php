<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pending Quantity Verifications</title>
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

        .table-custom tbody tr:hover {
            background-color: #f9fafb;
        }

        .badge-soft-warning {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
            font-weight: 500;
            padding: 0.5em 0.8em;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .qty-discrepancy {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }

        .financial-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body class="bg-light">
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content py-4 px-3 px-md-4">
        <div class="page-head mb-4">
            <h2 class="page-title fw-bold mb-0 text-dark" style="font-size: 1.5rem;">Quantity Verifications</h2>
            <p class="text-muted small mb-0 mt-1">Review and approve quantity mismatches reported by customers at
                delivery.</p>
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

        <div class="card card-custom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Order No.</th>
                                <th>Customer</th>
                                <th>Discrepancy Details</th>
                                <th>Financial Impact</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    // Calculate 'After' amounts dynamically for display
                                    $newSubtotal = 0;
                                    $deliveredMap = [];
                                    foreach ($order->pending_verifications as $v) {
                                        $deliveredMap[$v->item_id] = $v->delivered_qty;
                                    }
                                    foreach ($order->items as $item) {
                                        $qty = isset($deliveredMap[$item->id]) ? $deliveredMap[$item->id] : $item->qty;
                                        $newSubtotal += ($item->selling_price * $qty);
                                    }

                                    $newGrandTotal = max(0, ($newSubtotal + $order->tax_amount + $order->delivery_charges) - $order->discount_amount);
                                    $newPendingPayment = max(0, $newGrandTotal - $order->initial_payment);
                                @endphp

                                <tr>
                                    <td class="ps-4 fw-bold text-dark">
                                        {{ $order->order_number }}<br>
                                        <span class="badge badge-soft-warning mt-1">Pending Approval</span>
                                    </td>
                                    <td>
                                        <span class="d-block fw-semibold text-dark">{{ $order->user->name ?? 'N/A' }}</span>
                                        <span class="small text-muted">{{ $order->user->mobile ?? '' }}</span>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0 small">
                                            @foreach($order->pending_verifications as $v)
                                                <li class="mb-2 pb-2 border-bottom border-light">
                                                    <strong class="d-block text-dark">{{ $v->product_name }}</strong>
                                                    Ordered: <span class="text-muted">{{ $v->ordered_qty }}
                                                        {{ $v->unit }}</span>
                                                    &rarr; Delivered: <span class="qty-discrepancy">{{ $v->delivered_qty }}
                                                        {{ $v->unit }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>
                                        <div class="financial-box">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Original Order Total:</span>
                                                <span
                                                    class="text-dark text-decoration-line-through">₹{{ number_format($order->grand_total, 2) }}</span>
                                            </div>
                                            <div
                                                class="d-flex justify-content-between mb-2 pb-2 border-bottom border-light">
                                                <span class="fw-bold text-dark">New Order Total:</span>
                                                <span
                                                    class="fw-bold text-success">₹{{ number_format($newGrandTotal, 2) }}</span>
                                            </div>

                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Original Pending:</span>
                                                <span
                                                    class="text-dark text-decoration-line-through">₹{{ number_format($order->pending_payment, 2) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold text-dark">New Pending:</span>
                                                <span
                                                    class="fw-bold text-danger">₹{{ number_format($newPendingPayment, 2) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex flex-column gap-2 align-items-end">
                                            <!-- View Full Details -->
                                            <a href="{{ route('view_order', $order->id) }}"
                                                class="btn btn-sm btn-info px-3 fw-medium text-white w-100"
                                                style="max-width: 140px;">
                                                <i class="bi bi-eye me-1"></i> View Order
                                            </a>

                                            <!-- Approve Form -->
                                            <form action="{{ route('approve_qty_verification') }}" method="POST"
                                                class="w-100" style="max-width: 140px;">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                <button type="submit" class="btn btn-sm btn-success px-3 fw-medium w-100"
                                                    onclick="return confirm('Are you sure you want to approve this updated quantity? It will recalculate the user\'s bill.')">Accept
                                                    Changes</button>
                                            </form>

                                            <!-- Reject Trigger -->
                                            <button type="button" class="btn btn-sm btn-danger px-3 fw-medium w-100"
                                                style="max-width: 140px;" onclick="openRejectModal({{ $order->id }})">Reject
                                                Claim</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-check-circle fs-2 d-block mb-2 text-light"></i>
                                        <span class="fw-medium">No pending quantity verifications.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 pt-4 pb-3 px-4">
                {{ $orders->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <!-- Reject Verification Modal -->
    <div class="modal fade" id="rejectQtyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('reject_qty_verification') }}" method="POST"
                class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Reject Quantity Verification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <input type="hidden" name="order_id" id="modal_reject_order_id">
                    <div class="mb-2">
                        <label class="form-label text-danger">Reason for Rejection (Optional)</label>
                        <textarea name="reject_reason" class="form-control bg-light" rows="3"
                            placeholder="Enter reason for rejecting the customer's reported quantity..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-medium px-4">Reject Verification</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script>
        function openRejectModal(orderId) {
            document.getElementById('modal_reject_order_id').value = orderId;
            var modal = new bootstrap.Modal(document.getElementById('rejectQtyModal'));
            modal.show();
        }
    </script>
</body>

</html>