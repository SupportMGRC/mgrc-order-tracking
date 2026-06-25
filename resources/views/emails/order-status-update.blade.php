<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Status Update</title>
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
            <h2>Order Status Update</h2>
            <p>Order #{{ $order->id }} &mdash; {{ $statusLabel }}</p>
        </div>

        <div class="body-content">
            @php $scheduleChanges = $scheduleChanges ?? []; @endphp
            <p>Hello{{ $order->order_placed_by ? ' ' . $order->order_placed_by : '' }},</p>

            @if(!empty($scheduleChanges))
                <p>The delivery schedule for an order you placed has been updated:</p>
            @else
                <p>The status of an order you placed has been updated to:</p>
            @endif

            <div class="status-badge">{{ $statusLabel }}</div>

            @if(!empty($scheduleChanges))
            <h3>Schedule Change</h3>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>From (Old)</th>
                        <th>To (New)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scheduleChanges as $field => $change)
                    <tr>
                        <td>{{ $field }}</td>
                        <td>{{ $change['from'] }}</td>
                        <td>{{ $change['to'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            <h3>Order Details</h3>
            <div class="card">
                <p><span class="label">Order ID:</span> #{{ $order->id }}</p>
                @if($order->order_date)
                    <p><span class="label">Order Date:</span> {{ \Carbon\Carbon::parse($order->order_date)->format('F j, Y') }}</p>
                @endif
                @if($order->order_time)
                    <p><span class="label">Order Time:</span> {{ \Carbon\Carbon::parse($order->order_time)->format('g:i A') }}</p>
                @endif
                <p><span class="label">Delivery Type:</span>
                    {{ $order->delivery_type === 'delivery' ? '🚚 Delivery' : '🏃 Self Collect' }}
                </p>
                @if($order->pickup_delivery_date)
                    <p><span class="label">Delivery/Pickup Date:</span> {{ \Carbon\Carbon::parse($order->pickup_delivery_date)->format('F j, Y') }}</p>
                @endif
                @if($order->pickup_delivery_time)
                    <p><span class="label">Delivery/Pickup Time:</span> {{ \Carbon\Carbon::parse($order->pickup_delivery_time)->format('g:i A') }}</p>
                @endif
                @if($order->delivery_address)
                    <p><span class="label">Delivery Address:</span> {{ $order->delivery_address }}</p>
                @endif
                <p><span class="label">Order Placed By:</span> {{ $order->order_placed_by ?? 'N/A' }}</p>
                <p><span class="label">Current Status:</span> {{ $statusLabel }}</p>
            </div>

            @if($order->customer)
            <h3>Customer Information</h3>
            <div class="card">
                <p><span class="label">Name:</span> {{ $order->customer->name ?? 'N/A' }}</p>
                <p><span class="label">Phone:</span> {{ $order->customer->phoneNo ?? '-' }}</p>
                <p><span class="label">Email:</span> {{ $order->customer->email ?? 'N/A' }}</p>
                <p><span class="label">Address:</span> {{ $order->customer->address ?? 'N/A' }}</p>
            </div>
            @endif

            @if($order->products && count($order->products) > 0)
            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Patient Name</th>
                        <th>Batch</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->pivot->quantity ?? 'N/A' }}</td>
                        <td>{{ $product->pivot->patient_name ?? 'N/A' }}</td>
                        <td>{{ $product->pivot->batch_number ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            @if($order->remarks)
            <h3>General Remarks</h3>
            <p>{{ $order->remarks }}</p>
            @endif

            @php $emailOrderPhotos = $order->getAllPhotos(); @endphp
            @if(!empty($emailOrderPhotos))
            <h3>Order Photo</h3>
            <div class="order-photos">
                @foreach($emailOrderPhotos as $index => $photo)
                    @php $photoPath = storage_path('app/public/order_photos/' . $photo); @endphp
                    @if(file_exists($photoPath))
                    <img src="{{ $message->embed($photoPath) }}" alt="Order Photo {{ $index + 1 }}">
                    @endif
                @endforeach
            </div>
            @endif

            @if($status === 'delivered' && $order->hasDeliveryPhotos())
            <h3>Dispatcher Evidence</h3>
            <div class="order-photos">
                @foreach($order->delivery_photos as $index => $photo)
                    @php $deliveryPhotoPath = storage_path('app/public/order_photos/' . $photo); @endphp
                    @if(file_exists($deliveryPhotoPath))
                    <img src="{{ $message->embed($deliveryPhotoPath) }}" alt="Dispatcher Evidence {{ $index + 1 }}">
                    @endif
                @endforeach
            </div>
            @endif

            <div class="button-wrap">
                <a href="{{ route('orderdetails', $order->id) }}"
                   style="display:inline-block; background-color:#f0ad4e; color:#ffffff; text-decoration:none; padding:12px 26px; border-radius:5px; font-weight:bold; font-family:Arial, sans-serif;">
                   View Order Details
                </a>
            </div>

            <p style="font-size:13px; color:#777;">You are receiving this email because you created this order in the MGRC Order Tracking system.</p>
        </div>

        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System. Please do not reply.</p>
        </div>
    </div>
</body>
</html>