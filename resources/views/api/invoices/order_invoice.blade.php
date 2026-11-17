<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #333333;
            margin: 0;
            padding: 20px;
        }

        /* --- Header Section --- */
        .invoice-header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #F28D3C;
            padding-bottom: 15px;
        }

        .invoice-header td {
            vertical-align: middle;
        }

        .logo {
            max-height: 60px;
        }

        .invoice-title {
            color: #F28D3C;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .invoice-meta {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }

        /* --- Info Section (Bill To / Details) --- */
        .info-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-section td {
            vertical-align: top;
            width: 50%;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #F28D3C;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
            display: inline-block;
        }

        .info-text {
            line-height: 1.6;
            color: #444;
        }

        /* --- Items Table --- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
            text-align: left;
            padding: 12px;
            border-top: 1px solid #ddd;
            border-bottom: 2px solid #F28D3C;
            font-size: 12px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #444;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* --- Totals Section --- */
        .totals-container {
            width: 100%;
        }

        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f5f5f5;
        }

        .totals-table .grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #F28D3C;
            border-top: 2px solid #F28D3C;
            border-bottom: none;
            padding-top: 12px;
        }

        /* --- Footer --- */
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #777;
            text-align: center;
            line-height: 1.5;
        }

        .auth-sign {
            margin-top: 40px;
            text-align: right;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <table class="invoice-header">
        <tr>
            <!-- Using public_path is the most reliable way for DOMPDF to render local images -->
            <td style="width: 50%;">
                <img src="{{ public_path('img/logo_admin.png') }}" class="logo" alt="Nirmaan NXT Logo">
            </td>
            <td style="width: 50%;" class="text-right">
                <h1 class="invoice-title">Tax Invoice</h1>
                <div class="invoice-meta">
                    <strong>Order No:</strong> {{ $order->order_number }}<br>
                    <strong>Invoice Date:</strong> {{ \Carbon\Carbon::parse($order->updated_at)->format('d M, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Billing & Order Details -->
    <table class="info-section">
        <tr>
            <td style="padding-right: 20px;">
                <div class="section-title">Billed To</div>
                <div class="info-text">
                    <strong>{{ $order->user->name ?? 'Customer Name' }}</strong><br>
                    Phone: {{ $order->user->mobile ?? 'N/A' }}<br>
                    Address: {{ $order->address->delivery_address ?? 'Address not provided' }}
                </div>
            </td>
            <td style="padding-left: 20px;">
                <div class="section-title">Payment & Company Details</div>
                <div class="info-text">
                    <strong>Status:</strong> <span style="color: #16a34a; font-weight:bold;">PAID</span><br>
                    <strong>Payment Method:</strong> {{ $order->payment_type }}<br><br>
                    <strong>Company:</strong> Nirmaan NXT<br>
                    <strong>GSTIN:</strong> 36XXXXXXXXXX1Z5 <!-- Replace with actual GSTIN -->
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 15%;" class="text-center">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 20%;" class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                @php
                    $attr = $item->variant->attributes ?? 'Standard';
                    if (is_string($attr) && is_array(json_decode($attr, true))) {
                        $attrText = implode(', ', json_decode($attr, true));
                    } elseif (is_array($attr)) {
                        $attrText = implode(', ', $attr);
                    } else {
                        $attrText = $attr;
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->name ?? 'Product Name' }}</strong><br>
                        <span style="font-size: 11px; color: #777;">Spec: {{ $attrText }}</span>
                    </td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-right">₹{{ number_format($item->selling_price, 2) }}</td>
                    <td class="text-right">₹{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">₹{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if((float) $order->discount_amount > 0)
                <tr>
                    <td>Discount</td>
                    <td class="text-right" style="color: #dc2626;">-₹{{ number_format($order->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td>Delivery Charges</td>
                <td class="text-right">₹{{ number_format($order->delivery_charges, 2) }}</td>
            </tr>
            <tr>
                <td>Tax (GST)</td>
                <td class="text-right">₹{{ number_format($order->tax_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="grand-total">Grand Total</td>
                <td class="text-right grand-total">₹{{ number_format($order->grand_total, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Signatory -->
    <div style="clear: both;"></div>
    <div class="auth-sign">
        <p style="margin-bottom: 40px; color: #777;">For Nirmaan NXT</p>
        <p>Authorized Signatory</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>Thank you for your business!</strong><br>
        If you have any questions concerning this invoice, contact our support team at support@nirmaannxt.com.<br>
        <span style="font-size: 10px;">This is a computer-generated invoice and does not require a physical
            signature.</span>
    </div>

</body>

</html>