<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Order List</title>

    @include('admin.includes.header_links')

    <style>
        .filter-card {
            background: #f8fafc;
            border: 1px solid #e9eef4;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .icon-btn {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
            font-size: 18px;
        }

        .btn-search {
            background: #0f172a;
            color: #fff;
        }

        .btn-search:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .btn-refresh {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-refresh:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
        }

        .btn-export-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .btn-export-icon:hover {
            background: #bbf7d0;
            transform: translateY(-1px);
        }

        .bulk-action-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding: 16px 18px;
            border: 1px solid #e9eef4;
            border-radius: 16px;
            background: #ffffff;
        }

        .bulk-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bulk-left .selected-text {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }

        .bulk-btn {
            border: none;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .bulk-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .bulk-delete:hover {
            background: #fecaca;
        }

        .bulk-export {
            background: #dcfce7;
            color: #15803d;
        }

        .bulk-export:hover {
            background: #bbf7d0;
        }

        .order-id {
            font-weight: 800;
            color: #0f172a;
        }

        .sub-text {
            font-size: 13px;
            color: #64748b;
        }

        .amount {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .payment-badge,
        .delivery-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .payment-paid {
            background: #dcfce7;
            color: #15803d;
        }

        .payment-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .payment-failed {
            background: #fee2e2;
            color: #dc2626;
        }

        .delivery-processing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .delivery-shipped {
            background: #ede9fe;
            color: #7c3aed;
        }

        .delivery-delivered {
            background: #dcfce7;
            color: #15803d;
        }

        .delivery-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .customer-name {
            font-weight: 700;
            color: #0f172a;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .pagination-info {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .bulk-action-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

@include('admin.includes.sidebar')  
@include('admin.includes.header')

<div class="content">

    <!-- PAGE HEADER -->
    <div class="page-head">
        <div>
            <h1 class="page-title">Order List</h1>
            <div class="section-title">Track and manage all customer orders</div>
        </div>

        <div class="action-group">
            <a href="{{ route('admin.dashboard') }}" class="btn-action btn-back">← Dashboard</a>
        </div>
    </div>

    <div class="property-form">

        <!-- FILTER SECTION -->
        <div class="filter-card">
            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Search Order</label>
                    <input type="text" class="form-control" placeholder="Order ID / Customer name">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Payment Status</label>
                    <select class="form-select">
                        <option value="">All</option>
                        <option>Paid</option>
                        <option>Pending</option>
                        <option>Failed</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Delivery Status</label>
                    <select class="form-select">
                        <option value="">All</option>
                        <option>Processing</option>
                        <option>Shipped</option>
                        <option>Delivered</option>
                        <option>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control">
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="icon-btn btn-search" title="Search">
                            <i class="bi bi-search"></i>
                        </button>

                        <button type="button" class="icon-btn btn-refresh" title="Refresh" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>

                        <button type="button" class="icon-btn btn-export-icon" title="Export">
                            <i class="bi bi-box-arrow-up"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- BULK ACTION BAR -->
        <div class="bulk-action-bar">
            <div class="bulk-left">
                <span class="selected-text"><span id="selectedCount">0</span> selected</span>
                <button type="button" class="bulk-btn bulk-export">Export Selected</button>
                <button type="button" class="bulk-btn bulk-delete">Delete Selected</button>
            </div>
        </div>

        <!-- ORDER TABLE -->
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width:50px;"><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Delivery</th>
                        <th>Order Date</th>
                        <th style="min-width:180px;">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td>1</td>
                        <td>
                            <div class="order-id">#ORD1025</div>
                            <div class="sub-text">Online Order</div>
                        </td>
                        <td>
                            <div class="customer-name">Rahul Sharma</div>
                            <div class="sub-text">rahul@gmail.com</div>
                        </td>
                        <td>
                            <div class="sub-text">iPhone 15 Pro, AirPods</div>
                        </td>
                        <td><div class="amount">₹1,62,500</div></td>
                        <td><span class="payment-badge payment-paid">Paid</span></td>
                        <td><span class="delivery-badge delivery-processing">Processing</span></td>
                        <td>04 Apr 2026</td>
                        <td class="action-cell">
                            <a href="view_order.php?id=1" class="action-btn btn-view">View</a>
                            <a href="invoice.php?id=1" class="action-btn btn-edit">Invoice</a>
                            <button class="action-btn btn-delete">Delete</button>
                        </td>
                    </tr>

                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td>2</td>
                        <td>
                            <div class="order-id">#ORD1024</div>
                            <div class="sub-text">COD Order</div>
                        </td>
                        <td>
                            <div class="customer-name">Priya Reddy</div>
                            <div class="sub-text">priya@gmail.com</div>
                        </td>
                        <td>
                            <div class="sub-text">Gaming Laptop</div>
                        </td>
                        <td><div class="amount">₹79,999</div></td>
                        <td><span class="payment-badge payment-pending">Pending</span></td>
                        <td><span class="delivery-badge delivery-shipped">Shipped</span></td>
                        <td>03 Apr 2026</td>
                        <td class="action-cell">
                            <a href="view_order.php?id=2" class="action-btn btn-view">View</a>
                            <a href="invoice.php?id=2" class="action-btn btn-edit">Invoice</a>
                            <button class="action-btn btn-delete">Delete</button>
                        </td>
                    </tr>

                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td>3</td>
                        <td>
                            <div class="order-id">#ORD1023</div>
                            <div class="sub-text">Online Order</div>
                        </td>
                        <td>
                            <div class="customer-name">Amit Kumar</div>
                            <div class="sub-text">amit@gmail.com</div>
                        </td>
                        <td>
                            <div class="sub-text">Office Chair, Table Lamp</div>
                        </td>
                        <td><div class="amount">₹10,399</div></td>
                        <td><span class="payment-badge payment-paid">Paid</span></td>
                        <td><span class="delivery-badge delivery-delivered">Delivered</span></td>
                        <td>02 Apr 2026</td>
                        <td class="action-cell">
                            <a href="view_order.php?id=3" class="action-btn btn-view">View</a>
                            <a href="invoice.php?id=3" class="action-btn btn-edit">Invoice</a>
                            <button class="action-btn btn-delete">Delete</button>
                        </td>
                    </tr>

                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td>4</td>
                        <td>
                            <div class="order-id">#ORD1022</div>
                            <div class="sub-text">Online Order</div>
                        </td>
                        <td>
                            <div class="customer-name">Sneha Patel</div>
                            <div class="sub-text">sneha@gmail.com</div>
                        </td>
                        <td>
                            <div class="sub-text">Wall Lamp</div>
                        </td>
                        <td><div class="amount">₹1,899</div></td>
                        <td><span class="payment-badge payment-failed">Failed</span></td>
                        <td><span class="delivery-badge delivery-cancelled">Cancelled</span></td>
                        <td>01 Apr 2026</td>
                        <td class="action-cell">
                            <a href="view_order.php?id=4" class="action-btn btn-view">View</a>
                            <a href="invoice.php?id=4" class="action-btn btn-edit">Invoice</a>
                            <button class="action-btn btn-delete">Delete</button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="pagination-container">
            <div class="pagination-info">
                Showing 1–4 of 128 orders
            </div>

            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled">
                        <a class="page-link">Previous</a>
                    </li>
                    <li class="page-item active">
                        <a class="page-link">1</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link">2</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link">3</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link">Next</a>
                    </li>
                </ul>
            </nav>
        </div>

    </div>
</div>

@include('admin.includes.footer_links')

<script>
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const selectedCount = document.getElementById('selectedCount');

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        selectedCount.textContent = checked;
    }

    selectAll.addEventListener('change', function () {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAll.checked = false;
            }

            const allChecked = [...rowCheckboxes].every(cb => cb.checked);
            selectAll.checked = allChecked;

            updateSelectedCount();
        });
    });
</script>

</body>
</html>