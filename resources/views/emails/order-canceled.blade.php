<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Canceled Notification</title>
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
            background: #dc3545;
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
            color: #dc3545;
        }
        h3 {
            border-bottom: 2px solid #dc3545;
            padding-bottom: 5px;
            color: #444;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #dc3545;
            color: white;
            border-radius: 3px;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="mb-2" style="text-align: center;">
        <img src="{{ $message->embed(public_path('assets/images/mgrc/logo_title_mgrc.png')) }}" alt="MGRC Logo" height="100">
    </div>
    <div class="container">
        <div class="header">
            <h2>Order Canceled Notification</h2>
        </div>
        <p>Hello,</p>
        <div class="order-info">
            <p><strong>Notice:</strong> Order #{{ $order->id }} has been <span class="highlight">canceled</span> and will not be processed further.</p>
        </div>
        <h3>Order Details</h3>
        <div class="order-info">
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
            <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('F j, Y') }} {{ \Carbon\Carbon::parse($order->order_time)->format('g:i A') }}</p>
            <p><strong>Status:</strong> <span class="status-badge">Canceled</span></p>
        </div>
        <p>If you have any questions regarding this cancellation, please contact the system administrator. You can view the full order details by clicking the button below:</p>
        
        <div style="text-align: center;">
            <a href="{{ route('orderdetails', $order->id) }}" class="button">View Order Details</a>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 