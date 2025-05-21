<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Ready for Delivery</title>
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
            background-color: #f0ad4e;
            color: white;
            border-radius: 3px;
            font-weight: bold;
        }
        h3 {
            border-bottom: 2px solid #f0ad4e;
            padding-bottom: 5px;
            color: #444;
        }
        .button {
            display: inline-block;
            background-color: #f0ad4e;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .alert {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="mb-2" style="text-align: center;">
        <img src="{{ $message->embed(public_path('assets/images/mgrc/logo_title_mgrc.png')) }}" alt="MGRC Logo" height="100">
    </div>
    <div class="container">
        <div class="header">
            <h2>Order #{{ $order->id }} is Ready for Delivery</h2>
        </div>
        
        <p>Hello Admin & HR Team,</p>
        
        <div class="alert">
            <strong>Action Required:</strong> An order is now ready for delivery to the customer.
        </div>
        
        <h3>Order Details</h3>
        <div class="order-info">
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
            <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('F j, Y') }}</p>
            <p><strong>Status:</strong> <span class="status-badge">READY FOR DELIVERY</span></p>
            
            @if($order->delivery_date)
            <p><strong>Expected Delivery:</strong> {{ \Carbon\Carbon::parse($order->delivery_date)->format('F j, Y') }}</p>
            @endif
        </div>
            
        <h3>Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Batch #</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->pivot->quantity }}</td>
                    <td>{{ $product->pivot->batch_number ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if($order->remarks)
        <h3>Remarks</h3>
        <p>{{ $order->remarks }}</p>
        @endif
        
        <p>Please arrange for delivery as soon as possible. You can view the full order details by clicking the button below:</p>
        
        <div style="text-align: center;">
            <a href="{{ url('/orders/details/' . $order->id) }}" class="button">View Order Details</a>
        </div>
        
        <p>Thank you,<br>MGRC Order System</p>
        
        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 