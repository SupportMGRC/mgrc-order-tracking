<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>COA Edit Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #ddd;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .logo-area {
            text-align: center;
            padding: 20px 0 10px;
            background: #ffffff;
        }
        .logo-area img { height: 70px; }
        .header {
            background: #f0ad4e;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .header h2 { margin: 0; font-size: 21px; }
        .header p { margin: 6px 0 0; font-size: 14px; opacity: 0.95; }
        .body-content { padding: 22px; }
        .status-badge {
            display: inline-block;
            background: #5bc0de;
            color: #ffffff;
            padding: 7px 18px;
            border-radius: 4px;
            font-weight: bold;
            margin: 6px 0 18px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        h3 {
            font-size: 15px;
            color: #444;
            border-bottom: 2px solid #f0ad4e;
            padding-bottom: 6px;
            margin: 24px 0 12px;
        }
        .card {
            background: #faf9f7;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }
        .card p { margin: 5px 0; font-size: 14px; }
        .label { color: #777; display: inline-block; min-width: 150px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 13px;
        }
        table, th, td { border: 1px solid #e0e0e0; }
        th {
            background: #f0ad4e;
            color: #ffffff;
            text-align: left;
            padding: 9px;
        }
        td { padding: 9px; }
        .button-wrap { text-align: center; margin: 22px 0; }
        .order-photos { margin: 8px 0 4px; }
        .order-photos img {
            max-width: 100%;
            width: 260px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin: 6px 6px 6px 0;
            vertical-align: top;
        }
        .footer {
            padding: 16px 20px;
            font-size: 12px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-area">
            <img src="{{ $message->embed(public_path('assets/images/mgrc/logo_title_mgrc.png')) }}" alt="MGRC Logo" height="70">
        </div>

        <div class="header">
            <h2>COA Edit Request</h2>
            <p>Order #{{ $order->id }} &mdash; {{ $product->name }}</p>
        </div>

        <div class="body-content">
            <p>Hello {{ $approver->fullName() }},</p>

            <p>{{ $requester->fullName() }} has asked to edit a submitted Certificate of Analysis. The COA stays locked until you approve or reject the request.</p>

            <div class="status-badge">Waiting for approval</div>

            <h3>Request</h3>
            <div class="card">
                <p><span class="label">Requested By:</span> {{ $requester->fullName() }}</p>
                <p><span class="label">Requested At:</span> {{ $editRequest->created_at->format('F j, Y g:i A') }}</p>
                <p><span class="label">Reason:</span> {{ $editRequest->reason }}</p>
            </div>

            <h3>COA</h3>
            <div class="card">
                <p><span class="label">Order:</span> #{{ $order->id }}</p>
                <p><span class="label">Product:</span> {{ $product->name }}</p>
                @php $line = $order->products()->where('product_id', $product->id)->first(); @endphp
                @if($line)
                    <p><span class="label">COA No:</span> {{ $line->pivot->qc_document_number ?: '-' }}</p>
                    <p><span class="label">Submitted By:</span> {{ $line->pivot->coa_signatory_name ?: '-' }}</p>
                @endif
            </div>

            <p style="font-size:13px; color:#777;">If you approve, every COA value on this order line is cleared and Quality Control fills it in again. Patient name and batch number are kept.</p>

            <div class="button-wrap">
                <a href="{{ route('orders.coa', [$order->id, $product->id]) }}"
                   style="display:inline-block; background-color:#f0ad4e; color:#ffffff; text-decoration:none; padding:12px 26px; border-radius:5px; font-weight:bold; font-family:Arial, sans-serif;">
                   Open COA to Decide
                </a>
            </div>

            <p style="font-size:13px; color:#777;">You are receiving this because you are set as a COA approver in TRACOM.</p>
        </div>

        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
