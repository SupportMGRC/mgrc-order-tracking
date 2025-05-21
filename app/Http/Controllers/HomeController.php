<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Today's orders by status
        $todayNewCount = Order::whereDate('created_at', $today)
            ->where('status', 'new')
            ->count();
            
        $todayPreparingCount = Order::whereDate('created_at', $today)
            ->where('status', 'preparing')
            ->count();
            
        $todayReadyCount = Order::whereDate('created_at', $today)
            ->where('status', 'ready')
            ->count();
            
        $todayDeliveredCount = Order::whereDate('created_at', $today)
            ->where('status', 'delivered')
            ->count();
            
        $todayTotalOrders = $todayNewCount + $todayPreparingCount + $todayReadyCount + $todayDeliveredCount;
        
        // Today's orders list
        $todayOrders = Order::with(['customer', 'products'])
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->get();
            
        // This month's orders
        $startOfMonth = Carbon::now()->startOfMonth();
        $monthlyOrderCount = Order::whereDate('created_at', '>=', $startOfMonth)->count();
        
        // This year's orders
        $startOfYear = Carbon::now()->startOfYear();
        $yearlyOrderCount = Order::whereDate('created_at', '>=', $startOfYear)->count();
        
        // Total counts
        $totalNewCount = Order::where('status', 'new')->count();
        $totalPreparingCount = Order::where('status', 'preparing')->count();
        $totalReadyCount = Order::where('status', 'ready')->count();
        $totalDeliveredCount = Order::where('status', 'delivered')->count();
        $totalOrders = $totalNewCount + $totalPreparingCount + $totalReadyCount + $totalDeliveredCount;
        
        // Customer and product counts
        $customerCount = Customer::count();
        $productCount = Product::count();
        $lowStockCount = Product::where('stock', '<', 10)->count();
        
        // Recent orders
        $recentOrders = Order::with(['customer', 'products'])
            ->latest('created_at')
            ->take(5)
            ->get();
            
        // Monthly order trends (current year)
        $currentYear = Carbon::now()->year;
        $monthlyOrders = Order::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->whereYear('created_at', $currentYear)
        ->groupBy('month')
        ->orderBy('month')
        ->get();
        
        // Format for chart - all 12 months
        $labels = [];
        $data = array_fill(0, 12, 0); // Initialize with zeros for all 12 months
        
        foreach ($monthlyOrders as $record) {
            $monthIndex = $record->month - 1; // Convert 1-based month to 0-based index
            $data[$monthIndex] = $record->count;
        }
        
        // Create month labels
        for ($i = 0; $i < 12; $i++) {
            $labels[] = date('M', mktime(0, 0, 0, $i + 1, 1));
        }
        
        return view('dashboard', compact(
            'todayNewCount',
            'todayPreparingCount',
            'todayReadyCount',
            'todayDeliveredCount',
            'todayTotalOrders',
            'todayOrders',
            'monthlyOrderCount',
            'yearlyOrderCount',
            'totalNewCount',
            'totalPreparingCount',
            'totalReadyCount',
            'totalDeliveredCount',
            'totalOrders',
            'customerCount',
            'productCount',
            'lowStockCount',
            'recentOrders',
            'labels',
            'data',
            'currentYear'
        ));
    }
}
