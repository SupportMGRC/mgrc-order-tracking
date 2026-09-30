<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>COA Returned for Correction</title>
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
            <h2>COA Returned for Correction</h2>
            <p>Order #{{ $order->id }} &mdash; {{ $product->name }}</p>
        </div>

        <div class="body-content">
            <p>Hello {{ $recipient->fullName() }},</p>

            @if($isUpload)
                <p>{{ $returnedBy->fullName() }} has returned the COA file you uploaded. Please upload a corrected COA.</p>
            @else
                <p>{{ $returnedBy->fullName() }} has returned the COA you submitted. Please correct it and submit it again.</p>
            @endif

            <div class="status-badge">Returned for correction</div>

            <h3>Reason</h3>
            <div class="card">
                <p>{{ $unlock->reason }}</p>
            </div>

            <h3>COA</h3>
            <div class="card">
                <p><span class="label">Order:</span> #{{ $order->id }}</p>
                <p><span class="label">Product:</span> {{ $product->name }}</p>
                @php $line = $order->products()->wherePivot('id', $unlock->order_product_id)->first(); @endphp
                @if($line && $line->pivot->patient_name)
                    <p><span class="label">Patient:</span> {{ $line->pivot->patient_name }}</p>
                @endif
                @if(!$isUpload && $line)
                    <p><span class="label">COA No:</span> {{ $line->pivot->qc_document_number ?: '-' }}</p>
                @endif
                <p><span class="label">Returned By:</span> {{ $returnedBy->fullName() }}</p>
                <p><span class="label">Returned At:</span> {{ ($unlock->decided_at ?? $unlock->created_at)->format('F j, Y g:i A') }}</p>
            </div>

            @if($isUpload)
                <p style="font-size:13px; color:#777;">The returned file has been removed from the order. Upload the corrected COA from Order Details.</p>
            @else
                <p style="font-size:13px; color:#777;">The values already entered are kept. Correct what is wrong and submit the COA again. It is signed by whoever submits it.</p>
            @endif

            <div class="button-wrap">
                <a href="{{ $isUpload ? route('orderdetails', $order->id) : route('orders.coa', [$order->id, $unlock->order_product_id]) }}"
                   style="display:inline-block; background-color:#f0ad4e; color:#ffffff; text-decoration:none; padding:12px 26px; border-radius:5px; font-weight:bold; font-family:Arial, sans-serif;">
                   {{ $isUpload ? 'Open Order' : 'Open COA' }}
                </a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
