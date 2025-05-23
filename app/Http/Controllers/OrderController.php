<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Mail\NewOrderNotification;
use App\Mail\OrderReadyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Mail\ProductUpdateNotification;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['customer', 'user'])->latest()->paginate(10);
        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::all();
        $products = Product::where('stock', '>', 0)->get();
        $users = User::all();
        return view('orders.create', compact('customers', 'products', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Start a database transaction for data consistency
        DB::beginTransaction();
        
        try {
            // Validate the request
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'order_placed_by' => 'nullable|string|max:255',
                'order_date' => 'required|date',
                'order_time' => 'nullable|date_format:H:i',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.batch_number' => 'nullable|string',
                'products.*.patient_name' => 'nullable|string',
                'products.*.remarks' => 'nullable|string',
                'delivery_time' => 'nullable|date',
                'status' => 'required|in:new,preparing,ready,delivered',
                'remarks' => 'nullable|string',
            ]);

            // Create the order
            $order = new Order([
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'order_placed_by' => $request->order_placed_by,
                'order_date' => $request->order_date,
                'order_time' => Carbon::parse($request->order_time ?? now())->toTimeString(),
                'status' => $request->status,
                'delivery_date' => $request->delivery_time ? Carbon::parse($request->delivery_time)->toDateString() : null,
                'delivery_time' => $request->delivery_time ? Carbon::parse($request->delivery_time)->toTimeString() : null,
                'remarks' => $request->remarks,
            ]);
            $order->save();

            // Attach products to the order
            foreach ($request->products as $productData) {
                // Get product
                $product = Product::findOrFail($productData['id']);
                
                // Check if there's enough stock
                if ($product->stock < $productData['quantity']) {
                    throw new \Exception("Not enough stock for product: {$product->name}. Available: {$product->stock}");
                }
                
                // Decrease stock
                $product->stock -= $productData['quantity'];
                $product->save();
                
                // Attach product to order with all data
                $order->products()->attach($product->id, [
                    'quantity' => $productData['quantity'],
                    'batch_number' => $productData['batch_number'] ?? null,
                    'patient_name' => $productData['patient_name'] ?? null,
                    'remarks' => $productData['remarks'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('orderdetails', $order->id)->with('success', 'Order created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'user', 'products']);
        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $order->load('products');
        $customers = Customer::all();
        $products = Product::all();
        $users = User::all();
        return view('orders.edit', compact('order', 'customers', 'products', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Begin transaction
        DB::beginTransaction();
        
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'user_id' => 'required|exists:users,id',
                'order_placed_by' => 'nullable|string|max:255',
                'order_date' => 'required|date',
                'order_time' => 'nullable|date_format:H:i',
                'status' => 'required|in:new,preparing,ready,delivered',
                'delivery_date' => 'nullable|date',
                'delivery_time' => 'nullable|date_format:H:i',
                'remarks' => 'nullable|string',
                'products' => 'nullable|array',
                'products.*.id' => 'nullable|exists:products,id',
                'products.*.quantity' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Update order details
            $order->update([
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'order_placed_by' => $request->order_placed_by,
                'order_date' => $request->order_date,
                'order_time' => $request->order_time,
                'status' => $request->status,
                'delivery_date' => $request->delivery_date,
                'delivery_time' => $request->delivery_time,
                'remarks' => $request->remarks,
            ]);
            
            // If products are being updated
            if ($request->has('products')) {
                // First, return stock for all current products
                foreach ($order->products as $existingProduct) {
                    $product = Product::findOrFail($existingProduct->id);
                    $product->stock += $existingProduct->pivot->quantity;
                    $product->save();
                }
                
                // Clear existing products
                $order->products()->detach();
                
                // Add new products and decrease stock
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['id']);
                    
                    // Check if enough stock
                    if ($product->stock < $productData['quantity']) {
                        throw new \Exception("Not enough stock for product: {$product->name}. Available: {$product->stock}");
                    }
                    
                    // Decrease stock
                    $product->stock -= $productData['quantity'];
                    $product->save();
                    
                    // Attach to order
                    $order->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'batch_number' => $productData['batch_number'] ?? null,
                        'patient_name' => $productData['patient_name'] ?? null,
                        'remarks' => $productData['remarks'] ?? null,
                    ]);
                }
            }
            
            DB::commit();

            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'Order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Order $order)
    {
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Return products to stock
            foreach ($order->products as $product) {
                // First load the product to ensure we have the latest stock value
                $freshProduct = Product::findOrFail($product->id);
                $freshProduct->stock += $product->pivot->quantity;
                $freshProduct->save();
            }
            
            // Delete order (pivot relationships will be deleted automatically)
            $order->delete();
            
            DB::commit();
            
            // Check if we're coming from the order history page with a status filter
            if ($request->session()->has('status_filter')) {
                $status = $request->session()->get('status_filter');
                return redirect()->route('orderhistory', ['status' => $status])
                    ->with('success', 'Order deleted successfully.');
            }
            
            return redirect()->route('orders.index')
                ->with('success', 'Order deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error deleting order: ' . $e->getMessage());
        }
    }

    /**
     * Display the order history page.
     */
    public function history(Request $request)
    {
        $query = Order::with(['customer', 'products'])
            ->latest('order_date');
            
        // Filter by status if provided
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
            // Store the status filter in session for redirecting after deleting an order
            $request->session()->put('status_filter', $request->status);
        } else {
            // Clear the status filter from session
            $request->session()->forget('status_filter');
        }
        
        // Only apply date filtering for 'all' or 'delivered' status
        // For 'new', 'preparing', and 'ready' statuses, show all orders regardless of date
        if (!in_array($request->status, ['new', 'preparing', 'ready'])) {
            // Filter by date range - using predefined options
            $dateRange = $request->get('date_range', 'today'); // Default to today if not specified
            
            switch ($dateRange) {
                case 'today':
                    $today = Carbon::today();
                    $query->whereDate('order_date', $today);
                    break;
                    
                case 'weekly':
                    $startOfWeek = Carbon::now()->startOfWeek();
                    $endOfWeek = Carbon::now()->endOfWeek();
                    $query->whereBetween('order_date', [$startOfWeek, $endOfWeek]);
                    break;
                    
                case 'monthly':
                    $startOfMonth = Carbon::now()->startOfMonth();
                    $endOfMonth = Carbon::now()->endOfMonth();
                    $query->whereBetween('order_date', [$startOfMonth, $endOfMonth]);
                    break;
                    
                case 'yearly':
                    $startOfYear = Carbon::now()->startOfYear();
                    $endOfYear = Carbon::now()->endOfYear();
                    $query->whereBetween('order_date', [$startOfYear, $endOfYear]);
                    break;
                    
                case 'all':
                    // No date filtering
                    break;
                    
                default:
                    // If it's a custom date range string (for backwards compatibility)
                    if (strpos($dateRange, ' to ') !== false) {
                        $dates = explode(' to ', $dateRange);
                        if (count($dates) == 2) {
                            $start_date = date('Y-m-d', strtotime($dates[0]));
                            $end_date = date('Y-m-d', strtotime($dates[1]));
                            $query->whereBetween('order_date', [$start_date, $end_date]);
                        }
                    }
                    break;
            }
        }
        
        // Search by ID, customer name, product name, or status
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                  ->orWhereHas('customer', function($query) use ($search) {
                      $query->where('name', 'like', "%$search%");
                  })
                  ->orWhereHas('products', function($query) use ($search) {
                      $query->where('name', 'like', "%$search%");
                  })
                  ->orWhere('status', 'like', "%$search%");
            });
        }
        
        // Apply sorting if provided
        if ($request->has('sort_by') && !empty($request->sort_by)) {
            $sortBy = $request->sort_by;
            $sortOrder = $request->get('sort_order', 'asc');
            
            // Handle sorting based on column
            switch ($sortBy) {
                case 'id':
                    $query->orderBy('id', $sortOrder);
                    break;
                    
                case 'customer_name':
                    $query->join('customers', 'orders.customer_id', '=', 'customers.id')
                          ->orderBy('customers.name', $sortOrder)
                          ->select('orders.*');
                    break;
                    
                case 'product_name':
                    // Sort by first product name - more complex sorting
                    $query->leftJoin('order_product', 'orders.id', '=', 'order_product.order_id')
                          ->leftJoin('products', 'order_product.product_id', '=', 'products.id')
                          ->orderBy('products.name', $sortOrder)
                          ->select('orders.*')
                          ->groupBy('orders.id');
                    break;
                    
                case 'placed_by':
                    $query->orderBy('order_placed_by', $sortOrder);
                    break;
                    
                case 'delivered_by':
                    $query->orderBy('delivered_by', $sortOrder);
                    break;
                
                case 'order_date':
                    // Sort by both date and time
                    $query->orderBy('order_date', $sortOrder)
                          ->orderBy('order_time', $sortOrder);
                    break;
                    
                case 'delivery_time':
                    // Sort by both delivery date and time
                    $query->orderBy('delivery_date', $sortOrder)
                          ->orderBy('delivery_time', $sortOrder);
                    break;
                    
                case 'status':
                    $query->orderBy('status', $sortOrder);
                    break;
                    
                default:
                    // Default to latest order by date and time if no valid sort provided
                    $query->latest('order_date')->latest('order_time');
                    break;
            }
        } else {
            // Default sorting by latest order date and time
            $query->latest('order_date')->latest('order_time');
        }
        
        $orders = $query->paginate(10)->withQueryString();
            
        $newCount = Order::where('status', 'new')->count();
        $preparingCount = Order::where('status', 'preparing')->count();
        $readyCount = Order::where('status', 'ready')->count();
        $deliveredCount = Order::where('status', 'delivered')->count();
        
        return view('orders.orderhistory', compact(
            'orders', 
            'newCount', 
            'preparingCount',
            'readyCount',
            'deliveredCount'
        ));
    }

    /**
     * Display the new order form.
     */
    public function newOrder()
    {
        $customers = Customer::all();
        $products = Product::where('stock', '>', 0)->get();
        $dispatchers = User::all();
        return view('orders.neworder', compact('customers', 'products', 'dispatchers'));
    }

    /**
     * Store a new order from the neworder form.
     */
    public function storeNewOrder(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'order_placed_by' => 'nullable|string|max:255',
            'delivery_date' => 'required|date',
            'delivery_time' => 'required',
            'remarks' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.type' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.patient_name' => 'nullable|string',
            'products.*.remarks' => 'nullable|string',
        ], [
            'customer_name.required' => 'The customer name is required.',
            'customer_phone.required' => 'The customer phone number is required.',
            'customer_address.required' => 'The customer address is required.',
            'delivery_date.required' => 'The delivery date is required.',
            'delivery_time.required' => 'The delivery time is required.',
            'products.required' => 'At least one product is required.',
            'products.min' => 'You need at least one product item.',
            'products.*.type.required' => 'The product type is required for all products.',
            'products.*.quantity.required' => 'The product quantity is required for all products.',
            'products.*.quantity.min' => 'The product quantity must be at least 1.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the errors in the form.');
        }
        
        // Additional validation to ensure products exist
        if (!isset($request->products) || count($request->products) < 1) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'You need at least one product item.');
        }

        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Handle customer (existing or new)
            if ($request->customer_id) {
                // Use existing customer
                $customer_id = $request->customer_id;
            } else {
                // Create new customer
                $customer = new Customer();
                $customer->name = $request->customer_name;
                $customer->email = $request->customer_email;
                $customer->phoneNo = $request->customer_phone;
                $customer->address = $request->customer_address;
                $customer->userID = Auth::id();
                $customer->save();
                
                $customer_id = $customer->id;
            }
            
            // Create new order
            $order = new Order();
            $order->customer_id = $customer_id;
            $order->user_id = Auth::id(); // Current logged in user
            $order->order_placed_by = $request->order_placed_by;
            $order->order_date = now()->toDateString();
            $order->order_time = now()->toTimeString();
            $order->status = 'new';
            $order->delivery_date = $request->delivery_date;
            $order->delivery_time = $request->delivery_time ? Carbon::parse($request->delivery_time)->toTimeString() : null;
            $order->remarks = $request->remarks;
            $order->save();
            
            // Attach products to order
            foreach ($request->products as $product) {
                // Find product by name
                $productModel = Product::where('name', $product['type'])->first();
                
                if (!$productModel) {
                    throw new \Exception("Product not found: {$product['type']}");
                }
                
                // Check stock
                if ($productModel->stock < $product['quantity']) {
                    throw new \Exception("Not enough stock for product: {$productModel->name}. Available: {$productModel->stock}");
                }
                
                // Reduce stock
                $productModel->stock -= $product['quantity'];
                $productModel->save();
                
                // Attach to order with patient name
                $order->products()->attach($productModel->id, [
                    'quantity' => $product['quantity'],
                    'patient_name' => isset($product['patient_name']) ? $product['patient_name'] : null,
                    'remarks' => isset($product['remarks']) ? $product['remarks'] : null,
                ]);
            }
            
            DB::commit();

            // Send email notifications
            $this->sendNewOrderNotifications($order);

            // Redirect to the order details page
            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'Order created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating order: ' . $e->getMessage());
        }
    }

    /**
     * Send email notifications for new orders
     * 
     * @param \App\Models\Order $order
     * @return void
     */
    private function sendNewOrderNotifications(Order $order)
    {
        try {
            // Load order relationships for the email
            $order->load(['customer', 'products']);
            
            // Get users who have opted in to receive new order notifications
            $notificationUsers = User::where('receive_new_order_emails', true)->get();
            
            // Check if we're in test mode
            $testMode = config('mail_debug.test_mode', false);
            if ($testMode) {
                // Use test recipients only
                $testRecipients = config('mail_debug.test_recipients', []);
                if (!empty($testRecipients)) {
                    Log::info('Using test recipients: ' . implode(', ', $testRecipients));
                }
            }
            
            // Create the email
            $mail = new NewOrderNotification($order);
            
            // Track email recipients for logging
            $sentTo = [];
            
            // Try to send via Laravel's mail system first
            try {
                // Check if we should use test SMTP
                $useTestSmtp = config('mail_debug.use_test_smtp', false);
                if ($useTestSmtp) {
                    Log::info('Using test SMTP settings');
                    // Create a custom mailer with test SMTP settings
                    $transport = (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                        config('mail_debug.test_smtp.host'), 
                        config('mail_debug.test_smtp.port'),
                        config('mail_debug.test_smtp.encryption') === 'tls'
                    ))
                    ->setUsername(config('mail_debug.test_smtp.username'))
                    ->setPassword(config('mail_debug.test_smtp.password'));
                    
                    $customMailer = new \Illuminate\Mail\Mailer(
                        'smtp',
                        new \Symfony\Component\Mailer\Mailer($transport),
                        app('view'),
                        app('events')
                    );
                    
                    // Send using custom mailer
                    if ($testMode && !empty($testRecipients)) {
                        foreach ($testRecipients as $recipient) {
                            $customMailer->to($recipient)->send($mail);
                            $sentTo[] = $recipient . ' (Test Recipient)';
                        }
                    } else {
                        // Send to users who opted in
                        foreach ($notificationUsers as $user) {
                            if ($user->email) {
                                $customMailer->to($user->email)->send($mail);
                                $sentTo[] = $user->email . ' (' . $user->department . ')';
                            }
                        }
                    }
                } else {
                    // Use standard Laravel mail
                    if ($testMode && !empty($testRecipients)) {
                        foreach ($testRecipients as $recipient) {
                            Mail::to($recipient)->send($mail);
                            $sentTo[] = $recipient . ' (Test Recipient)';
                        }
                    } else {
                        // Send to users who opted in
                        foreach ($notificationUsers as $user) {
                            if ($user->email) {
                                Mail::to($user->email)->send($mail);
                                $sentTo[] = $user->email . ' (' . $user->department . ')';
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Check if we should use PHP mail() as fallback
                $usePhpMailFallback = config('mail_debug.use_php_mail_fallback', true);
                if (!$usePhpMailFallback) {
                    // Re-throw the exception if we don't want to use fallback
                    throw $e;
                }
                
                // If Laravel mail fails, try PHP mail() as fallback
                Log::warning('Laravel mail failed, trying PHP mail() as fallback: ' . $e->getMessage());
                
                // Generate email content
                $subject = '[ACTION REQUIRED] New Order #' . $order->id . ' - Delivery: ' . \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y');
                
                // Using view() to render the email template to a string
                $htmlContent = view('emails.new-order', ['order' => $order])->render();
                
                // Log email content if enabled
                if (config('mail_debug.log_email_content', false)) {
                    Log::info('Email HTML content: ' . substr($htmlContent, 0, 500) . '... [truncated]');
                }
                
                // Basic email headers
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: MGRC Order System <support@mgrc.com.my>\r\n";
                $headers .= "X-Priority: 1\r\n";
                $headers .= "X-MSMail-Priority: High\r\n";
                $headers .= "Importance: High\r\n";
                
                if ($testMode && !empty($testRecipients)) {
                    foreach ($testRecipients as $recipient) {
                        $sent = mail($recipient, $subject, $htmlContent, $headers);
                        if ($sent) {
                            $sentTo[] = $recipient . ' (Test Recipient - via PHP mail)';
                        }
                    }
                } else {
                    // Send to users who opted in
                    foreach ($notificationUsers as $user) {
                        if ($user->email) {
                            $sent = mail($user->email, $subject, $htmlContent, $headers);
                            if ($sent) {
                                $sentTo[] = $user->email . ' (' . $user->department . ' - via PHP mail)';
                            }
                        }
                    }
                }
            }
            
            // Log successful email sending
            if (count($sentTo) > 0) {
                Log::info('Order #' . $order->id . ' notification emails sent to: ' . implode(', ', $sentTo));
            } else {
                Log::warning('No notification emails sent for Order #' . $order->id . ': No recipients found or all mail methods failed');
            }
        } catch (\Exception $e) {
            // Log detailed error but don't stop the process
            Log::error('Failed to send order notification email for Order #' . $order->id . ': ' . $e->getMessage(), [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Show the form for editing batch information.
     */
    public function editBatchInfo($id)
    {
        $order = Order::with(['customer', 'products'])->findOrFail($id);
        $products = Product::all();
        return view('orders.batch-edit', compact('order', 'products'));
    }
    
    /**
     * Update batch information for all products in an order
     */
    public function updateBatchInfo(Request $request, $id)
    {
        $order = Order::with(['customer', 'products'])->findOrFail($id);
        
        // Create custom validation rules
        $rules = [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1', // Make quantity required
            'products.*.patient_name' => 'nullable|string',
            'products.*.remarks' => 'nullable|string',
            'products.*.qc_document_number' => 'nullable|string',
            'products.*.prepared_by' => 'nullable|string',
        ];
        
        // For each product, determine if batch_number is required or nullable
        foreach ($request->products as $index => $productData) {
            // Make batch_number nullable for all products (no longer required)
            $rules['products.' . $index . '.batch_number'] = 'nullable|string';
        }
        
        // Validate with the dynamic rules
        $validated = $request->validate($rules);
        
        $user = Auth::user();
        $canEditAll = $user->role === 'admin' || $user->role === 'superadmin';
        
        // Begin transaction to ensure data consistency
        DB::beginTransaction();
        
        try {
            $hasBatchInfo = false;
            $hasErrors = false;
            $errorMessage = '';
            $productUpdates = [];
            
            // Store existing products before updating for comparison
            $existingProducts = $order->products->keyBy('id');
            $existingProductIds = $existingProducts->keys()->toArray();
            $updatedProductIds = collect($request->products)->pluck('product_id')->toArray();
            $updatedProducts = [];
            
            // If admin/superadmin is changing products, handle stock adjustments
            if ($canEditAll && array_diff($existingProductIds, $updatedProductIds)) {
                // Return stock for products being removed
                foreach ($existingProducts as $product) {
                    // Skip if product is not in the updated list
                    if (!in_array($product->id, $updatedProductIds)) {
                        $product->stock += $product->pivot->quantity;
                        $product->save();
                    }
                }
                
                // Clear existing products
                $order->products()->detach();
                
                // We will add new products below
            }
            
            foreach ($request->products as $productData) {
                $productId = $productData['product_id'];
                $quantity = $productData['quantity'];
                
                // Get the product
                $product = Product::findOrFail($productId);
                
                // If admin/superadmin is changing products/quantities
                if ($canEditAll) {
                    // Check if product is new or quantity has changed
                    $isNewProduct = !$existingProducts->has($productId);
                    $existingQuantity = $isNewProduct ? 0 : $existingProducts[$productId]->pivot->quantity;
                    $quantityDiff = $quantity - $existingQuantity;
                    
                    // If quantity is decreasing or product is being removed, add stock back
                    if ($quantityDiff < 0) {
                        $product->stock -= $quantityDiff; // Adding back (negative diff)
                        $product->save();
                    }
                    // If quantity is increasing or product is new, check if there's enough stock
                    elseif ($quantityDiff > 0) {
                        if ($product->stock < $quantityDiff) {
                            throw new \Exception("Not enough stock for product: {$product->name}. Available: {$product->stock}");
                        }
                        
                        // Decrease stock
                        $product->stock -= $quantityDiff;
                        $product->save();
                    }
                    
                    // Track changes for email notification if product is new or quantity changed
                    if ($isNewProduct || $quantityDiff != 0) {
                        $updatedProducts[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity' => $quantity,
                            'batch_number' => $productData['batch_number'] ?? null,
                            'previous_quantity' => $existingQuantity,
                            'is_new' => $isNewProduct
                        ];
                    }
                    
                    // Prepare data for attaching/updating in pivot table
                    $pivotData = [
                        'quantity' => $quantity,
                        'patient_name' => $productData['patient_name'] ?? null,
                        'remarks' => $productData['remarks'] ?? null,
                        'prepared_by' => $productData['prepared_by'] ?? null,
                    ];
                    
                    // Handle batch number permissions
                    if (isset($productData['batch_number']) && (!$isNewProduct && $existingProducts->has($productId))) {
                        $existingBatchNumber = $existingProducts[$productId]->pivot->batch_number;
                        if (!empty($productData['batch_number']) && $existingBatchNumber !== $productData['batch_number']) {
                            if ($user->department === 'Cell Lab' || $user->role === 'superadmin') {
                                $pivotData['batch_number'] = $productData['batch_number'];
                            } else {
                                $hasErrors = true;
                                $errorMessage = 'Only Cell Lab department can edit batch document numbers.';
                                break;
                            }
                        } elseif ($existingBatchNumber) {
                            $pivotData['batch_number'] = $existingBatchNumber;
                        } else {
                            $pivotData['batch_number'] = $productData['batch_number'] ?? null;
                        }
                    } else {
                        $pivotData['batch_number'] = $productData['batch_number'] ?? null;
                    }
                    
                    // Handle QC document number permissions
                    if (isset($productData['qc_document_number']) && (!$isNewProduct && $existingProducts->has($productId))) {
                        $existingQcNumber = $existingProducts[$productId]->pivot->qc_document_number;
                        if (!empty($productData['qc_document_number']) && $existingQcNumber !== $productData['qc_document_number']) {
                            if ($user->department === 'Quality' || $user->role === 'superadmin') {
                                $pivotData['qc_document_number'] = $productData['qc_document_number'];
                            } else {
                                $hasErrors = true;
                                $errorMessage = 'Only Quality department can edit QC document numbers.';
                                break;
                            }
                        } elseif ($existingQcNumber) {
                            $pivotData['qc_document_number'] = $existingQcNumber;
                        } else {
                            $pivotData['qc_document_number'] = $productData['qc_document_number'] ?? null;
                        }
                    } else {
                        $pivotData['qc_document_number'] = $productData['qc_document_number'] ?? null;
                    }
                    
                    // Check if product exists in order (for admin/superadmin)
                    if ($isNewProduct || array_diff($existingProductIds, $updatedProductIds)) {
                        // Attach new product
                        $order->products()->attach($productId, $pivotData);
                    } else {
                        // Update existing product
                        $order->products()->updateExistingPivot($productId, $pivotData);
                    }
                } else {
                    // Non-admin users - use the original behavior
                    // Get existing pivot data
                    $existingPivot = $order->products()->wherePivot('product_id', $productId)->first();
                    
                    if (!$existingPivot) {
                        $hasErrors = true;
                        $errorMessage = 'You do not have permission to add new products to this order.';
                        break;
                    }
                    
                    $existingPivot = $existingPivot->pivot;
                    
                    $updateData = [
                        'patient_name' => $productData['patient_name'] ?? null,
                        'remarks' => $productData['remarks'] ?? null,
                        'prepared_by' => $productData['prepared_by'] ?? null,
                    ];
                    
                    // Only update batch number if it was submitted and user has permission
                    if (isset($productData['batch_number']) && !empty($productData['batch_number']) && 
                        $existingPivot->batch_number !== $productData['batch_number']) {
                        if ($user->department === 'Cell Lab' || $user->role === 'superadmin') {
                            $updateData['batch_number'] = $productData['batch_number'];
                        } else {
                            $hasErrors = true;
                            $errorMessage = 'Only Cell Lab department can edit batch document numbers.';
                            break;
                        }
                    } elseif ($existingPivot->batch_number) {
                        // Keep existing batch number if present
                        $updateData['batch_number'] = $existingPivot->batch_number;
                    } elseif (isset($productData['batch_number'])) {
                        // Use new batch number if provided and no existing one
                        $updateData['batch_number'] = $productData['batch_number'];
                    }
                    
                    // Check permissions for QC document number
                    if (isset($productData['qc_document_number']) && !empty($productData['qc_document_number']) && 
                        $existingPivot->qc_document_number !== $productData['qc_document_number']) {
                        if ($user->department === 'Quality' || $user->role === 'superadmin') {
                            $updateData['qc_document_number'] = $productData['qc_document_number'];
                        } else {
                            $hasErrors = true;
                            $errorMessage = 'Only Quality department can edit QC document numbers.';
                            break;
                        }
                    } elseif ($existingPivot->qc_document_number) {
                        // Keep existing QC document number if present
                        $updateData['qc_document_number'] = $existingPivot->qc_document_number;
                    } elseif (isset($productData['qc_document_number'])) {
                        // Use new QC document number if provided and no existing one
                        $updateData['qc_document_number'] = $productData['qc_document_number'];
                    }
                    
                    if (!$hasErrors) {
                        // Check if any fields changed (for non-admin users)
                        $existingPivot = $order->products()->wherePivot('product_id', $productId)->first()->pivot;
                        $hasChanges = false;
                        
                        // Check each field for changes - but we won't send notifications for these changes
                        // Just update the data without tracking for notifications
                        $order->products()->updateExistingPivot($productId, $updateData);
                    }
                }
                
                // Check if any batch information exists
                if (!empty($productData['batch_number']) || 
                    !empty($productData['qc_document_number']) || 
                    !empty($productData['prepared_by'])) {
                    $hasBatchInfo = true;
                }
            }
            
            if ($hasErrors) {
                DB::rollBack();
                return redirect()->back()->with('error', $errorMessage);
            }
            
            // Update order status to "preparing" if it's still "new" and batch information exists
            if ($order->status === 'new' && $hasBatchInfo) {
                $order->update(['status' => 'preparing']);
            }
            
            // Send email notification if products or quantities were updated
            if (!empty($updatedProducts)) {
                // Filter to only include products or quantity changes (is_new or quantity diff)
                $productsWithChanges = array_filter($updatedProducts, function($product) {
                    return $product['is_new'] === true || 
                           (isset($product['previous_quantity']) && $product['quantity'] != $product['previous_quantity']);
                });
                
                // Only send notification if there are product additions or quantity changes
                if (!empty($productsWithChanges)) {
                    // Send product update email for all changed products
                    $this->sendProductUpdateNotification($order, $productsWithChanges);
                }
            }
            
            DB::commit();
            
            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'Batch information updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating batch information: ' . $e->getMessage());
        }
    }

    /**
     * Send notification email when products or quantities are updated.
     */
    private function sendProductUpdateNotification(Order $order, array $updatedProducts)
    {
        try {
            // Skip if no changes were made
            if (empty($updatedProducts)) {
                return;
            }
            
            // Reload order with relations
            $order->load(['customer', 'user']);
            
            // Get users who have opted in to receive new order notifications
            // Using the same recipients list as new order emails
            $notificationUsers = User::where('receive_new_order_emails', true)->get();
            
            // Check if we're in test mode
            $testMode = config('mail_debug.test_mode', false);
            if ($testMode) {
                // Use test recipients only
                $testRecipients = config('mail_debug.test_recipients', []);
                if (!empty($testRecipients)) {
                    Log::info('Using test recipients: ' . implode(', ', $testRecipients));
                }
            }
            
            // Create the email
            $mail = new ProductUpdateNotification($order, $updatedProducts);
            
            // Track email recipients for logging
            $sentTo = [];
            
            // Try to send via Laravel's mail system first
            try {
                // Check if we should use test SMTP
                $useTestSmtp = config('mail_debug.use_test_smtp', false);
                if ($useTestSmtp) {
                    Log::info('Using test SMTP settings');
                    // Create a custom mailer with test SMTP settings
                    $transport = (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                        config('mail_debug.test_smtp.host'), 
                        config('mail_debug.test_smtp.port'),
                        config('mail_debug.test_smtp.encryption') === 'tls'
                    ))
                    ->setUsername(config('mail_debug.test_smtp.username'))
                    ->setPassword(config('mail_debug.test_smtp.password'));
                    
                    $customMailer = new \Illuminate\Mail\Mailer(
                        'smtp',
                        new \Symfony\Component\Mailer\Mailer($transport),
                        app('view'),
                        app('events')
                    );
                    
                    // Send using custom mailer
                    if ($testMode && !empty($testRecipients)) {
                        foreach ($testRecipients as $recipient) {
                            $customMailer->to($recipient)->send($mail);
                            $sentTo[] = $recipient . ' (Test Recipient)';
                        }
                    } else {
                        // Send to users who opted in
                        foreach ($notificationUsers as $user) {
                            if ($user->email) {
                                $customMailer->to($user->email)->send($mail);
                                $sentTo[] = $user->email . ' (' . $user->department . ')';
                            }
                        }
                    }
                } else {
                    // Use standard Laravel mail
                    if ($testMode && !empty($testRecipients)) {
                        foreach ($testRecipients as $recipient) {
                            Mail::to($recipient)->send($mail);
                            $sentTo[] = $recipient . ' (Test Recipient)';
                        }
                    } else {
                        // Send to users who opted in
                        foreach ($notificationUsers as $user) {
                            if ($user->email) {
                                Mail::to($user->email)->send($mail);
                                $sentTo[] = $user->email . ' (' . $user->department . ')';
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't interrupt the process
                Log::error('Error sending product update notification: ' . $e->getMessage());
            }
            
            // Log successful email sending
            if (count($sentTo) > 0) {
                Log::info('Product update notification emails sent to: ' . implode(', ', $sentTo));
            } else {
                Log::warning('No product update notification emails sent: No recipients found or all mail methods failed');
            }
        } catch (\Exception $e) {
            // Log error but don't interrupt the process
            Log::error('Error sending product update notification: ' . $e->getMessage());
        }
    }

    /**
     * Display the order details page.
     */
    public function orderDetails(Order $order)
    {
        $order->load(['customer', 'user', 'products']);
        return view('orders.orderdetails', compact('order'));
    }

    /**
     * Update the batch information for order products.
     */
    public function updateBatch(Request $request, $id)
    {
        $request->validate([
            'batch_number' => 'nullable|string',
            'product_id' => 'required|exists:products,id',
            'patient_name' => 'nullable|string',
            'remarks' => 'nullable|string',
            'qc_document_number' => 'nullable|string',
            'prepared_by' => 'nullable|string',
        ]);

        // Begin transaction
        DB::beginTransaction();
        
        try {
            $order = Order::findOrFail($id);
            $user = Auth::user();
            
            // Get existing pivot data
            $existingPivot = $order->products()->wherePivot('product_id', $request->product_id)->first()->pivot;
            
            $updateData = [
                'patient_name' => $request->patient_name,
                'remarks' => $request->remarks,
                'prepared_by' => $request->prepared_by,
            ];
            
            // Check permissions for batch number
            if ($request->filled('batch_number') && $existingPivot->batch_number !== $request->batch_number) {
                if ($user->department === 'Cell Lab' || $user->role === 'superadmin') {
                    $updateData['batch_number'] = $request->batch_number;
                } else {
                    return redirect()->back()->with('error', 'Only Cell Lab department can edit batch document numbers.');
                }
            } else {
                $updateData['batch_number'] = $existingPivot->batch_number;
            }
            
            // Check permissions for QC document number
            if ($request->filled('qc_document_number') && $existingPivot->qc_document_number !== $request->qc_document_number) {
                if ($user->department === 'Quality' || $user->role === 'superadmin') {
                    $updateData['qc_document_number'] = $request->qc_document_number;
                } else {
                    return redirect()->back()->with('error', 'Only Quality department can edit QC document numbers.');
                }
            } else {
                $updateData['qc_document_number'] = $existingPivot->qc_document_number;
            }
            
            // Update the pivot table
            $order->products()->updateExistingPivot($request->product_id, $updateData);

            // Update order status to "preparing" if it's still "new" 
            // and any of these fields are filled
            if ($order->status === 'new' && 
                (!empty($updateData['batch_number']) || !empty($updateData['qc_document_number']) || !empty($updateData['prepared_by']))) {
                $order->update(['status' => 'preparing']);
            }
            
            DB::commit();
            
            return redirect()->back()->with('success', 'Batch information updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating batch information: ' . $e->getMessage());
        }
    }

    /**
     * Update the delivery information for an order.
     */
    public function updateDelivery(Request $request, $id)
    {
        $request->validate([
            'dispatcher' => 'required|string',
            'delivery_datetime' => 'required|string',
        ]);

        // Parse the datetime
        $dateTime = Carbon::createFromFormat('d.m.Y H:i', $request->delivery_datetime);
        
        $order = Order::findOrFail($id);
        $order->status = 'delivered';
        $order->delivery_date = $dateTime->toDateString();
        $order->delivery_time = $dateTime->toTimeString();
        $order->delivered_by = $request->dispatcher;
        $order->save();

        return redirect()->back()->with('success', 'Order marked as delivered successfully!');
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:new,preparing,ready,delivered',
        ]);

        // Begin transaction
        DB::beginTransaction();
        
        try {
            $order = Order::findOrFail($id);
            $user = Auth::user();
            
            // Store original status to detect changes
            $oldStatus = $order->status;
            
            // Check permissions for status changes
            if ($request->status === 'preparing' && $order->status !== 'preparing') {
                if (!($user->department === 'Quality' || $user->department === 'Cell Lab' || $user->role === 'admin' || $user->role === 'superadmin')) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Only Quality, Cell Lab, Admin or Superadmin can mark orders as preparing.');
                }
            }
            
            if ($request->status === 'ready' && $order->status !== 'ready') {
                if ($user->department !== 'Quality' && $user->department !== 'Cell Lab' && $user->role !== 'admin' && $user->role !== 'superadmin') {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Only Quality or Cell Lab departments can mark orders as ready.');
                }
            }
            
            if ($request->status === 'delivered' && $order->status !== 'delivered') {
                if ($user->department !== 'Admin & Human Resource' && $user->role !== 'admin' && $user->role !== 'superadmin') {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Only Admin department can mark orders as delivered.');
                }
                
                // Additional validation for delivered status
                $request->validate([
                    'dispatcher' => 'required|string',
                    'delivery_datetime' => 'required|string',
                ]);

                // Parse the datetime
                $dateTime = Carbon::createFromFormat('d.m.Y H:i', $request->delivery_datetime);
                
                $order->delivery_date = $dateTime->toDateString();
                $order->delivery_time = $dateTime->toTimeString();
                $order->delivered_by = $request->dispatcher;
            }
            
            $order->status = $request->status;
            $order->save();
            
            // If the status changed to 'ready', send notifications
            if ($oldStatus !== 'ready' && $request->status === 'ready') {
                $order->load(['customer', 'products']);
                $this->sendOrderReadyNotifications($order);
            }
            
            // If the status changed to 'new', send new order notifications 
            if ($oldStatus !== 'new' && $request->status === 'new') {
                $order->load(['customer', 'products']);
                $this->sendNewOrderNotifications($order);
            }
            
            DB::commit();

            $statusMessage = ucfirst($request->status);
            return redirect()->back()->with('success', "Order marked as {$statusMessage} successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating order status: ' . $e->getMessage());
        }
    }

    /**
     * Mark an order as ready.
     * This method is specifically for marking orders as ready with department permissions.
     */
    public function markReady(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('customer', 'products')->findOrFail($id);
            
            // Check if order is already ready
            if ($order->status === 'ready') {
                return redirect()->back()->with('info', 'Order is already marked as Ready.');
            }
            
            // Only allow marking as ready if current status is preparing
            if ($order->status !== 'preparing') {
                return redirect()->back()->with('error', 'Only orders in preparing status can be marked as Ready.');
            }
            
            // Update the status to Ready
            $order->status = 'ready';
            $order->save();
            
            // Send email notifications to users who opted in
            $this->sendOrderReadyNotifications($order);
            
            DB::commit();

            return redirect()->back()->with('success', 'Order marked as Ready successfully! Email notifications sent to designated recipients.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking order as ready: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating order status: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications to users who opted in for order ready notifications.
     * 
     * @param Order $order
     * @return void
     */
    private function sendOrderReadyNotifications(Order $order)
    {
        try {
            // Get users who have opted in to receive order ready notifications
            $notificationUsers = User::where('receive_order_ready_emails', true)->get();
            
            // If no users found who opted in, default to admins as fallback
            if ($notificationUsers->isEmpty()) {
                $notificationUsers = User::whereIn('role', ['superadmin', 'admin'])->get();
            }
            
            // Create the email notification
            $mail = new OrderReadyNotification($order);
            
            // Send to all users who opted in
            foreach ($notificationUsers as $user) {
                if ($user->email) {
                    try {
                        Mail::to($user->email)->send($mail);
                        Log::info('Order ready notification sent to: ' . $user->email);
                    } catch (\Exception $e) {
                        Log::error('Failed to send order ready email to ' . $user->email . ': ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending order ready notifications: ' . $e->getMessage());
        }
    }

    /**
     * Update product ready status
     */
    public function updateProductReadyStatus(Request $request, $orderId, $productId)
    {
        try {
            // Log the raw request for debugging
            Log::info('Raw request data for product ready status update', [
                'all_request_data' => $request->all(),
                'request_method' => $request->method(),
                'request_url' => $request->url(),
                'route_parameters' => $request->route()->parameters(),
                'headers' => $request->header(),
            ]);
            
            $order = Order::findOrFail($orderId);
            
            // Add debug logging
            Log::info('Product ready status update request', [
                'order_id' => $orderId,
                'product_id' => $productId,
                'status' => $request->status,
                'current_order_status' => $order->status,
                'user' => Auth::user()->name . ' (' . Auth::user()->department . ')',
            ]);
            
            // Check if user has permission
            if (!(Auth::user()->department === 'Quality' || 
                Auth::user()->department === 'Cell Lab' || 
                Auth::user()->role === 'admin' || 
                Auth::user()->role === 'superadmin')) {
                Log::warning('Permission denied for product ready status update');
                
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action.'], 403);
                }
                
                return redirect()->back()->with('error', 'You do not have permission to perform this action.');
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:ready,not_ready',
            ]);
            
            if ($validator->fails()) {
                Log::warning('Validation failed for product ready status update', [
                    'errors' => $validator->errors()->toArray(),
                    'input' => $request->all()
                ]);
                
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())], 422);
                }
                
                return redirect()->back()->with('error', 'Validation failed: ' . implode(', ', $validator->errors()->all()));
            }
            
            // Log before update
            Log::info('Before updating pivot', [
                'product_current_status' => $order->products()->where('product_id', $productId)->first()->pivot->status ?? 'not found'
            ]);
            
            // Update the product's ready status
            $updated = $order->products()->updateExistingPivot($productId, [
                'status' => $request->status,
            ]);
            
            Log::info('Pivot update result', [
                'updated' => $updated,
                'status_value' => $request->status
            ]);
            
            // Force refresh the relationship
            $order->load('products');
            
            // Check if the specific product was updated
            $updatedProduct = $order->products()->where('product_id', $productId)->first();
            Log::info('Updated product check', [
                'product_id' => $productId,
                'found' => $updatedProduct ? true : false,
                'status' => $updatedProduct ? $updatedProduct->pivot->status : 'N/A'
            ]);
            
            // Check if all products are ready after this update
            $allProductsReady = true;
            $readyCount = 0;
            $totalProducts = count($order->products);
            
            foreach($order->products as $product) {
                if($product->pivot->status === 'ready') {
                    $readyCount++;
                } else {
                    $allProductsReady = false;
                }
            }
            
            // Log the status of products
            Log::info('Product ready status after update', [
                'ready_count' => $readyCount,
                'total_products' => $totalProducts,
                'all_ready' => $allProductsReady,
                'products' => $order->products->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'status' => $p->pivot->status
                    ];
                })
            ]);
            
            // Auto-update order status if all products are ready
            if ($allProductsReady && $totalProducts > 0 && $order->status === 'preparing') {
                // Update the order status to Ready
                $order->status = 'ready';
                $order->save();
                
                Log::info('Order status automatically updated to Ready', [
                    'order_id' => $order->id,
                    'previous_status' => 'preparing',
                    'new_status' => 'ready'
                ]);
                
                // Send email notifications
                $this->sendOrderReadyNotifications($order);
                
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true, 
                        'message' => 'All products are now ready! Order status has been automatically updated to Ready.',
                        'order_status' => 'ready',
                        'all_ready' => true
                    ]);
                }
                
                return redirect()->route('orderdetails', $order->id)
                    ->with('success', 'All products are now ready! Order status has been automatically updated to Ready.');
            }
            
            $statusMessage = $request->status === 'ready' ? 'Product marked as ready.' : 'Product marked as not ready.';
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => $statusMessage,
                    'order_status' => $order->status,
                    'all_ready' => $allProductsReady,
                    'ready_count' => $readyCount,
                    'total_products' => $totalProducts
                ]);
            }
            
            return redirect()->route('orderdetails', $order->id)->with('success', $statusMessage);
            
        } catch (\Exception $e) {
            Log::error('Error in updateProductReadyStatus: ' . $e->getMessage(), [
                'order_id' => $orderId ?? null,
                'product_id' => $productId ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error updating product status: ' . $e->getMessage()], 500);
            }
            
            return redirect()->back()->with('error', 'Error updating product status: ' . $e->getMessage());
        }
    }

    /**
     * For testing email notifications
     */
    public function testEmailNotification()
    {
        $results = [
            'status' => 'testing',
            'laravel_mail' => [],
            'php_mail' => [],
            'users_new_order' => [],
            'users_ready_order' => [],
            'errors' => [],
            'mail_config' => []
        ];
        
        try {
            // Get the latest order for testing
            $order = Order::with(['customer', 'products'])->latest()->first();
            
            if (!$order) {
                $results['errors'][] = 'No orders found to test with.';
                $results['status'] = 'error';
                return $results;
            }
            
            $results['order_found'] = 'Using Order #' . $order->id;
            
            // Get users who have opted-in to receive email notifications
            $newOrderUsers = User::where('receive_new_order_emails', true)->get();
            $readyOrderUsers = User::where('receive_order_ready_emails', true)->get();
            
            // Record user info
            foreach ($newOrderUsers as $user) {
                $results['users_new_order'][] = [
                    'email' => $user->email,
                    'name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                    'department' => $user->department
                ];
            }
            
            foreach ($readyOrderUsers as $user) {
                $results['users_ready_order'][] = [
                    'email' => $user->email,
                    'name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                    'department' => $user->department
                ];
            }
            
            // Record mail configuration
            $results['mail_config'] = [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name')
            ];
            
            // Create the emails
            $newOrderMail = new NewOrderNotification($order);
            $readyOrderMail = new OrderReadyNotification($order);
            
            // Send using Laravel Mail
            try {
                // Test new order emails
                foreach ($newOrderUsers as $user) {
                    if ($user->email) {
                        try {
                            Mail::to($user->email)->send($newOrderMail);
                            $results['laravel_mail'][] = 'New Order email sent to ' . $user->email . ' (' . $user->department . ')';
                        } catch (\Exception $e) {
                            $results['errors'][] = 'Laravel mail to ' . $user->email . ' failed: ' . $e->getMessage();
                        }
                    }
                }
                
                // Test ready order emails
                foreach ($readyOrderUsers as $user) {
                    if ($user->email) {
                        try {
                            Mail::to($user->email)->send($readyOrderMail);
                            $results['laravel_mail'][] = 'Order Ready email sent to ' . $user->email . ' (' . $user->department . ')';
                        } catch (\Exception $e) {
                            $results['errors'][] = 'Laravel mail to ' . $user->email . ' failed: ' . $e->getMessage();
                        }
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = 'Laravel mail system error: ' . $e->getMessage();
            }
            
            // Try PHP mail() as fallback if no emails sent via Laravel Mail
            if (empty($results['laravel_mail']) && function_exists('mail')) {
                $subject = '[TEST] Order #' . $order->id . ' - ' . date('Y-m-d H:i:s');
                $newOrderHtml = view('emails.new-order', ['order' => $order])->render();
                $readyOrderHtml = view('emails.order-ready', ['order' => $order])->render();
                
                // Basic email headers
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: MGRC Order System <support@mgrc.com.my>\r\n";
                $headers .= "X-Priority: 1\r\n";
                
                // Test new order emails with PHP mail()
                foreach ($newOrderUsers as $user) {
                    if ($user->email) {
                        $sent = @mail($user->email, '[TEST] New Order #' . $order->id, $newOrderHtml, $headers);
                        if ($sent) {
                            $results['php_mail'][] = 'New Order email sent to ' . $user->email . ' (' . $user->department . ')';
                        } else {
                            $results['errors'][] = 'PHP mail() to ' . $user->email . ' failed';
                        }
                    }
                }
                
                // Test ready order emails with PHP mail()
                foreach ($readyOrderUsers as $user) {
                    if ($user->email) {
                        $sent = @mail($user->email, '[TEST] Order Ready #' . $order->id, $readyOrderHtml, $headers);
                        if ($sent) {
                            $results['php_mail'][] = 'Order Ready email sent to ' . $user->email . ' (' . $user->department . ')';
                        } else {
                            $results['errors'][] = 'PHP mail() to ' . $user->email . ' failed';
                        }
                    }
                }
            }
            
            // Check overall status
            if (count($results['laravel_mail']) > 0 || count($results['php_mail']) > 0) {
                $results['status'] = 'success';
            } else if (count($results['errors']) > 0) {
                $results['status'] = 'error';
            } else {
                $results['status'] = 'no_recipients';
                $results['errors'][] = 'No emails sent: No users have opted in to receive notifications or all mail methods failed';
            }
            
            // Log result
            Log::info('Email test run', $results);
            
            return $results;
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['errors'][] = 'Critical error: ' . $e->getMessage();
            Log::error('Email test critical error: ' . $e->getMessage());
            return $results;
        }
    }
}
