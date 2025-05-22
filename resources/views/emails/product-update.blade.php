<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
        .header {
            background-color: #4b72b2;
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
        .changed {
            background-color: #fffbe6;
        }
        .new-product {
            background-color: #f0fff4;
        }
        .removed-product {
            background-color: #fff0f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Order Update Notification</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>This is to inform you that changes have been made to Order #{{ $order->id }}.</p>
            
            <h3>Order Details:</h3>
            <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
            <p><strong>Order Date:</strong> {{ $order->order_date }} {{ $order->order_time }}</p>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            
            <h3>Updated Information:</h3>
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
            
            <p>Please check the order in the system for complete details.</p>
            
            <p>Thank you,<br>MGRC Order Tracking System</p>
        </div>
        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 