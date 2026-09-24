<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Pickup;
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
     * Same rule as Order History and Pickup History: these departments only
     * see their own records, here too.
     */
    private const OWN_RECORDS_ONLY_DEPARTMENTS = [
        'medical affairs',
        'business development',
    ];

    private function restrictedToOwnRecords(): bool
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['admin', 'superadmin'], true)) {
            return false;
        }

        return in_array(strtolower(trim((string) $user->department)), self::OWN_RECORDS_ONLY_DEPARTMENTS, true);
    }

    /**
     * Order query for this dashboard. Matches OrderController: by user_id, or
     * by the name recorded in order_placed_by for orders created before the
     * user link existed.
     */
    private function orders()
    {
        $query = Order::query();

        if (!$this->restrictedToOwnRecords()) {
            return $query;
        }

        $user = Auth::user();
        $fullName = strtolower(trim($user->first_name . ' ' . $user->last_name));

        return $query->where(function ($q) use ($user, $fullName) {
            $q->where('user_id', $user->id);

            if ($fullName !== '') {
                $q->orWhereRaw('LOWER(TRIM(order_placed_by)) = ?', [$fullName]);
            }
        });
    }

    /** Pickup query for this dashboard, scoped the same way. */
    private function pickups()
    {
        $query = Pickup::query();

        return $this->restrictedToOwnRecords()
            ? $query->where('user_id', Auth::id())
            : $query;
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
        $todayNewCount = $this->orders()->whereDate('created_at', $today)
            ->where('status', 'new')
            ->count();
            
        $todayPreparingCount = $this->orders()->whereDate('created_at', $today)
            ->where('status', 'preparing')
            ->count();
            
        $todayReadyCount = $this->orders()->whereDate('created_at', $today)
            ->where('status', 'ready')
            ->count();
            
        $todayDeliveredCount = $this->orders()->whereDate('created_at', $today)
            ->where('status', 'delivered')
            ->count();
            
        $todayTotalOrders = $todayNewCount + $todayPreparingCount + $todayReadyCount + $todayDeliveredCount;
        
        // Today's orders list
        $todayOrders = $this->orders()->with(['customer', 'products'])
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->get();
            
        // This month's orders
        $startOfMonth = Carbon::now()->startOfMonth();
        $monthlyOrderCount = $this->orders()->whereDate('created_at', '>=', $startOfMonth)->count();
        
        // This year's orders
        $startOfYear = Carbon::now()->startOfYear();
        $yearlyOrderCount = $this->orders()->whereDate('created_at', '>=', $startOfYear)->count();
        
        // Total counts
        $totalNewCount = $this->orders()->where('status', 'new')->count();
        $totalPreparingCount = $this->orders()->where('status', 'preparing')->count();
        $totalReadyCount = $this->orders()->where('status', 'ready')->count();
        $totalDeliveredCount = $this->orders()->where('status', 'delivered')->count();
        $totalOrders = $totalNewCount + $totalPreparingCount + $totalReadyCount + $totalDeliveredCount;
        
        // Customer and product counts
        $customerCount = Customer::count();
        $productCount = Product::count();
        $lowStockCount = Product::where('stock', '<', 10)->count();
        
        // Recent orders
        $recentOrders = $this->orders()->with(['customer', 'products'])
            ->latest('created_at')
            ->take(5)
            ->get();
            
        // Monthly order trends (current year)
        $currentYear = Carbon::now()->year;
        $monthlyOrders = $this->orders()->select(
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
        $calendarEvents = $this->orders()->with(['customer', 'products'])
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
                    'record_type' => 'order',
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
        
        // Calendar events - pickups on their requested pickup date
        $pickupEvents = $this->pickups()->with(['customer', 'items.product'])
            ->where('status', '!=', Pickup::STATUS_CANCELLED)
            ->get()
            ->map(function ($pickup) {
                $statusColors = [
                    Pickup::STATUS_NEW        => '#f8f9fa',
                    Pickup::STATUS_ON_THE_WAY => '#f1b44c',
                    Pickup::STATUS_PICKED_UP  => '#405189',
                    Pickup::STATUS_RECEIVED   => '#0ab39c',
                ];

                $textColors = [
                    Pickup::STATUS_NEW        => '#212529',
                    Pickup::STATUS_ON_THE_WAY => '#ffffff',
                    Pickup::STATUS_PICKED_UP  => '#ffffff',
                    Pickup::STATUS_RECEIVED   => '#ffffff',
                ];

                $itemList = $pickup->items->map(function ($item) {
                    return ($item->product->name ?? 'Unknown item') . ' (Qty: ' . $item->quantity . ')';
                })->toArray();

                $backgroundColor = $statusColors[$pickup->status] ?? '#6c757d';
                $textColor = $textColors[$pickup->status] ?? '#ffffff';

                if ($pickup->time_sensitive) {
                    $backgroundColor = '#dc3545';
                    $textColor = '#ffffff';
                }

                return [
                    'id' => $pickup->id,
                    'title' => $pickup->reference_no . ' - ' . ($pickup->customer->name ?? 'N/A'),
                    'start' => $pickup->pickup_date->format('Y-m-d'),
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $backgroundColor,
                    'textColor' => $textColor,
                    // Read by the calendar to pick the right link and tooltip.
                    'record_type' => 'pickup',
                    'reference' => $pickup->reference_no,
                    'status' => $pickup->statusLabel(),
                    'customer' => $pickup->customer->name ?? 'N/A',
                    'products_count' => $pickup->items->count(),
                    'products_list' => $itemList,
                    'delivery_type' => 'Pickup',
                    'delivery_time' => $pickup->pickup_time ? $pickup->pickup_time->format('H:i') : null,
                    'time_sensitive' => (bool) $pickup->time_sensitive,
                ];
            });

        // Orders and pickups share one calendar.
        $calendarEvents = $calendarEvents->concat($pickupEvents)->values();

        // Upcoming deliveries (today and tomorrow) - exclude delivered and canceled orders
        $upcomingDeliveries = $this->orders()->with(['customer', 'products'])
            ->whereNotNull('pickup_delivery_date')
            ->whereBetween('pickup_delivery_date', [Carbon::now()->startOfDay(), Carbon::now()->addDay()->endOfDay()])
            ->whereNotIn('status', ['delivered', 'canceled'])
            ->orderBy('pickup_delivery_date')
            ->get();
            
        // Overdue deliveries - orders that passed delivery date but not delivered
        $overdueDeliveries = $this->orders()->with(['customer', 'products'])
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