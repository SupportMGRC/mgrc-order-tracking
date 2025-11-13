<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Update Notification</title>
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
        .changed {
            background-color: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .new-product {
            background-color: #d4edda;
        }
        .removed-product {
            background-color: #f8d7da;
        }
        .alert {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="mb-2" style="text-align: center;">
        <img src="{{ $message->embed(public_path('assets/images/mgrc/logo_title_mgrc.png')) }}" alt="MGRC Logo" height="100">
    </div>
    <div class="container">
        <div class="header">
            <h2>Order Update Notification</h2>
        </div>
        
        <p>Hello,</p>
        
        <div class="alert">
            <strong>Notice:</strong> Changes have been made to Order #{{ $order->id }}.
        </div>
        
        <h3>Order Details</h3>
        <div class="order-info">
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
            <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('F j, Y') }} {{ \Carbon\Carbon::parse($order->order_time)->format('g:i A') }}</p>
            <p><strong>Delivery Type:</strong> <span style="color: {{ $order->delivery_type === 'delivery' ? '#28a745' : '#007bff' }}">
                <i>{{ $order->delivery_type === 'delivery' ? '🚚 Delivery' : '🏃 Self Collect' }}</i>
            </span></p>
            <p><strong>Status:</strong> <span class="status-badge">{{ ucfirst($order->status) }}</span></p>
        </div>
        
        @php
            $isDeliveryUpdate = collect($updatedProducts)->where('name', 'Delivery Schedule')->isNotEmpty();
        @endphp

        @if($isDeliveryUpdate)
            <h3>📅 Delivery Schedule Update</h3>
            <div class="order-info">
                @foreach($updatedProducts as $product)
                    @if($product['name'] === 'Delivery Schedule' && isset($product['field_changes']))
                        @foreach($product['field_changes'] as $field => $change)
                            @if($change['from'] !== $change['to'])
                                @if($field === 'delivery_datetime')
                                    <p><strong>{{ $order->delivery_type === 'delivery' ? 'Delivery' : 'Self Collect' }} Date & Time:</strong></p>
                                    <p style="padding: 10px; background-color: #fff3cd; border-radius: 5px; margin: 10px 0;">
                                        <span style="color: #721c24; text-decoration: line-through;">{{ $change['from'] }}</span>
                                        <br>
                                        <span style="color: #155724; font-weight: bold;">→ {{ $change['to'] }}</span>
                                    </p>
                                @elseif($field === 'ready_time')
                                    <p><strong>🕐 Ready Time:</strong></p>
                                    <p style="padding: 10px; background-color: #e7f3ff; border-radius: 5px; margin: 10px 0;">
                                        <span style="color: #721c24; text-decoration: line-through;">{{ $change['from'] }}</span>
                                        <br>
                                        <span style="color: #155724; font-weight: bold;">→ {{ $change['to'] }}</span>
                                    </p>
                                @endif
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>
        @else
            <h3>Updated Information</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($updatedProducts as $product)
                    <tr class="{{ $product['is_new'] ? 'new-product' : '' }}">
                        <td>{{ $product['name'] }}</td>
                        <td>
                            @if(isset($product['previous_quantity']) && $product['quantity'] != $product['previous_quantity'])
                                <span class="changed">
                                    {{ $product['previous_quantity'] }} → {{ $product['quantity'] }}
                                </span>
                            @else
                                {{ $product['quantity'] }}
                            @endif
                        </td>
                        <td>
                            @if($product['is_new'])
                                <strong>New product added</strong>
                            @elseif(isset($product['field_changes']))
                                <ul style="margin: 0; padding-left: 20px;">
                                    @foreach($product['field_changes'] as $field => $change)
                                        @if($change['from'] !== $change['to'])
                                            <li>
                                                <strong>{{ ucwords(str_replace('_', ' ', $field)) }}:</strong> 
                                                {{ $change['from'] ?: 'empty' }} → {{ $change['to'] ?: 'empty' }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        
        <p>Please check the order in the system for complete details.</p>
        
        <div style="text-align: center;">
            <a href="{{ route('orderdetails', $order->id) }}" class="button">View Order Details</a>
        </div>
        
        <p>Thank you,<br>MGRC Order Tracking System</p>
        
        <div class="footer">
            <p>This is an automated message from the MGRC Order Tracking System. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 