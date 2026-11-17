<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Coupon Details</title>
    @include('admin.includes.header_links')
    <style>
        .info-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #edf2f7;
        }

        .label-text {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .value-text {
            color: #1e293b;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.6;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        .value-text p {
            margin-bottom: 1rem;
            white-space: normal;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-logo {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid #f8fafc;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Coupon Details</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('edit_coupon', $coupon->id) }}" class="btn-action btn-manage">Edit Coupon</a>
            </div>
        </div>

        <div class="info-card shadow-sm">
            <div class="profile-header">
                <img src="{{ $coupon->logo ? asset('uploads/coupon_logos/' . $coupon->logo) : 'https://via.placeholder.com/80' }}"
                    class="profile-logo shadow-sm">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">{{ $coupon->title }}</h2>
                    <h4 class="text-secondary">{{ $coupon->code }}</h4>
                    <span
                        class="status-badge {{ $coupon->status == 1 ? 'status-active' : ($coupon->status == 2 ? 'bg-danger text-white' : 'status-inactive') }}">
                        {{ $coupon->status == 1 ? 'Active' : ($coupon->status == 2 ? 'Expired' : 'Inactive') }}
                    </span>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-md-3">
                    <div class="label-text">Coupon Type</div>
                    <div class="value-text">{{ $coupon->coupon_type }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Discount Strategy</div>
                    <div class="value-text">{{ $coupon->discount_type }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Discount Value</div>
                    <div class="value-text">
                        {{ $coupon->discount_type == 'Percentage' ? $coupon->discount_value . '%' : '₹' . $coupon->discount_value }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Max Discount Amt.</div>
                    <div class="value-text">{{ $coupon->max_discount ? '₹' . $coupon->max_discount : 'Unlimited' }}</div>
                </div>

                @if($coupon->coupon_type == 'Normal')
                    <div class="col-md-3">
                        <div class="label-text">Min Order Amount</div>
                        <div class="value-text">{{ $coupon->min_order_amount ? '₹' . $coupon->min_order_amount : 'None' }}
                        </div>
                    </div>
                @else
                    <div class="col-md-3">
                        <div class="label-text">Threshold Amount</div>
                        <div class="value-text">₹{{ $coupon->threshold_amount }}</div>
                    </div>
                @endif

                <div class="col-md-3">
                    <div class="label-text">Usage Limit</div>
                    <div class="value-text">{{ $coupon->usage_limit ?? 'Unlimited' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Start Date</div>
                    <div class="value-text">{{ date('d M, Y', strtotime($coupon->start_date)) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">End Date</div>
                    <div class="value-text">{{ date('d M, Y', strtotime($coupon->end_date)) }}</div>
                </div>

                <div class="col-12 mt-4">
                    <div class="label-text">Description / Terms</div>
                    <div class="value-text shadow-none border-0 p-0">
                        @if($coupon->description)
                            {!! $coupon->description !!}
                        @else
                            <span class="text-muted italic">No description provided.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>