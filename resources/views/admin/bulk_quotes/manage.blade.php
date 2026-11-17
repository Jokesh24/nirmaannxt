<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Bulk Quotations</title>
    @include('admin.includes.header_links')
    <!-- jQuery UI CSS for Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">

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

        .badge-soft-info {
            background-color: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

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

        .form-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 0.85rem;
        }

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
                <h2 class="page-title fw-bold mb-0 text-dark" style="font-size: 1.5rem;">Manage Bulk Quotations</h2>
                <p class="text-muted small mb-0 mt-1">Track, manage, and assign bulk orders to vendors.</p>
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

        <!-- Filter Section -->
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <form action="{{ route('manage_bulk_quotes') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Search Quote</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i
                                        class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light"
                                    value="{{ request('search') }}" placeholder="ID, Name, Phone...">
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
                                <a href="{{ route('manage_bulk_quotes') }}"
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
                                <th class="ps-4">Quote ID</th>
                                <th>Client Details</th>
                                <th>Location & Date</th>
                                <th>Assigned Vendor</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotes as $quote)
                                <tr>
                                    <td class="ps-4">
                                        <span
                                            class="d-block fw-bold text-dark">#BQR{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</span>
                                        <span class="small text-muted"><i
                                                class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($quote->created_at)->format('d M Y') }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width: 32px; height: 32px; background-color: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.85rem;">
                                                {{ strtoupper(substr($quote->full_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="d-block fw-semibold text-dark">{{ $quote->full_name }}</span>
                                                <span class="small text-muted">{{ $quote->phone_number }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block fw-medium text-dark text-truncate" style="max-width: 200px;"
                                            title="{{ $quote->delivery_location }}">{{ $quote->delivery_location }}</span>
                                        <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>Req:
                                            {{ \Carbon\Carbon::parse($quote->preferred_delivery_date)->format('d M Y') }}</span>
                                    </td>
                                    <td>
                                        @if($quote->vendor)
                                            <span
                                                class="d-block fw-semibold text-success">{{ $quote->vendor->business_name }}</span>
                                            <span class="small text-muted">{{ $quote->vendor->contact_person }}</span>
                                        @else
                                            <span class="badge badge-soft-secondary"><i class="bi bi-dash"></i>
                                                Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = 'badge-soft-secondary';
                                            if (in_array($quote->status, ['Accepted', 'Completed', 'Shipped']))
                                                $badgeClass = 'badge-soft-success';
                                            if (in_array($quote->status, ['Rejected', 'Canceled']))
                                                $badgeClass = 'badge-soft-danger';
                                            if ($quote->status == 'Pending')
                                                $badgeClass = 'badge-soft-warning';
                                            if ($quote->status == 'Vendor Assigned')
                                                $badgeClass = 'badge-soft-primary';
                                            if ($quote->status == 'Out For Delivery')
                                                $badgeClass = 'badge-soft-info';
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $quote->status }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('view_bulk_quote', $quote->id) }}" class="btn btn-elegant">
                                            <i class="bi bi-eye me-1 icon-dark"></i> View & Assign
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                                        <span class="fw-medium">No bulk quotations found.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 pt-4 pb-3 px-4" style="border-radius: 0 0 10px 10px;">
                {{ $quotes->appends(request()->input())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function () {
            $(".datepicker").datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
        });
    </script>
</body>

</html>