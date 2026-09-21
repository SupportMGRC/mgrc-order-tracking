<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $copy['headline'] }}</title>
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
            <h2>{{ $copy['headline'] }}</h2>
            <p>Pickup {{ $pickup->reference_no }} &mdash; {{ $pickup->statusLabel() }}</p>
        </div>

        <div class="body-content">
            <p>Hello{{ $recipientName ? ' ' . $recipientName : '' }},</p>

            <p>{{ $copy['intro'] }}</p>

            <div class="status-badge">{{ $pickup->statusLabel() }}</div>

            @if($pickup->time_sensitive && in_array($event, ['new', 'on_the_way'], true))
                <p style="background:#fdecea; color:#c62828; border:1px solid #f5c2c0; border-radius:4px; padding:8px 12px; font-weight:bold; font-size:13px;">
                    This pickup is marked as Time Sensitive.
                </p>
            @endif

            {{-- Event-specific details --}}
            @if($event === 'on_the_way')
                <h3>Despatch Details</h3>
                <div class="card">
                    <p><span class="label">Despatcher:</span> {{ $pickup->despatcher_name ?? 'N/A' }}</p>
                    <p><span class="label">On the Way Since:</span> {{ $pickup->on_the_way_at ? $pickup->on_the_way_at->format('F j, Y g:i A') : 'N/A' }}</p>
                </div>
            @elseif($event === 'picked_up')
                <h3>Pickup Confirmation</h3>
                <div class="card">
                    <p><span class="label">Despatcher:</span> {{ $pickup->despatcher_name ?? $pickup->picked_up_by_name ?? 'N/A' }}</p>
                    <p><span class="label">Picked Up At:</span> {{ $pickup->picked_up_at ? $pickup->picked_up_at->format('F j, Y g:i A') : 'N/A' }}</p>
                    <p><span class="label">Handed Over By:</span> {{ $pickup->handed_over_by ?? 'N/A' }}</p>
                    <p><span class="label">Temperature:</span> {{ $pickup->pickup_temperature ?: 'N/A' }}</p>
                    @if($pickup->pickup_remarks)
                        <p><span class="label">Despatcher Remarks:</span> {{ $pickup->pickup_remarks }}</p>
                    @endif
                </div>
            @elseif($event === 'received')
                <h3>Receiving Details</h3>
                <div class="card">
                    <p><span class="label">Received By:</span> {{ $pickup->received_by_name ?? 'N/A' }}</p>
                    <p><span class="label">Received At:</span> {{ $pickup->received_at ? $pickup->received_at->format('F j, Y g:i A') : 'N/A' }}</p>
                    <p><span class="label">Temperature on Arrival:</span> {{ $pickup->received_temperature ?: 'N/A' }}</p>
                    @if($pickup->received_remarks)
                        <p><span class="label">Receiving Remarks:</span> {{ $pickup->received_remarks }}</p>
                    @endif
                </div>
            @elseif($event === 'cancelled')
                <h3>Cancellation Details</h3>
                <div class="card">
                    <p><span class="label">Cancelled By:</span> {{ $pickup->cancelled_by_name ?? 'N/A' }}</p>
                    <p><span class="label">Cancelled At:</span> {{ $pickup->cancelled_at ? $pickup->cancelled_at->format('F j, Y g:i A') : 'N/A' }}</p>
                    <p><span class="label">Reason:</span> {{ $pickup->cancel_reason ?? 'N/A' }}</p>
                </div>
            @endif

            <h3>Pickup Details</h3>
            <div class="card">
                <p><span class="label">Reference:</span> {{ $pickup->reference_no }}</p>
                <p><span class="label">Requested On:</span> {{ $pickup->created_at->format('F j, Y g:i A') }}</p>
                <p><span class="label">Pickup Date:</span> {{ $pickup->pickup_date->format('F j, Y') }}</p>
                <p><span class="label">Pickup Time:</span> {{ $pickup->pickup_time->format('g:i A') }}</p>
                <p><span class="label">Pickup Address:</span> {{ preg_replace('/\s*\R\s*/', ' ', $pickup->pickup_address) }}</p>
                @if($pickup->contact_person)
                    <p><span class="label">Contact Person:</span> {{ $pickup->contact_person }}</p>
                @endif
                <p><span class="label">Contact Phone:</span> {{ $pickup->contact_phone }}</p>
                <p><span class="label">Requested By:</span> {{ $pickup->requested_by ?? 'N/A' }}</p>
                <p><span class="label">Current Status:</span> {{ $pickup->statusLabel() }}</p>
            </div>

            @if($pickup->customer)
            <h3>Customer Information</h3>
            <div class="card">
                <p><span class="label">Name:</span> {{ $pickup->customer->name ?? 'N/A' }}</p>
                <p><span class="label">Phone:</span> {{ $pickup->customer->phoneNo ?? '-' }}</p>
                <p><span class="label">Email:</span> {{ $pickup->customer->email ?? 'N/A' }}</p>
                <p><span class="label">Address:</span> {{ $pickup->customer->address ?? 'N/A' }}</p>
            </div>
            @endif

            <h3>Pickup Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pickup->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->quantity }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($pickup->remarks)
            <h3>General Remarks</h3>
            <p>{{ $pickup->remarks }}</p>
            @endif

            @if($event === 'picked_up' && !empty($pickup->pickup_photos))
            <h3>Despatcher Evidence</h3>
            <div class="order-photos">
                @foreach($pickup->pickup_photos as $index => $photo)
                    @php $photoPath = storage_path('app/public/' . \App\Models\Pickup::PHOTO_DIR . '/' . $photo); @endphp
                    @if(file_exists($photoPath))
                    <img src="{{ $message->embed($photoPath) }}" alt="Despatcher Evidence {{ $index + 1 }}">
                    @endif
                @endforeach
            </div>
            @endif

            <div class="button-wrap">
                <a href="{{ route('pickups.show', $pickup->id) }}"
                   style="display:inline-block; background-color:#f0ad4e; color:#ffffff; text-decoration:none; padding:12px 26px; border-radius:5px; font-weight:bold; font-family:Arial, sans-serif;">
                   View Pickup Details
                </a>
            </div>

            <p style="font-size:13px; color:#777;">{{ $reason }}</p>
        </div>

        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
