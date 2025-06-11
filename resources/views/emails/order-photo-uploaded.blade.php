<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Photo Uploaded</title>
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
        .btn-primary {
            display: inline-block;
            padding: 12px 24px;
            background-color: #f0ad4e;
            color: #fff !important;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn-primary:hover {
            background-color: #ec971f;
        }
        .photo-preview {
            text-align: center;
            margin: 20px 0;
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .photo-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .photo-preview .photo-caption {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        .product-list {
            margin: 15px 0;
            padding: 0;
            list-style: none;
        }
        .product-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .product-list li:last-child {
            border-bottom: none;
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
    <div class="container">
        <div class="header">
            <h2>Order Photo Uploaded</h2>
        </div>
        <p>Hello,</p>
        <p>A photo has been uploaded for your order <span class="highlight">#{{ $order->id }}</span>.</p>
        
        <div class="order-info">
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Status:</strong> <span class="status-badge">{{ ucfirst($order->status) }}</span></p>
            <p><strong>Order Placed By:</strong> {{ $order->order_placed_by }}</p>
            <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
            
            <div class="product-list">
                <h4>Products:</h4>
                @foreach($order->products as $product)
                <li>
                    {{ $product->name }} (Qty: {{ $product->pivot->quantity }})
                    @if($product->pivot->batch_number)
                    <br><small>Batch: {{ $product->pivot->batch_number }}</small>
                    @endif
                </li>
                @endforeach
            </div>
        </div>

        @if($order->order_photo)
        <div class="photo-preview">
            <h4>Order Photo</h4>
            <img src="{{ $message->embed(storage_path('app/public/order_photos/' . $order->order_photo)) }}" alt="Order Photo">
            <p class="photo-caption">Photo of completed order #{{ $order->id }}</p>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ $markReadyUrl }}" class="btn-primary">Mark as Ready</a>
            {{-- <a href="{{ route('orderdetails', $order->id) }}" class="button">View Order Details</a> --}}
        </div>

        <p>Thank you,<br>MGRC Order Management System</p>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
