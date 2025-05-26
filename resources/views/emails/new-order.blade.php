<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Order Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            background: #f0ad4e;
            color: #fff;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header img {
            height: 50px;
            margin-right: 15px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .order-info {
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .order-info p {
            margin: 5px 0;
        }
        .highlight {
            font-weight: bold;
            color: #f0ad4e;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #5bc0de;
            color: white;
            border-radius: 3px;
            font-weight: bold;
        }
        h3 {
            border-bottom: 2px solid #f0ad4e;
            padding-bottom: 5px;
            color: #444;
        }
    </style>
</head>
<body>
    <div class="mb-2" style="text-align: center;">
        <img src="{{ $message->embed(public_path('assets/images/mgrc/logo_title_mgrc.png')) }}" alt="MGRC Logo" height="100">
    </div>
    <div class="container">
        <div class="header">
            <h2>New Order Notification</h2>
        </div>
        
        <p>Hello,</p>
        
        <p>A new order has been placed and requires attention. Please find the details below:</p>
        
        <div class="order-info">
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('F j, Y') }}</p>
            <p><strong>Order Time:</strong> {{ \Carbon\Carbon::parse($order->order_time)->format('g:i A') }}</p>
            <p><strong>Delivery Type:</strong> <span style="color: {{ $order->delivery_type === 'delivery' ? '#28a745' : '#007bff' }}">
                <i>{{ $order->delivery_type === 'delivery' ? '🚚 Delivery' : '🏃 Self Collect' }}</i>
            </span></p>
            <p><strong>Delivery/Pickup Date:</strong> {{ \Carbon\Carbon::parse($order->pickup_delivery_date)->format('F j, Y') }}</p>
            <p><strong>Delivery Time:</strong> {{ \Carbon\Carbon::parse($order->delivery_time)->format('g:i A') }}</p>
            <p><strong>Status:</strong> <span class="status-badge">Pending Production</span></p>
            <p><strong>Order Placed By:</strong> {{ $order->order_placed_by }}</p>
        </div>
        
        <h3>Customer Information</h3>
        <div class="order-info">
            <p><strong>Name:</strong> {{ $order->customer->name }}</p>
            <p><strong>Phone:</strong> {{ $order->customer->phoneNo }}</p>
            <p><strong>Email:</strong> {{ $order->customer->email ?? 'N/A' }}</p>
            <p><strong>Address:</strong> {{ $order->customer->address }}</p>
        </div>
        
        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Patient Name</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->pivot->quantity }}</td>
                    <td>{{ $product->pivot->patient_name ?? 'N/A' }}</td>
                    <td>{{ $product->pivot->remarks ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if($order->remarks)
        <h3>General Remarks</h3>
        <p>{{ $order->remarks }}</p>
        @endif
        
        <p>Please process this order at your earliest convenience.</p>
        
        <p>Thank you,<br>MGRC Order Management System</p>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 