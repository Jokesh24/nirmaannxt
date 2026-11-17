<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Order Rejections</title>
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

        .badge-soft-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            font-weight: 500;
            padding: 0.5em 0.8em;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .reason-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #78350f;
        }

        .item-list {
            font-size: 0.85rem;
            color: #4b5563;
        }
    </style>
</head>

<body class="bg-light">
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content py-4 px-3 px-md-4">
        <div class="page-head mb-4">
            <h2 class="page-title fw-bold mb-0 text-dark" style="font-size: 1.5rem;">Customer Rejections</h2>
            <p class="text-muted small mb-0 mt-1">Review orders that customers rejected at the time of delivery.</p>
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
                                <th>Customer Details</th>
                                <th>Order Summary</th>
                                <th>Customer Rejection Reason</th>
                                <th>Amount</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">
                                        {{ $order->order_number }}<br>
                                        <span class="badge badge-soft-danger mt-1">Customer Rejected</span>
                                    </td>
                                    <td>
                                        <span class="d-block fw-semibold text-dark">{{ $order->user->name ?? 'N/A' }}</span>
                                        <span class="small text-muted">{{ $order->user->mobile ?? '' }}</span>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0 item-list">
                                            @foreach($order->items as $item)
                                                <li>• {{ $item->qty }}x {{ $item->product->name ?? 'Product' }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>
                                        <div class="reason-box">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            {{ $order->cancel_reason ?? 'No reason provided by customer.' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold"
                                            style="color: #374151;">₹{{ number_format($order->grand_total, 2) }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex flex-column gap-2 align-items-end">
                                            <!-- View Details Button -->
                                            <a href="{{ route('view_order', $order->id) }}"
                                                class="btn btn-sm btn-info px-3 fw-medium text-white w-100"
                                                style="max-width: 140px;">
                                                <i class="bi bi-eye me-1"></i> View Details
                                            </a>

                                            <!-- Approve Form -->
                                            <form action="{{ route('approve_customer_rejection') }}" method="POST"
                                                class="w-100" style="max-width: 140px;">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                <button type="submit" class="btn btn-sm btn-success px-3 fw-medium w-100"
                                                    onclick="return confirm('Are you sure you want to approve this rejection? Status will be updated to Customer Reject Approved.')">Approve
                                                    Rejection</button>
                                            </form>

                                            <!-- Deny Trigger -->
                                            <button type="button" class="btn btn-sm btn-secondary px-3 fw-medium w-100"
                                                style="max-width: 140px;" onclick="openDenyModal({{ $order->id }})">Deny
                                                Rejection</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-shield-check fs-2 d-block mb-2 text-light"></i>
                                        <span class="fw-medium">No pending customer rejections.</span>
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

    <!-- Deny Rejection Modal -->
    <div class="modal fade" id="denyRejectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('reject_customer_rejection') }}" method="POST"
                class="modal-content border-0 shadow rounded-3">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Deny Customer Rejection</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <div class="alert alert-warning small py-2 mb-3 border-0">
                        <i class="bi bi-info-circle me-1"></i> Denying this will revert the order status back to
                        <strong>Out for Delivery</strong>.
                    </div>
                    <input type="hidden" name="order_id" id="modal_deny_order_id">
                    <div class="mb-2">
                        <label class="form-label text-dark">Reason for Denying (Optional)</label>
                        <textarea name="reject_reason" class="form-control bg-light" rows="3"
                            placeholder="Let the customer know why their rejection was denied..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-medium px-4">Confirm Deny</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script>
        function openDenyModal(orderId) {
            document.getElementById('modal_deny_order_id').value = orderId;
            var modal = new bootstrap.Modal(document.getElementById('denyRejectionModal'));
            modal.show();
        }
    </script>
</body>

</html>