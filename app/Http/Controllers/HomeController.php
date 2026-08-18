<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        
        // Calendar events - orders with delivery dates
        $calendarEvents = Order::with(['customer', 'products'])
            ->whereNotNull('pickup_delivery_date')
            ->where('status', '!=', 'cancel')
            ->get()
            ->map(function ($order) {
                $statusColors = [
                    'new' => '#f8f9fa',
                    'preparing' => '#f1b44c',
                    'ready' => '#405189',
                    'delivered' => '#0ab39c'
                ];
                
                $textColors = [
                    'new' => '#212529',
                    'preparing' => '#ffffff',
                    'ready' => '#ffffff',
                    'delivered' => '#ffffff'
                ];
                
                // Get product list with quantities
                $productList = $order->products->map(function ($product) {
                    return $product->name . ' (Qty: ' . $product->pivot->quantity . ')';
                })->toArray();
                
                $backgroundColor = $statusColors[$order->status] ?? '#6c757d';
                $borderColor = $backgroundColor;
                $textColor = $textColors[$order->status] ?? '#ffffff';

                if ($order->time_sensitive) {
                    $backgroundColor = '#dc3545';
                    $borderColor = '#dc3545';
                    $textColor = '#ffffff';
                }

                return [
                    'id' => $order->id,
                    'title' => '#' . $order->id . ' - ' . ($order->customer->name ?? 'N/A'),
                    'start' => $order->pickup_delivery_date->format('Y-m-d'),
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'textColor' => $textColor,
                    'status' => $order->status,
                    'customer' => $order->customer->name ?? 'N/A',
                    'products_count' => $order->products->count(),
                    'products_list' => $productList,
                    'delivery_type' => $order->delivery_type,
                    'delivery_time' => $order->pickup_delivery_time ? $order->pickup_delivery_time->format('H:i') : null,
                    'time_sensitive' => (bool) $order->time_sensitive
                ];
            });
        
        // Upcoming deliveries (today and tomorrow) - exclude delivered and canceled orders
        $upcomingDeliveries = Order::with(['customer', 'products'])
            ->whereNotNull('pickup_delivery_date')
            ->whereBetween('pickup_delivery_date', [Carbon::now()->startOfDay(), Carbon::now()->addDay()->endOfDay()])
            ->whereNotIn('status', ['delivered', 'canceled'])
            ->orderBy('pickup_delivery_date')
            ->get();
            
        // Overdue deliveries - orders that passed delivery date but not delivered
        $overdueDeliveries = Order::with(['customer', 'products'])
            ->whereNotNull('pickup_delivery_date')
            ->where('pickup_delivery_date', '<', Carbon::now())
            ->whereNotIn('status', ['delivered', 'cancel'])
            ->orderBy('pickup_delivery_date')
            ->get();
        
        // Set by verifyPassword(). Read here so the prompt appears once per
        // session rather than on every dashboard load - the flag was already
        // being written, it was just never checked.
        $dashboardUnlocked = (bool) session('dashboard_unlocked', false);

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
            'currentYear',
            'calendarEvents',
            'upcomingDeliveries',
            'overdueDeliveries',
            'dashboardUnlocked'
        ));
    }

    /**
     * Clear the dashboard unlock so a refresh cannot bypass the idle re-lock.
     *
     * Called by the idle timer on the dashboard. Without it the session flag
     * would survive a lock and reloading the page would walk straight in.
     */
    public function lockDashboard(Request $request)
    {
        session()->forget(['dashboard_unlocked', 'dashboard_unlocked_at']);

        return response()->json(['success' => true]);
    }

    /**
     * Verify password for dashboard access
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (Hash::check($request->password, $user->password)) {
            // Store in session that dashboard is unlocked
            session(['dashboard_unlocked' => true]);
            session(['dashboard_unlocked_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Password verified successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid password. Please try again.'
        ], 401);
    }
}