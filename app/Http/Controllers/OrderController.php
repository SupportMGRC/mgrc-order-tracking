<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

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
            $validator = Validator::make($request->all(), [
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
                'delivery_type' => 'required|in:delivery,self_collect',
                'pickup_delivery_date' => 'required|date',
                'pickup_delivery_time' => 'required|date_format:H:i',
                'status' => 'required|in:new,preparing,ready,delivered,cancel',
                'remarks' => 'nullable|string',
                'delivery_address' => 'required_if:delivery_type,delivery|nullable|string|max:255',
            ], [
                'customer_id.required' => 'The customer ID is required.',
                'order_placed_by.max' => 'The order placed by name must not exceed 255 characters.',
                'order_date.required' => 'The order date is required.',
                'order_time.date_format' => 'The order time must be a valid time format.',
                'products.required' => 'At least one product is required.',
                'products.min' => 'You need at least one product item.',
                'products.*.id.required' => 'The product ID is required.',
                'products.*.quantity.required' => 'The product quantity is required.',
                'products.*.quantity.min' => 'The product quantity must be at least 1.',
                'delivery_type.required' => 'The delivery type is required.',
                'pickup_delivery_date.required' => 'The pickup delivery date is required.',
                'pickup_delivery_time.required' => 'The pickup delivery time is required.',
                'status.required' => 'The order status is required.',
                'delivery_address.required_if' => 'The delivery address is required for delivery orders.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Create the order
            $order = new Order([
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'order_placed_by' => $request->order_placed_by,
                'order_date' => $request->order_date,
                'order_time' => Carbon::parse($request->order_time ?? now())->toTimeString(),
                'status' => $request->status,
                'delivery_type' => $request->delivery_type,
                'pickup_delivery_date' => $request->pickup_delivery_date,
                'pickup_delivery_time' => Carbon::parse($request->pickup_delivery_time)->toTimeString(),
                'remarks' => $request->remarks,
                'delivery_address' => $request->delivery_address,
            ]);
            $order->save();

            // Attach products to the order
            foreach ($request->products as $productData) {
                $product = Product::findOrFail($productData['id']);
                
                // Check if enough stock
                if ($product->stock < $productData['quantity']) {
                    throw new \Exception("Not enough stock for product: {$product->name}. Available: {$product->stock}");
                }
                
                // Decrease stock
                $product->stock -= $productData['quantity'];
                $product->save();
                
                // Create single record with actual quantity
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
                'status' => 'required|in:new,preparing,ready,delivered,cancel',
                'delivery_type' => 'required|in:delivery,self_collect',
                'pickup_delivery_date' => 'required|date',
                'pickup_delivery_time' => 'required|date_format:H:i',
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
                'delivery_type' => $request->delivery_type,
                'pickup_delivery_date' => $request->pickup_delivery_date,
                'pickup_delivery_time' => $request->pickup_delivery_time,
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
                    
                    // Create single record with actual quantity
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
            
            // Default to order history page for "All Orders" section
            return redirect()->route('orderhistory')
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
            $dateRange = $request->get('date_range', 'all'); // Default to all if not specified
            
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
        
        // Filter by reach client date if provided
        if ($request->has('reach_client_date') && !empty($request->reach_client_date)) {
            $reachClientDate = $request->reach_client_date;
            $query->whereDate('pickup_delivery_date', $reachClientDate);
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
            'pickup_delivery_date' => 'required|date',
            'pickup_delivery_time' => 'required',
            'delivery_type' => 'required|in:delivery,self_collect',
            'remarks' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.type' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.patient_name' => 'nullable|string',
            'products.*.remarks' => 'nullable|string',
            'item_ready_at' => 'required|date_format:g:i A',
        ], [
            'customer_name.required' => 'The customer name is required.',
            'customer_phone.required' => 'The customer phone number is required.',
            'customer_address.required' => 'The customer address is required.',
            'pickup_delivery_date.required' => 'The delivery date is required.',
            'pickup_delivery_time.required' => 'The delivery time is required.',
            'products.required' => 'At least one product is required.',
            'products.min' => 'You need at least one product item.',
            'products.*.type.required' => 'The product type is required for all products.',
            'products.*.quantity.required' => 'The product quantity is required for all products.',
            'products.*.quantity.min' => 'The product quantity must be at least 1.',
            'item_ready_at.required' => 'The item ready time is required.',
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
            $order->pickup_delivery_date = $request->pickup_delivery_date;
            $order->pickup_delivery_time = $request->pickup_delivery_time ? Carbon::parse($request->pickup_delivery_time)->toTimeString() : null;
            $order->delivery_type = $request->delivery_type;
            $order->remarks = $request->remarks;
            $order->delivery_address = $request->delivery_address;
            if ($request->filled('item_ready_at')) {
                $order->item_ready_at = \Carbon\Carbon::createFromFormat('g:i A', $request->item_ready_at)->format('H:i:s');
            }
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
                
                // Create single record with actual quantity
                $order->products()->attach($productModel->id, [
                    'quantity' => $product['quantity'],
                    'patient_name' => isset($product['patient_name']) ? $product['patient_name'] : null,
                    'remarks' => isset($product['remarks']) ? $product['remarks'] : null,
                ]);
            }
            
            DB::commit();

            // Send new order notification emails
            $emailController = new EmailController();
            $emailController->sendNewOrderNotification($order);

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
     * Show the form for editing batch information.
     */
    public function editBatchInfo($id)
    {
        $order = Order::with(['customer', 'products'])->findOrFail($id);
        
        // Debug logging to see what products and pivot data we have
        \Log::info('Loading batch edit form', [
            'order_id' => $order->id,
            'products_count' => $order->products->count(),
            'products_data' => $order->products->map(function($product) {
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'pivot_id' => $product->pivot->id ?? 'NULL',
                    'pivot_data' => $product->pivot ? $product->pivot->toArray() : 'NULL'
                ];
            })
        ]);
        
        $products = Product::all();
        return view('orders.batch-edit', compact('order', 'products'));
    }
    
    /**
     * Update batch information for all products in an order
     */
    public function updateBatchInfo(Request $request, $id)
    {
        // Add debugging logs
        \Log::info('Batch Info Update Request', [
            'request_data' => $request->all(),
            'order_id' => $id
        ]);
        
        $order = Order::with(['customer', 'products'])->findOrFail($id);
        
        // Create custom validation rules
        $rules = [
            'products' => 'required|array',
            'products.*.pivot_id' => 'required|exists:order_product,id',
            'products.*.patient_name' => 'nullable|string',
            'products.*.remarks' => 'nullable|string',
            'products.*.qc_document_number' => 'nullable|string',
            'products.*.prepared_by' => 'nullable|string',
            'products.*.batch_number' => 'nullable|string',
        ];
        
        try {
            // Log validation attempt
            \Log::info('Validating batch info data');
            
            // Validate with the rules
            $validated = $request->validate($rules);
            
            \Log::info('Validation passed', ['validated_data' => $validated]);
            
            $user = Auth::user();
            
            // Begin transaction to ensure data consistency
            DB::beginTransaction();
            
            $hasBatchInfo = false;
            $hasErrors = false;
            $errorMessage = '';
            
            foreach ($request->products as $productData) {
                // Log each product data being processed
                \Log::info('Processing product data', ['product_data' => $productData]);
                
                // Find the pivot record directly
                $pivotRecord = DB::table('order_product')
                    ->where('id', $productData['pivot_id'])
                    ->where('order_id', $order->id)
                    ->first();
                
                if (!$pivotRecord) {
                    $hasErrors = true;
                    $errorMessage = 'Invalid product record.';
                    \Log::error('Pivot record not found', [
                        'pivot_id' => $productData['pivot_id'],
                        'order_id' => $order->id
                    ]);
                    break;
                }
                
                $updateData = [
                    'patient_name' => $productData['patient_name'] ?? null,
                    'remarks' => $productData['remarks'] ?? null,
                    'prepared_by' => $productData['prepared_by'] ?? null,
                ];
                
                // Handle batch number permissions
                if (isset($productData['batch_number']) && !empty($productData['batch_number']) && 
                    $pivotRecord->batch_number !== $productData['batch_number']) {
                    if ($user->department === 'Cell Lab' || $user->department === 'Quality' || $user->role === 'superadmin') {
                        $updateData['batch_number'] = $productData['batch_number'];
                    } else {
                        $hasErrors = true;
                        $errorMessage = 'Only Cell Lab and Quality departments can edit batch numbers.';
                        \Log::warning('Unauthorized batch number update attempt', [
                            'user' => $user->toArray(),
                            'product_data' => $productData
                        ]);
                        break;
                    }
                } elseif ($pivotRecord->batch_number) {
                    $updateData['batch_number'] = $pivotRecord->batch_number;
                } else {
                    $updateData['batch_number'] = $productData['batch_number'] ?? null;
                }
                
                // Handle QC document number permissions
                if (isset($productData['qc_document_number']) && !empty($productData['qc_document_number']) && 
                    $pivotRecord->qc_document_number !== $productData['qc_document_number']) {
                    if ($user->department === 'Quality' || $user->role === 'superadmin') {
                        $updateData['qc_document_number'] = $productData['qc_document_number'];
                    } else {
                        $hasErrors = true;
                        $errorMessage = 'Only Quality department can edit QC document numbers.';
                        \Log::warning('Unauthorized QC document number update attempt', [
                            'user' => $user->toArray(),
                            'product_data' => $productData
                        ]);
                        break;
                    }
                } elseif ($pivotRecord->qc_document_number) {
                    $updateData['qc_document_number'] = $pivotRecord->qc_document_number;
                } else {
                    $updateData['qc_document_number'] = $productData['qc_document_number'] ?? null;
                }
                
                // Log update attempt
                \Log::info('Updating pivot record', [
                    'pivot_id' => $productData['pivot_id'],
                    'update_data' => $updateData
                ]);
                
                // Update the pivot record directly
                DB::table('order_product')
                    ->where('id', $productData['pivot_id'])
                    ->update($updateData);
                
                // Check if any batch information exists
                if (!empty($updateData['batch_number']) || 
                    !empty($updateData['qc_document_number']) || 
                    !empty($updateData['prepared_by'])) {
                    $hasBatchInfo = true;
                }
            }
            
            if ($hasErrors) {
                DB::rollBack();
                \Log::error('Batch info update failed', ['error_message' => $errorMessage]);
                return redirect()->back()->with('error', $errorMessage);
            }
            
            // Update order status to "preparing" if it's still "new" and batch information exists
            if ($order->status === 'new' && $hasBatchInfo) {
                $order->update(['status' => 'preparing']);
                \Log::info('Updated order status to preparing', ['order_id' => $order->id]);
            }
            
            DB::commit();
            \Log::info('Batch info update completed successfully', ['order_id' => $order->id]);
            
            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'Batch information updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Exception during batch info update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error updating batch information: ' . $e->getMessage());
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
            
            // Handle batch number permissions
            if (isset($request->batch_number) && !empty($request->batch_number) && 
                $existingPivot->batch_number !== $request->batch_number) {
                if ($user->department === 'Cell Lab' || $user->department === 'Quality' || $user->role === 'superadmin') {
                    $updateData['batch_number'] = $request->batch_number;
                } else {
                    return redirect()->back()->with('error', 'Only Cell Lab and Quality departments can edit batch numbers.');
                }
            } elseif ($existingPivot->batch_number) {
                $updateData['batch_number'] = $existingPivot->batch_number;
            } else {
                $updateData['batch_number'] = $request->batch_number ?? null;
            }
            
            // Handle QC document number permissions
            if (isset($request->qc_document_number) && !empty($request->qc_document_number) && 
                $existingPivot->qc_document_number !== $request->qc_document_number) {
                if ($user->department === 'Quality' || $user->role === 'superadmin') {
                    $updateData['qc_document_number'] = $request->qc_document_number;
                } else {
                    return redirect()->back()->with('error', 'Only Quality department can edit QC document numbers.');
                }
            } elseif ($existingPivot->qc_document_number) {
                $updateData['qc_document_number'] = $existingPivot->qc_document_number;
            } else {
                $updateData['qc_document_number'] = $request->qc_document_number ?? null;
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
            
            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'Batch information updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating batch information: ' . $e->getMessage());
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
            'delivery_type' => 'required|in:delivery,self_collect',
        ]);

        // Parse the datetime with flexible format handling
        try {
            $dateTimeString = trim($request->delivery_datetime);
            
            // Log the incoming datetime string for debugging
            Log::info('Datetime parsing attempt', [
                'original_string' => $dateTimeString,
                'order_id' => $id,
                'user' => Auth::user()->name
            ]);
            
            // Try multiple formats to handle different flatpickr outputs
            $formats = [
                'd.m.Y H:i',      // 31.12.2023 15:30 (expected flatpickr format)
                'Y-m-d H:i',      // 2023-12-31 15:30 (ISO format)
                'd/m/Y H:i',      // 31/12/2023 15:30
                'm/d/Y H:i',      // 12/31/2023 15:30
                'd-m-Y H:i',      // 31-12-2023 15:30
                'd.m.Y h:i A',    // 31.12.2023 3:30 PM
                'd/m/Y h:i A',    // 31/12/2023 3:30 PM
                'Y-m-d h:i A',    // 2023-12-31 3:30 PM
                'd.m.Y H:i:s',    // 31.12.2023 15:30:00
                'Y-m-d H:i:s',    // 2023-12-31 15:30:00
                'Y-m-d\TH:i:s',   // ISO 8601 format
                'Y-m-d\TH:i:s.u\Z', // ISO 8601 with microseconds
            ];
            
            $dateTime = null;
            $usedFormat = null;
            
            foreach ($formats as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $dateTimeString);
                    if ($parsed && $parsed->year > 1900 && $parsed->year < 2100) {
                        $dateTime = $parsed;
                        $usedFormat = $format;
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If specific formats fail, try Carbon's flexible parsing
            if (!$dateTime) {
                try {
                    $dateTime = Carbon::parse($dateTimeString);
                    $usedFormat = 'Carbon::parse()';
                } catch (\Exception $e) {
                    // Log the parsing failure
                    Log::error('All datetime parsing methods failed', [
                        'input_string' => $dateTimeString,
                        'attempted_formats' => $formats,
                        'carbon_parse_error' => $e->getMessage(),
                        'order_id' => $id
                    ]);
                    throw new \Exception('Unable to parse the date and time. Please ensure you have selected both a valid date and time.');
                }
            }
            
            // Log successful parsing
            Log::info('Datetime parsing successful', [
                'input_string' => $dateTimeString,
                'used_format' => $usedFormat,
                'parsed_datetime' => $dateTime->format('Y-m-d H:i:s'),
                'order_id' => $id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Datetime parsing error in updateStatus', [
                'error' => $e->getMessage(),
                'input' => $request->delivery_datetime ?? 'NULL',
                'order_id' => $id
            ]);
            return redirect()->back()->with('error', 'Invalid date/time format. Please select a valid date and time from the date picker. Error: ' . $e->getMessage());
        }
        
        $order = Order::findOrFail($id);
        $order->status = 'delivered';
        $order->delivery_type = $request->delivery_type;
        $order->pickup_delivery_date = $dateTime->toDateString();
        $order->pickup_delivery_time = $dateTime->toTimeString();
        $order->delivered_by = $request->dispatcher;
        $order->save();

        $actionType = $request->delivery_type === 'delivery' ? 'delivered' : 'collected';
        return redirect()->back()->with('success', "Order marked as {$actionType} successfully!");
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:new,preparing,ready,delivered,cancel',
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
                // Set item_ready_at if not already set
                if (!$order->item_ready_at) {
                    $order->item_ready_at = now();
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
                    'delivery_type' => 'required|in:delivery,self_collect',
                ]);

                // Parse the datetime with flexible format handling
                try {
                    $dateTimeString = trim($request->delivery_datetime);
                    
                    // Log the incoming datetime string for debugging
                    Log::info('Datetime parsing attempt', [
                        'original_string' => $dateTimeString,
                        'order_id' => $id,
                        'user' => Auth::user()->name
                    ]);
                    
                    // Try multiple formats to handle different flatpickr outputs
                    $formats = [
                        'd.m.Y H:i',      // 31.12.2023 15:30 (expected flatpickr format)
                        'Y-m-d H:i',      // 2023-12-31 15:30 (ISO format)
                        'd/m/Y H:i',      // 31/12/2023 15:30
                        'm/d/Y H:i',      // 12/31/2023 15:30
                        'd-m-Y H:i',      // 31-12-2023 15:30
                        'd.m.Y h:i A',    // 31.12.2023 3:30 PM
                        'd/m/Y h:i A',    // 31/12/2023 3:30 PM
                        'Y-m-d h:i A',    // 2023-12-31 3:30 PM
                        'd.m.Y H:i:s',    // 31.12.2023 15:30:00
                        'Y-m-d H:i:s',    // 2023-12-31 15:30:00
                        'Y-m-d\TH:i:s',   // ISO 8601 format
                        'Y-m-d\TH:i:s.u\Z', // ISO 8601 with microseconds
                    ];
                    
                    $dateTime = null;
                    $usedFormat = null;
                    
                    foreach ($formats as $format) {
                        try {
                            $parsed = Carbon::createFromFormat($format, $dateTimeString);
                            if ($parsed && $parsed->year > 1900 && $parsed->year < 2100) {
                                $dateTime = $parsed;
                                $usedFormat = $format;
                                break;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    
                    // If specific formats fail, try Carbon's flexible parsing
                    if (!$dateTime) {
                        try {
                            $dateTime = Carbon::parse($dateTimeString);
                            $usedFormat = 'Carbon::parse()';
                        } catch (\Exception $e) {
                            // Log the parsing failure
                            Log::error('All datetime parsing methods failed', [
                                'input_string' => $dateTimeString,
                                'attempted_formats' => $formats,
                                'carbon_parse_error' => $e->getMessage(),
                                'order_id' => $id
                            ]);
                            throw new \Exception('Unable to parse the date and time. Please ensure you have selected both a valid date and time.');
                        }
                    }
                    
                    // Log successful parsing
                    Log::info('Datetime parsing successful', [
                        'input_string' => $dateTimeString,
                        'used_format' => $usedFormat,
                        'parsed_datetime' => $dateTime->format('Y-m-d H:i:s'),
                        'order_id' => $id
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Datetime parsing error in updateStatus', [
                        'error' => $e->getMessage(),
                        'input' => $request->delivery_datetime ?? 'NULL',
                        'order_id' => $id
                    ]);
                    return redirect()->back()->with('error', 'Invalid date/time format. Please select a valid date and time from the date picker. Error: ' . $e->getMessage());
                }
                
                $order->delivery_type = $request->delivery_type;
                $order->pickup_delivery_date = $dateTime->toDateString();
                $order->pickup_delivery_time = $dateTime->toTimeString();
                $order->delivered_by = $request->dispatcher;
            }
            
            $order->status = $request->status;
            $order->save();
            
            DB::commit();

            // Send order cancellation notification emails if status changed to cancel
            if ($request->status === 'cancel' && $oldStatus !== 'cancel') {
                $emailController = new EmailController();
                $emailController->sendOrderCanceledNotification($order);
            }

            // Send order ready notification emails if status changed to ready
            if ($request->status === 'ready' && $oldStatus !== 'ready') {
                $emailController = new EmailController();
                $emailController->sendOrderReadyNotification($order);
            }

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
            // Set item_ready_at if not already set
            if (!$order->item_ready_at) {
                $order->item_ready_at = now();
            }
            $order->save();
            
            DB::commit();

            // Send order ready notification emails
            $emailController = new EmailController();
            $emailController->sendOrderReadyNotification($order);

            return redirect()->back()->with('success', 'Order marked as Ready successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking order as ready: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating order status: ' . $e->getMessage());
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
                'pivot_id' => $request->pivot_id,
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
                'pivot_id' => 'required|integer|exists:order_product,id',
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
            
            // Get the specific pivot record
            $pivotRecord = DB::table('order_product')
                ->where('id', $request->pivot_id)
                ->where('order_id', $orderId)
                ->first();
                
            if (!$pivotRecord) {
                Log::error('Pivot record not found', [
                    'pivot_id' => $request->pivot_id,
                    'order_id' => $orderId
                ]);
                return redirect()->back()->with('error', 'Product record not found.');
            }
            
            // Log before update
            Log::info('Before updating pivot', [
                'pivot_id' => $request->pivot_id,
                'current_status' => $pivotRecord->status
            ]);
            
            // Update the specific pivot record
            $updated = DB::table('order_product')
                ->where('id', $request->pivot_id)
                ->where('order_id', $orderId)
                ->update([
                    'status' => $request->status,
                    'updated_at' => now()
                ]);
            
            Log::info('Pivot update result', [
                'updated' => $updated,
                'status_value' => $request->status,
                'pivot_id' => $request->pivot_id
            ]);
            
            // Force refresh the relationship
            $order->load('products');
            
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
     * Handle uploading an order photo after all items are ready.
     */
    public function uploadOrderPhoto(Request $request, $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check if all products are ready
            $allProductsReady = true;
            $totalProducts = count($order->products);
            foreach($order->products as $product) {
                if($product->pivot->status !== 'ready') {
                    $allProductsReady = false;
                    break;
                }
            }

            // Only allow upload if order is ready or if all products are ready in preparing status
            if ($order->status !== 'ready' && !($order->status === 'preparing' && $allProductsReady && $totalProducts > 0)) {
                return redirect()->back()->with('error', 'Photo can only be uploaded when all products are ready.');
            }

            // Enhanced validation with better error messages
            $validator = Validator::make($request->all(), [
                'order_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:51200', // 50MB limit
            ], [
                'order_photo.required' => 'Please select an image to upload.',
                'order_photo.image' => 'The uploaded file must be an image.',
                'order_photo.mimes' => 'Image must be a JPEG, PNG, GIF, WebP, or HEIC file.',
                'order_photo.max' => 'Image size must not exceed 50MB. Please compress your image and try again.'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Upload failed: ' . $validator->errors()->first());
            }

            $file = $request->file('order_photo');
            
            // Additional file validation
            if (!$file || !$file->isValid()) {
                return redirect()->back()->with('error', 'Invalid file upload. Please try again.');
            }

            // Check actual file size (double-check)
            $maxFileSize = 52428800; // 50MB in bytes
            if ($file->getSize() > $maxFileSize) {
                return redirect()->back()->with('error', 'File size exceeds 50MB limit. Please compress your image and try again.');
            }

            // Generate unique filename with timestamp and order ID
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = 'order_' . $order->id . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Delete old photo if exists
            if ($order->order_photo) {
                \Storage::delete('public/order_photos/' . $order->order_photo);
            }

            // Store the uploaded photo with error handling
            try {
                $path = $file->storeAs('public/order_photos', $filename);
                
                if (!$path) {
                    throw new \Exception('Failed to store uploaded file.');
                }
                
                // Verify file was actually saved
                if (!\Storage::exists($path)) {
                    throw new \Exception('File upload verification failed.');
                }
                
            } catch (\Exception $e) {
                Log::error('File storage error for Order #' . $order->id . ': ' . $e->getMessage());
                return redirect()->back()->with('error', 'Failed to save uploaded image. Please try again.');
            }

            // Update the order with the photo filename
            $order->order_photo = $filename;
            $order->save();

            // Log successful upload
            Log::info('Photo uploaded successfully for Order #' . $order->id . ' - File: ' . $filename . ' - Size: ' . round($file->getSize() / 1024 / 1024, 2) . 'MB');

            // Send photo upload notification to the person who placed the order
            $emailController = new EmailController();
            $emailController->sendPhotoUploadNotification($order);

            return redirect()->back()->with('success', 'Order photo uploaded successfully! (' . round($file->getSize() / 1024 / 1024, 2) . 'MB)');
            
        } catch (\Exception $e) {
            Log::error('Photo upload error for Order #' . $orderId . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while uploading the photo. Please try again.');
        }
    }



    /**
     * Delete the order photo from storage and database.
     */
    public function deleteOrderPhoto($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Only allow delete if order has a photo
        if (!$order->order_photo) {
            return redirect()->back()->with('error', 'No photo to delete.');
        }

        // Delete the file from storage
        \Storage::delete('public/order_photos/' . $order->order_photo);

        // Remove the filename from the order
        $order->order_photo = null;
        $order->save();

        return redirect()->back()->with('success', 'Order photo deleted successfully!');
    }

    /**
     * Mark the order as ready via a GET link (no token required).
     */
    public function markReadyLink($id)
    {
        $order = Order::findOrFail($id);
        if ($order->status !== 'ready') {
            $order->status = 'ready';
            if (!$order->item_ready_at) {
                $order->item_ready_at = now();
            }
            $order->save();

            // Send order ready notification emails
            $emailController = new EmailController();
            $emailController->sendOrderReadyNotification($order);
        }
        return redirect()->route('orderdetails', $order->id)
            ->with('success', 'Order has been marked as Ready!');
    }

    /**
     * Update delivery date and time for an order.
     */
    public function updateDeliveryDateTime(Request $request, $id)
    {
        $request->validate([
            'pickup_delivery_date' => 'required|date',
            'pickup_delivery_time' => 'required|date_format:H:i',
            'item_ready_time' => 'required|date_format:H:i',
        ]);

        DB::beginTransaction();
        
        try {
            $order = Order::with(['customer', 'products'])->findOrFail($id);
            
            // Store original values for comparison
            $originalDateTime = null;
            if ($order->pickup_delivery_date && $order->pickup_delivery_time) {
                $originalDateTime = Carbon::parse($order->pickup_delivery_date->format('Y-m-d') . ' ' . $order->pickup_delivery_time->format('H:i:s'));
            }
            
            // Store original ready time for comparison
            $originalReadyTime = null;
            if ($order->item_ready_at) {
                $originalReadyTime = Carbon::parse($order->item_ready_at)->format('g:i A');
            }
            
            // Update delivery date, time, and ready time
            $order->pickup_delivery_date = $request->pickup_delivery_date;
            $order->pickup_delivery_time = $request->pickup_delivery_time;
            $order->item_ready_at = $request->item_ready_time;
            $order->save();
            
            // Create new datetime and ready time for comparison
            $newDateTime = Carbon::parse($request->pickup_delivery_date . ' ' . $request->pickup_delivery_time);
            $newReadyTime = Carbon::parse($request->item_ready_time)->format('g:i A');
            
            DB::commit();
            
            // Send delivery update notification emails
            $emailController = new EmailController();
            $emailController->sendDeliveryUpdateNotification($order, $originalDateTime, $newDateTime, $originalReadyTime, $newReadyTime);
            
            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'Delivery schedule and ready time updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating delivery schedule: ' . $e->getMessage());
        }
    }

    /**
     * Save e-signature for order collection
     */
    public function saveSignature(Request $request, Order $order)
    {
        try {
            $validator = Validator::make($request->all(), [
                'collected_by' => 'required|string|max:255',
                'signature_data' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update order with signature data
            $order->update([
                'collected_by' => $request->collected_by,
                'signature_data' => $request->signature_data,
                'signature_date' => now(),
                'signature_ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Signature saved successfully!',
                'data' => [
                    'collected_by' => $order->collected_by,
                    'signature_date' => $order->signature_date->format('d/m/Y h:i A'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving signature: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving signature: ' . $e->getMessage()
            ], 500);
        }
    }
}
