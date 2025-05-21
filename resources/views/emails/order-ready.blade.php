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
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0d6efd;
            color: white;
            padding: 15px 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 5px 5px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .order-info {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #eee;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
        .button {
            display: inline-block;
            background-color: #0d6efd;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
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
    <div class="container">
        <div class="header">
            <h2>Order #{{ $order->id }} is Ready for Delivery</h2>
        </div>
        
        <div class="content">
            <p>Hello Admin & HR Team,</p>
            
            <div class="alert">
                <strong>Action Required:</strong> An order is now ready for delivery to the customer.
            </div>
            
            <div class="order-info">
                <h3>Order Details:</h3>
                <p><strong>Order ID:</strong> #{{ $order->id }}</p>
                <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
                <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}</p>
                <p><strong>Status:</strong> <strong style="color: #0d6efd;">READY FOR DELIVERY</strong></p>
                
                @if($order->delivery_date)
                <p><strong>Expected Delivery:</strong> {{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}</p>
                @endif
                
                <h4>Products:</h4>
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
                <p><strong>Remarks:</strong> {{ $order->remarks }}</p>
                @endif
            </div>
            
            <p>Please arrange for delivery as soon as possible. You can view the full order details by clicking the button below:</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/orders/details/' . $order->id) }}" class="button">View Order Details</a>
            </div>
            
            <p>Thank you,<br>MGRC Order System</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System.</p>
        </div>
    </div>
</body>
</html> 