<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PRFController extends Controller
{
    /**
     * Display the PRF for printing based on order ID
     */
    public function show($id)
    {
        $order = Order::with(['customer', 'user', 'products'])->findOrFail($id);
        return view('orders.prfprint', compact('order'));
    }
    
    /**
     * Print the PRF for a specific order
     */
    public function print($id)
    {
        $order = Order::with(['customer', 'user', 'products'])->findOrFail($id);
        return view('orders.prfprint', compact('order'));
    }
} 