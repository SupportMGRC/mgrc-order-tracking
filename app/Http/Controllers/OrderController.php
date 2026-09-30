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
use App\Models\BlockedDate;
use App\Models\CoaEditRequest;
use App\Mail\CoaEditRequestNotification;
use App\Mail\CoaReturnedNotification;
use Illuminate\Support\Facades\Mail;
use App\Services\ActivityLogger;
use App\Services\CoaTemplateService;

class OrderController extends Controller
{
    /**
     * Columns the Order History date filter may target.
     *
     * Whitelisted because the chosen field goes straight into a query builder
     * call; anything not on this list falls back to order_date.
     */
    private const DATE_FIELDS = [
        'order_date',
        'pickup_delivery_date',
        'collection_date',
    ];

    /**
     * Image size limit for morphology uploads
     */
    public const MORPHOLOGY_MAX_KB = 8192;

    /**
     * Size limit for a COA PDF uploaded by QC.
     */
    public const COA_DOCUMENT_MAX_KB = 20480;

    /**
     * Departments whose staff see only the orders they placed themselves.
     */
    private const OWN_ORDERS_ONLY_DEPARTMENTS = [
        'medical affairs',
        'business development',
    ];

    /**
     * Whether the logged-in user is limited to the orders they placed.
     *
     * Role wins over department: admin and superadmin always see everything,
     * even if their department is on the list above.
     */
    private function restrictedToOwnOrders(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return false;
        }

        $department = strtolower(trim((string) $user->department));

        return in_array($department, self::OWN_ORDERS_ONLY_DEPARTMENTS, true);
    }

    /**
     * Limit a query to the orders belonging to the given user.
     *
     */
    private function applyOwnOrdersScope($query, $user)
    {
        $fullName = strtolower(trim($user->first_name . ' ' . $user->last_name));

        return $query->where(function ($q) use ($user, $fullName) {
            $q->where('user_id', $user->id);

            if ($fullName !== '') {
                $q->orWhereRaw('LOWER(TRIM(order_placed_by)) = ?', [$fullName]);
            }
        });
    }

    /**
     * Whether the logged-in user may open this order. Medical Affairs and
     * Business Development (below admin) see only the orders they placed;
     * everyone else with order access sees all of them.
     */
    private function currentUserMaySeeOrder(Order $order): bool
    {
        if (!$this->restrictedToOwnOrders()) {
            return true;
        }

        $user = Auth::user();
        $fullName = strtolower(trim($user->first_name . ' ' . $user->last_name));
        $placedBy = strtolower(trim((string) $order->order_placed_by));

        return $order->user_id == $user->id
            || ($fullName !== '' && $placedBy === $fullName);
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
        $user = Auth::user();
        $restrictToOwn = $this->restrictedToOwnOrders();

        if ($restrictToOwn) {
            $this->applyOwnOrdersScope($query, $user);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
            // Store the status filter in session for redirecting after deleting an order
            $request->session()->put('status_filter', $request->status);
        } else {
            // Clear the status filter from session
            $request->session()->forget('status_filter');
        }

        // Date filtering. Previously this was skipped entirely for the new,
        // preparing and ready tabs, so "new orders from last week" silently
        // returned everything. It now applies on every tab.
        //
        // date_field chooses which column to filter, so one control covers what
        // used to be a dropdown for order_date plus a separate hidden popup for
        // the reach-client date.
        $dateField = in_array($request->get('date_field'), self::DATE_FIELDS, true)
            ? $request->get('date_field')
            : 'order_date';

        $dateRange = $request->get('date_range', 'all');

        switch ($dateRange) {
            case 'today':
                $query->whereDate($dateField, Carbon::today());
                break;

            case 'weekly':
                $query->whereBetween($dateField, [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
                break;

            case 'monthly':
                $query->whereBetween($dateField, [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
                break;

            case 'yearly':
                $query->whereBetween($dateField, [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ]);
                break;

            case 'custom':
                // Two separate inputs rather than one "X to Y" string, so a
                // half-filled range still works as an open-ended one.
                $from = $request->get('date_from');
                $to   = $request->get('date_to');

                if ($from && $to) {
                    $query->whereBetween($dateField, [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay(),
                    ]);
                } elseif ($from) {
                    $query->whereDate($dateField, '>=', Carbon::parse($from));
                } elseif ($to) {
                    $query->whereDate($dateField, '<=', Carbon::parse($to));
                }
                break;

            case 'all':
            default:
                // Legacy "X to Y" strings from old bookmarked URLs.
                if (is_string($dateRange) && strpos($dateRange, ' to ') !== false) {
                    $dates = explode(' to ', $dateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween($dateField, [
                            Carbon::parse($dates[0])->startOfDay(),
                            Carbon::parse($dates[1])->endOfDay(),
                        ]);
                    }
                }
                break;
        }

        // Legacy parameter from the old column-header popup. Kept so existing
        // bookmarks and links still work.
        if ($request->filled('reach_client_date')) {
            $query->whereDate('pickup_delivery_date', $request->reach_client_date);
        }

        // Search by ID, customer name, product name, or status
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('products', function ($query) use ($search) {
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

        // Status tab counts. These carry the same ownership scope as the list
        // above, so a badge never promises more rows than the tab shows. One
        // closure builds each base query rather than branching the whole block
        // in two, which is how this drifted out of sync before.
        $countQuery = function () use ($restrictToOwn, $user) {
            $q = Order::query();

            if ($restrictToOwn) {
                $this->applyOwnOrdersScope($q, $user);
            }

            return $q;
        };

        $newCount = $countQuery()->where('status', 'new')->count();
        $preparingCount = $countQuery()->where('status', 'preparing')->count();
        $readyCount = $countQuery()->where('status', 'ready')->count();
        $deliveredCount = $countQuery()->where('status', 'delivered')->count();
        // Cancelled and the overall total were never counted, so those two
        // tabs showed no badge.
        $canceledCount = $countQuery()->where('status', 'cancel')->count();
        $allCount = $countQuery()->count();

        return view('orders.orderhistory', compact(
            'orders',
            'newCount',
            'preparingCount',
            'readyCount',
            'deliveredCount',
            'canceledCount',
            'allCount'
        ));
    }

    /**
     * Display the new order form.
     */
    public function newOrder()
    {
        $customers = Customer::all();
        // Pickup items (Blood Tube etc.) live in the same table but are never ordered.
        $products = Product::forOrders()->active()->where('stock', '>', 0)->get();
        $dispatchers = User::all();
        $blockedDates = BlockedDate::getBlockedDatesArray();
        $blockedDatesWithReasons = BlockedDate::getBlockedDatesWithReasons();

        return view('orders.neworder', compact('customers', 'products', 'dispatchers', 'blockedDates', 'blockedDatesWithReasons'));
    }

    /**
     * Store a new order from the neworder form.
     */
    public function storeNewOrder(Request $request)
    {
        // Check if the selected date is blocked
        if (BlockedDate::isDateBlocked($request->pickup_delivery_date)) {
            $blockedDate = BlockedDate::where('blocked_date', $request->pickup_delivery_date)
                ->where('is_active', true)
                ->first();

            $reason = $blockedDate ? $blockedDate->reason : 'Holiday/Maintenance';
            return redirect()->back()
                ->withInput()
                ->with('error', "Orders cannot be placed for " . Carbon::parse($request->pickup_delivery_date)->format('d/m/Y') . ". Reason: {$reason}");
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'order_placed_by' => 'nullable|string|max:255',
            'pickup_delivery_date' => 'required|date|after_or_equal:today',
            'pickup_delivery_time' => 'required',
            'collection_date' => 'nullable|date',
            'delivery_type' => 'required|in:delivery,self_collect',
            'remarks' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.type' => 'required|string',
            // Quantity is hidden for patient test products, so it is not
            // trusted to arrive. It is normalised to 1 below.
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.patient_name' => 'nullable|string',
            'products.*.patient_ic' => 'nullable|string|max:30',
            'products.*.remarks' => 'nullable|string',
            'products.*.coa_required' => 'nullable|boolean',
            'item_ready_at' => 'required|date_format:g:i A',
        ], [
            'customer_name.required' => 'The customer name is required.',
            'customer_phone.required' => 'The customer phone number is required.',
            'customer_address.required' => 'The customer address is required.',
            'pickup_delivery_date.required' => 'The delivery date is required.',
            'pickup_delivery_date.after_or_equal' => 'The delivery date cannot be in the past.',
            'pickup_delivery_time.required' => 'The delivery time is required.',
            'products.required' => 'At least one product is required.',
            'products.min' => 'You need at least one product item.',
            'products.*.type.required' => 'The product type is required for all products.',
            'products.*.quantity.min' => 'The product quantity must be at least 1.',
            'item_ready_at.required' => 'The item ready time is required.',
        ]);

        // Patient name and IC are compulsory only for products flagged as
        // patient tests, so the rule cannot be expressed statically — which
        // product a line refers to is only known once the input is read.
        // Keyed by name because that is what the form posts.
        $validator->after(function ($v) use ($request) {
            $patientProducts = Product::all()
                ->filter(fn ($p) => $p->requiresPatientDetails())
                ->pluck('name')
                ->all();

            foreach ((array) $request->input('products', []) as $i => $line) {
                $name = $line['type'] ?? null;

                if (!$name || !in_array($name, $patientProducts, true)) {
                    continue;
                }

                if (trim((string) ($line['patient_name'] ?? '')) === '') {
                    $v->errors()->add(
                        "products.{$i}.patient_name",
                        "Patient name is required for {$name}."
                    );
                }

                if (trim((string) ($line['patient_ic'] ?? '')) === '') {
                    $v->errors()->add(
                        "products.{$i}.patient_ic",
                        "Patient IC number is required for {$name}."
                    );
                }
            }
        });

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
            $order->collection_date = $request->collection_date;
            $order->collection_time = $request->collection_time ? Carbon::parse($request->collection_time)->toTimeString() : null;
            $order->delivery_type = $request->delivery_type;
            $order->time_sensitive = $request->has('time_sensitive') ? true : false;
            $order->remarks = $request->remarks;
            $order->delivery_address = $request->delivery_address;
            if ($request->filled('item_ready_at')) {
                $order->item_ready_at = \Carbon\Carbon::createFromFormat('g:i A', $request->item_ready_at)->format('H:i:s');
            }
            $order->save();

            // Attach products to order
            foreach ($request->products as $product) {
                // Find product by name
                $productModel = Product::forOrders()->where('name', $product['type'])->first();

                if (!$productModel) {
                    throw new \Exception("Product not found: {$product['type']}");
                }

                $isPatientTest = $productModel->requiresPatientDetails();

                // Patient tests are one per order line: the quantity field is
                // hidden on the form, so nothing reliable arrives for it.
                $quantity = $isPatientTest ? 1 : (int) ($product['quantity'] ?? 1);

                if ($quantity < 1) {
                    $quantity = 1;
                }

                // Check stock
                if ($productModel->stock < $quantity) {
                    throw new \Exception("Not enough stock for product: {$productModel->name}. Available: {$productModel->stock}");
                }

                // Reduce stock
                $productModel->stock -= $quantity;
                $productModel->save();

                // Create single record with actual quantity
                $order->products()->attach($productModel->id, [
                    'quantity' => $quantity,
                    'patient_name' => isset($product['patient_name']) ? $product['patient_name'] : null,
                    'patient_ic' => $isPatientTest ? ($product['patient_ic'] ?? null) : null,
                    // Remarks do not apply to patient tests; the field is
                    // hidden, so any stale value is discarded rather than
                    // stored where nobody will look for it.
                    'remarks' => $isPatientTest ? null : (isset($product['remarks']) ? $product['remarks'] : null),
                    'coa_required' => isset($product['coa_required']) ? (bool) $product['coa_required'] : false,
                ]);
            }

            DB::commit();
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
            'products_data' => $order->products->map(function ($product) {
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
            'products.*.patient_ic' => 'nullable|string|max:30',
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
                    'patient_ic' => $productData['patient_ic'] ?? null,
                    'remarks' => $productData['remarks'] ?? null,
                    'prepared_by' => $productData['prepared_by'] ?? null,
                ];

                // Handle batch number permissions
                if (
                    isset($productData['batch_number']) && !empty($productData['batch_number']) &&
                    $pivotRecord->batch_number !== $productData['batch_number']
                ) {
                    if ($user->department === 'Cell Lab' || $user->isQualityControl() || $user->role === 'superadmin') {
                        $updateData['batch_number'] = $productData['batch_number'];
                    } else {
                        $hasErrors = true;
                        $errorMessage = 'Only Cell Lab and Quality Control departments can edit batch numbers.';
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
                if (
                    isset($productData['qc_document_number']) && !empty($productData['qc_document_number']) &&
                    $pivotRecord->qc_document_number !== $productData['qc_document_number']
                ) {
                    if ($user->isQualityControl() || $user->role === 'superadmin') {
                        $updateData['qc_document_number'] = $productData['qc_document_number'];
                    } else {
                        $hasErrors = true;
                        $errorMessage = 'Only Quality Control department can edit QC document numbers.';
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

                // Audit log: record batch/QC/pivot changes
                $productName = null;
                foreach ($order->products as $op) {
                    if (($op->pivot->id ?? null) == $productData['pivot_id']) {
                        $productName = $op->name;
                        break;
                    }
                }
                \App\Services\ActivityLogger::recordPivotChange(
                    $order,
                    (array) $pivotRecord,
                    $updateData,
                    $productName
                );

                // Check if any batch information exists
                if (
                    !empty($updateData['batch_number']) ||
                    !empty($updateData['qc_document_number']) ||
                    !empty($updateData['prepared_by'])
                ) {
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

        if (!$this->currentUserMaySeeOrder($order)) {
            $user = Auth::user();
            Log::warning('User attempted to access an order they did not place', [
                'user_id' => $user->id,
                'username' => $user->username,
                'department' => $user->department,
                'order_id' => $order->id,
                'order_user_id' => $order->user_id,
                'order_placed_by' => $order->order_placed_by,
            ]);

            return redirect()->route('orderhistory')
                ->with('error', 'You can only view orders that you have placed.');
        }

        // Order lines with a COA edit request waiting for the HOD, so the COA
        // column can flag them.
        $pendingCoaEdits = CoaEditRequest::pending()
            ->where('order_id', $order->id)
            ->pluck('order_product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Latest decided unlock per order line (approved request, return to QC,
        // reopen, or a returned upload), so the COA column can flag a line
        // that is waiting to be corrected.
        $coaLastUnlock = CoaEditRequest::where('order_id', $order->id)
            ->where('status', CoaEditRequest::STATUS_APPROVED)
            ->with('decider')
            ->orderByDesc('id')
            ->get()
            ->unique('order_product_id')
            ->keyBy('order_product_id');

        return view('orders.orderdetails', compact('order', 'pendingCoaEdits', 'coaLastUnlock'));
    }

    /**
     * Display the Certificate of Analysis (COA) page for a specific product in an order.
     *
     * Everyone with order access may open it read-only; Medical Affairs and
     * Business Development only for their own orders. Quality Control fills
     * it in and submits it, after which it is locked for everyone until the
     * COA approver (QC HOD) approves a request to edit.
     */
    public function showCOA(Order $order, int $line, CoaTemplateService $coa)
    {
        $user = auth()->user();

        if (!$coa->userMayAccess($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to COAs.');
        }

        // Same rule as Order Details. Without it the COA of another person's
        // order could be opened by typing its link.
        if (!$this->currentUserMaySeeOrder($order)) {
            return redirect()->route('orderhistory')
                ->with('error', 'You can only view COAs for orders that you have placed.');
        }

        // Load necessary relationships
        $order->load(['customer', 'user', 'products']);

        // This order line: the product with its own pivot row.
        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        $orderProduct = $product;

        // Check if COA is required for this product
        if (!$orderProduct->pivot->coa_required) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'COA is not required for this product.');
        }

        // Product explicitly marked as having no COA
        if (!$coa->productHasCoa($product)) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'This product does not have a COA.');
        }

        // The HOD switched this line to an uploaded COA. The template can't
        // be used for it any more, including from an old link or bookmark.
        if ($coa->isUploadMode($orderProduct->pivot)) {
            $uploader = $orderProduct->pivot->coa_document_uploaded_by
                ? User::find($orderProduct->pivot->coa_document_uploaded_by)
                : null;
            $uploadedAt = $orderProduct->pivot->coa_document_uploaded_at
                ? Carbon::parse($orderProduct->pivot->coa_document_uploaded_at)
                : null;

            $message = $orderProduct->pivot->coa_document
                ? 'This COA was uploaded as a file'
                    . ($uploader ? ' by ' . $uploader->fullName() : '')
                    . ($uploadedAt ? ' on ' . $uploadedAt->format('j M Y') : '')
                    . '. The COA template can\'t be used for ' . $product->name . '.'
                : 'The HOD switched ' . $product->name . ' to an uploaded COA, so the COA template can\'t be used. '
                    . 'Quality Control uploads the COA from this page.';

            return redirect()->route('orderdetails', $order->id)->with('error', $message);
        }

        $templateKey = $coa->resolveForOrderLine($product);

        // Only superadmin can pick COA on the spot if not set
        if ($templateKey === null) {
            if ($user->role !== 'superadmin') {
                return redirect()->route('orderdetails', $order->id)
                    ->with('error', 'No COA template set for ' . $product->name
                        . '. Please ask a system administrator to set one in Product Management.');
            }

            return view('orders.coa-choose-template', [
                'order'     => $order,
                'product'   => $product,
                'lineId'    => $product->pivot->id,
                'templates' => $coa->options(),
            ]);
        }

        $pivot     = $orderProduct->pivot;
        $submitted = $coa->isSubmitted($pivot);

        $requests = CoaEditRequest::forLine($pivot->id)
            ->with(['requester', 'decider'])
            ->get();
        $pendingRequest = $requests->first(fn ($r) => $r->isPending());
        $lastDecided    = $requests->first(fn ($r) => !$r->isPending());

        // Unlocks of this template COA (approved requests, returns to QC and
        // HOD reopens), newest first. The latest drives the reopened banner;
        // the count shows on the banner once it is submitted again.
        $templateUnlocks = $requests->filter(fn ($r) => $r->isTemplateUnlock())->values();
        $lastUnlock      = $templateUnlocks->first();
        $mayDecide       = $user->canApproveCoaEdit();

        $submittedBy = $pivot->coa_submitted_by ? User::find($pivot->coa_submitted_by) : null;

        // Before submission the preview signs with whoever is filling it in.
        // After submission it is the stored name, the same for every reader.
        $signatoryName = $submitted
            ? (string) $pivot->coa_signatory_name
            : ($coa->userMayEdit($user) ? $user->fullName() : '');

        return view('orders.coa-editor', [
            'order'        => $order,
            'product'      => $product,
            'orderProduct' => $orderProduct,
            'lineId'       => $pivot->id,
            'templateKey'  => $templateKey,
            'template'     => $coa->get($templateKey),
            'pdfUrl'       => $coa->pdfUrl($templateKey),
            'editable'     => $coa->editableFields($templateKey),
            'fieldLabels'  => $coa->fieldLabels($templateKey),
            'acceptsImage' => $coa->acceptsMorphologyImage($templateKey),
            'coaValues'    => $this->coaValues($orderProduct),
            'variants'     => $coa->variantsFor($templateKey),
            'morphologyMaxMb' => intdiv(self::MORPHOLOGY_MAX_KB, 1024),
            'certificatePages' => $coa->certificatePages($templateKey),

            // Submit-and-lock state
            'submitted'      => $submitted,
            'submittedBy'    => $submittedBy,
            'submittedAt'    => $pivot->coa_submitted_at ? Carbon::parse($pivot->coa_submitted_at) : null,
            'signatoryName'  => $signatoryName,
            'pendingRequest' => $pendingRequest,
            'lastDecided'    => $lastDecided,
            'lastUnlock'     => $lastUnlock,
            'unlockCount'    => $templateUnlocks->count(),

            // Permissions. canEdit is false for everyone once submitted.
            // A COA approver corrects a submitted COA directly (Return to QC
            // or Reopen), so the request form is for other QC staff only.
            'canEdit'        => $coa->userMayEdit($user) && !$submitted,
            'canPrint'       => $user->canPrintCoa(),
            'canDownload'    => $submitted || $user->canDownloadDraftCoa(),
            'canRequestEdit' => $submitted && $user->canRequestCoaEdit() && !$mayDecide,
            'canDecideEdit'  => $mayDecide,
            'canCorrect'     => $submitted && $mayDecide && !$pendingRequest,
            'canReopenSelf'  => $submitted && $mayDecide && !$pendingRequest && $coa->userMayEdit($user),
        ]);
    }

    /**
     * Display the editable COA page. Same view as showCOA; kept so the
     * existing /edit route still resolves.
     */
    public function editCOA(Order $order, int $line, CoaTemplateService $coa)
    {
        return $this->showCOA($order, $line, $coa);
    }

    /**
     * Old COA link, keyed by product (edit request emails sent before COAs
     * moved to one per order line). Opens that product's first line on the
     * order, which is the line the old link always showed.
     */
    public function legacyCoaLink(Order $order, int $product)
    {
        $first = $order->products()
            ->where('product_id', $product)
            ->orderBy('order_product.id')
            ->first();

        if (!$first) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        return redirect()->route('orders.coa', [$order->id, $first->pivot->id]);
    }

    /**
     * Record which template to use for this order line.
     *
     * Serves three callers:
     *   - the fallback picker, when the product has no template set
     *   - the Quality variant toggle, which may only move an order between
     *     alternate wordings of the same certificate (MSC P2 with/without
     *     the patient's name)
     *   - the superadmin panel, which may move it to any template at all
     */
    public function chooseCoaTemplate(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        if (!$coa->userMayEdit(auth()->user())) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Only the Quality Control department can change the COA template.');
        }

        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        if ($coa->isSubmitted($product->pivot)) {
            return back()->with('error', 'This COA has been submitted and is locked. Request an edit to change it.');
        }

        if ($coa->isUploadMode($product->pivot)) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'This product uses an uploaded COA, so the COA template can\'t be used.');
        }

        $key = $request->input('coa_template');

        if (!$coa->exists($key) || $coa->isLegacy($key)) {
            return back()->with('error', 'Please choose a valid COA template.');
        }

        // Anyone below superadmin is held to the current certificate's variant
        // group. Without this the route would accept any template key from any
        // Quality user, even though the UI only ever offers the alternates.
        if (auth()->user()->role !== 'superadmin') {
            $current = $coa->resolveForOrderLine($product);

            // Nothing set on the product means nobody below superadmin gets to
            // decide. Matches the hard stop in showCOA(), and closes the route
            // against a hand-posted template key.
            if ($current === null) {
                return redirect()->route('orderdetails', $order->id)
                    ->with('error', 'No COA template set for ' . $product->name
                        . '. Please ask a system administrator to set one in Product Management.');
            }

            // Switching is only allowed between alternates of what is already
            // set, e.g. MSC P2 with and without the patient's name.
            if ($current !== $key && !$coa->sameVariantGroup($current, $key)) {
                return back()->with('error', 'You can only switch between versions of this certificate.');
            }
        }

        $before = $product->pivot->getAttributes();

        // This line only, and only while it is still unsubmitted.
        $this->updateCoaLine($order, $product, ['coa_template' => $key], true);

        // Which certificate an order was issued against is the single most
        // useful thing to have in the audit trail, so record it even though
        // nothing else on the line changed.
        ActivityLogger::recordCoaChange(
            $order,
            $before,
            array_merge($before, ['coa_template' => $key]),
            $this->coaLineName($product),
            'Changed COA template'
        );

        return redirect()->route('orders.coa', [$order->id, $line]);
    }

    /**
     * Submit the COA.
     *
     * Every field the template shows must be filled in (and the morphology
     * image uploaded, where the template has one). On success the line is
     * locked: who submitted it, when, and the name used for the signature are
     * stored, and nobody can change it again until the COA approver (QC HOD)
     * approves a request to edit.
     *
     * Only the fields the chosen template actually exposes are written, so a
     * stale form cannot introduce values that do not belong on the certificate.
     */
    public function saveCOA(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        $product = null;

        try {
            $user = auth()->user();

            if (!$coa->userMayEdit($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the Quality Control department can submit a COA.'
                ], 403);
            }

            // This order line: the product with its own pivot row.
            $product = $this->coaLine($order, $line);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in this order.'
                ], 404);
            }

            $orderProduct = $product;

            if ($coa->isSubmitted($orderProduct->pivot)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This COA has already been submitted and is locked. Reload the page to see it.'
                ], 409);
            }

            if ($coa->isUploadMode($orderProduct->pivot)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The HOD switched this product to an uploaded COA, so the COA template can\'t be submitted.'
                ], 409);
            }

            $templateKey = $coa->resolveForOrderLine($product);

            if ($templateKey === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No COA template selected for this product.'
                ], 422);
            }

            // Map each editable form field to its pivot column.
            $columns = [
                'coa_number'        => 'coa_number',
                'patient_name'      => 'patient_name',
                'batch_number'      => 'batch_number',
                'product_date'      => 'coa_product_date',
                'mfg_date'          => 'coa_mfg_date',
                'expiry_date'       => 'coa_expiry_date',
                'viable_cell_count' => 'coa_viable_cell_count',
                'signature_date'    => 'coa_signature_date',
                'immuno_cd73'       => 'coa_immuno_cd73',
                'immuno_cd90'       => 'coa_immuno_cd90',
                'immuno_cd105'      => 'coa_immuno_cd105',
                'immuno_negative'   => 'coa_immuno_negative',
            ];

            $labels = $coa->fieldLabels($templateKey);

            // The COA number is posted as qc_document_number, the column it
            // has always been stored in (it also shows in the QC Doc column).
            $posted = function (string $field) use ($request) {
                $key = $field === 'coa_number' ? 'qc_document_number' : $field;
                return trim((string) $request->input($key, ''));
            };

            // A submitted COA cannot be corrected without HOD approval, so a
            // blank field is refused here rather than locked in.
            $missing = [];
            foreach ($coa->editableFields($templateKey) as $field) {
                if ($posted($field) === '') {
                    $missing[] = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                }
            }
            if ($coa->acceptsMorphologyImage($templateKey) && empty($orderProduct->pivot->coa_morphology_image)) {
                $missing[] = 'Morphology of Cells Image';
            }
            if (!empty($missing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please fill in every field before submitting. Missing: ' . implode(', ', $missing) . '.',
                    'missing' => $missing,
                ], 422);
            }

            $now = now();
            $update = [
                'coa_template'       => $templateKey,
                'updated_at'         => $now,
                'coa_updated_by'     => $user->id,
                'coa_updated_at'     => $now,
                'coa_submitted_by'   => $user->id,
                'coa_submitted_at'   => $now,
                'coa_signatory_name' => $user->fullName(),
            ];

            foreach ($coa->editableFields($templateKey) as $field) {
                if ($field === 'coa_number') {
                    // prepared_by is deliberately NOT accepted here: no COA
                    // template prints it, so the COA editor must not touch it.
                    $update['qc_document_number'] = $posted($field);
                } elseif (isset($columns[$field])) {
                    $update[$columns[$field]] = $posted($field);
                }
            }

            // Snapshot before the write so the audit entry can show old -> new.
            $before = $orderProduct->pivot->getAttributes();

            // Written only while the line is still unsubmitted, so two QC
            // staff pressing Submit at the same moment cannot both win.
            $written = DB::table('order_product')
                ->where('id', $orderProduct->pivot->id)
                ->where('order_id', $order->id)
                ->whereNull('coa_submitted_at')
                ->update($update);

            if ($written === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This COA has just been submitted by someone else. Reload the page to see it.'
                ], 409);
            }

            ActivityLogger::recordCoaChange(
                $order,
                $before,
                array_merge($before, $update),
                $this->coaLineName($product),
                'Submitted COA'
            );

            return response()->json([
                'success' => true,
                'message' => 'COA submitted. It is now locked.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error submitting COA', [
                'order_id' => $order->id,
                'order_product_id' => $line,
                'product_id' => $product?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error submitting COA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ask for a submitted COA to be unlocked. Quality Control only; one
     * pending request per order line. The COA approvers are emailed.
     */
    public function requestCoaEdit(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        $user = auth()->user();

        if (!$user->canRequestCoaEdit()) {
            return back()->with('error', 'Only the Quality Control department can request to edit a COA.');
        }

        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        if (!$coa->isSubmitted($product->pivot)) {
            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('error', 'This COA has not been submitted, so it can be edited directly.');
        }

        if (CoaEditRequest::pending()->forLine($line)->exists()) {
            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('error', 'A request to edit this COA is already waiting for approval.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Please give a reason for the edit.',
        ]);

        $editRequest = CoaEditRequest::create([
            'order_id'         => $order->id,
            'product_id'       => $product->id,
            'order_product_id' => $line,
            'type'             => CoaEditRequest::TYPE_REQUEST,
            'requested_by'     => $user->id,
            'reason'           => trim($validated['reason']),
            'status'           => CoaEditRequest::STATUS_PENDING,
        ]);

        ActivityLogger::recordCoaEvent(
            $order,
            $this->coaLineName($product),
            'Requested to edit submitted COA',
            ['edit_request' => ['old' => null, 'new' => 'Request #' . $editRequest->id . ': ' . $editRequest->reason]]
        );

        // Email the COA approvers. A mail failure must not lose the request:
        // it is already saved and shows on the COA page and in Order Details.
        $approvers = User::where('coa_approver', true)
            ->whereNotNull('email')
            ->get();

        foreach ($approvers as $approver) {
            try {
                Mail::to($approver->email)->send(
                    new CoaEditRequestNotification($editRequest, $order, $product, $user, $approver)
                );
            } catch (\Throwable $e) {
                Log::error('COA edit request email failed', [
                    'request_id' => $editRequest->id,
                    'approver'   => $approver->email,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('orders.coa', [$order->id, $line])
            ->with('success', 'Request sent. The COA stays locked until the HOD approves it.');
    }

    /**
     * Approve a request to edit. The lock and the signature are removed and
     * every value QC entered is kept, so QC corrects the wrong field and
     * submits again.
     */
    public function approveCoaEdit(Order $order, int $line, CoaEditRequest $coaEditRequest, CoaTemplateService $coa)
    {
        $user = auth()->user();

        if (!$user->canApproveCoaEdit()) {
            return back()->with('error', 'Only the COA approver (QC HOD) can approve a request to edit.');
        }

        if ((int) $coaEditRequest->order_id !== (int) $order->id
            || (int) $coaEditRequest->order_product_id !== $line) {
            abort(404);
        }

        if (!$coaEditRequest->isPending()) {
            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('error', 'This request has already been decided.');
        }

        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        $requester = $coaEditRequest->requester;

        $this->unlockTemplateCoa($order, $product, $user, function (array $previous) use ($coaEditRequest, $user) {
            $coaEditRequest->update($previous + [
                'status'     => CoaEditRequest::STATUS_APPROVED,
                'decided_by' => $user->id,
                'decided_at' => now(),
            ]);

            return $coaEditRequest;
        }, 'Approved edit request #' . $coaEditRequest->id
            . ($requester ? ' from ' . $requester->fullName() : '')
            . ' and unlocked COA');

        return redirect()->route('orders.coa', [$order->id, $line])
            ->with('success', 'Request approved. The COA is unlocked for Quality Control to correct and submit again.');
    }

    /**
     * The COA approver (QC HOD) unlocks a submitted COA without a request.
     *
     *   action=return  Return to QC: the staff who submitted it is emailed
     *                  the reason and corrects it.
     *   action=reopen  Reopen: the HOD corrects it herself (e.g. no QC staff
     *                  available). Needs COA edit rights as well.
     *
     * Either way the values are kept and the lock and signature removed; the
     * next person to submit signs it.
     */
    public function unlockCoa(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        $user = auth()->user();

        if (!$user->canApproveCoaEdit()) {
            return back()->with('error', 'Only the COA approver (QC HOD) can return or reopen a COA.');
        }

        $validated = $request->validate([
            'action' => 'required|in:return,reopen',
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Please give a reason.',
        ]);

        $action = $validated['action'];

        if ($action === 'reopen' && !$coa->userMayEdit($user)) {
            return back()->with('error', 'Only Quality Control can correct a COA. Use Return to QC instead.');
        }

        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        if (!$coa->isSubmitted($product->pivot)) {
            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('error', 'This COA has not been submitted, so it can be edited directly.');
        }

        if (CoaEditRequest::pending()->forLine($line)->exists()) {
            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('error', 'A request to edit this COA is waiting. Approve or reject it instead.');
        }

        $submitterId = $product->pivot->coa_submitted_by;
        $reason = trim($validated['reason']);
        $type = $action === 'return' ? CoaEditRequest::TYPE_RETURN : CoaEditRequest::TYPE_REOPEN;

        $unlock = $this->unlockTemplateCoa($order, $product, $user, function (array $previous) use ($order, $product, $line, $user, $reason, $type) {
            return CoaEditRequest::create($previous + [
                'order_id'         => $order->id,
                'product_id'       => $product->id,
                'order_product_id' => $line,
                'type'             => $type,
                'requested_by'     => $user->id,
                'reason'           => $reason,
                'status'           => CoaEditRequest::STATUS_APPROVED,
                'decided_by'       => $user->id,
                'decided_at'       => now(),
            ]);
        }, ($action === 'return' ? 'Returned COA to QC' : 'Reopened COA') . ': ' . $reason);

        if ($action === 'return') {
            $this->sendCoaReturnedEmail($unlock, $order, $product, $submitterId, $user, false);

            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('success', 'COA returned to QC. The staff who submitted it has been emailed.');
        }

        return redirect()->route('orders.coa', [$order->id, $line])
            ->with('success', 'COA reopened. Correct it and submit it again.');
    }

    /**
     * Remove the lock and signature from a submitted template COA, keep every
     * value, and record the unlock. $record receives the previous submitter
     * fields and returns the CoaEditRequest row it wrote.
     */
    private function unlockTemplateCoa(Order $order, Product $product, User $user, callable $record, string $summary): CoaEditRequest
    {
        $pivot  = $product->pivot;
        $before = $pivot->getAttributes();

        $previous = [
            'previous_signatory_name' => $pivot->coa_signatory_name,
            'previous_submitted_at'   => $pivot->coa_submitted_at,
        ];

        $clear = array_fill_keys(CoaTemplateService::CLEARED_ON_REOPEN, null);
        $clear['coa_updated_by'] = $user->id;
        $clear['coa_updated_at'] = now();

        $unlock = DB::transaction(function () use ($order, $product, $clear, $record, $previous) {
            // This line only. The same product on another line of the order
            // has its own COA and is not touched.
            $this->updateCoaLine($order, $product, $clear);

            return $record($previous);
        });

        ActivityLogger::recordCoaChange(
            $order,
            $before,
            array_merge($before, $clear),
            $this->coaLineName($product),
            $summary
        );

        return $unlock;
    }

    /**
     * Email the QC staff who submitted (or uploaded) a COA that the HOD has
     * returned. Skipped when there is nobody to tell, or the HOD returned her
     * own COA. A mail failure must not undo the return.
     */
    private function sendCoaReturnedEmail(CoaEditRequest $unlock, Order $order, Product $product, $recipientId, User $returnedBy, bool $isUpload): void
    {
        $recipient = $recipientId ? User::find($recipientId) : null;

        if (!$recipient || !$recipient->email || (int) $recipient->id === (int) $returnedBy->id) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(
                new CoaReturnedNotification($unlock, $order, $product, $recipient, $returnedBy, $isUpload)
            );
        } catch (\Throwable $e) {
            Log::error('COA returned email failed', [
                'unlock_id' => $unlock->id,
                'recipient' => $recipient->email,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Switch an order line between the COA template and an uploaded COA.
     * COA approver (QC HOD) only. Backup for when the COA can't be prepared
     * in TRACOM because of a system issue.
     *
     *   mode=upload    only before the template COA is submitted
     *   mode=template  only while no file has been uploaded; once a file is
     *                  uploaded it is the final COA
     */
    public function setCoaUploadMode(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        $user = auth()->user();

        if (!$user->canApproveCoaEdit()) {
            return back()->with('error', 'Only the COA approver (QC HOD) can change how this COA is prepared.');
        }

        $validated = $request->validate([
            'mode' => 'required|in:upload,template',
        ]);

        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        $pivot = $product->pivot;

        if (!$pivot->coa_required || !$coa->productHasCoa($product)) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'This product has no COA template to switch from.');
        }

        $toUpload = $validated['mode'] === 'upload';

        if ($toUpload) {
            if ($coa->isUploadMode($pivot)) {
                return redirect()->route('orderdetails', $order->id)
                    ->with('error', $product->name . ' already uses an uploaded COA.');
            }
            if ($coa->isSubmitted($pivot)) {
                return redirect()->route('orderdetails', $order->id)
                    ->with('error', 'The COA for ' . $product->name . ' has been submitted. Return or reopen it first.');
            }
        } else {
            if (!$coa->isUploadMode($pivot)) {
                return redirect()->route('orderdetails', $order->id)
                    ->with('error', $product->name . ' already uses the COA template.');
            }
            if ($pivot->coa_document) {
                return redirect()->route('orderdetails', $order->id)
                    ->with('error', 'A COA file has been uploaded for ' . $product->name . ', so it is the final COA.');
            }
        }

        $this->updateCoaLine($order, $product, ['coa_upload_mode' => $toUpload ? 1 : 0], $toUpload);

        ActivityLogger::recordCoaEvent(
            $order,
            $this->coaLineName($product),
            $toUpload ? 'Switched COA to uploaded file' : 'Switched COA back to template',
            ['coa_upload_mode' => [
                'old' => $toUpload ? 'template' : 'upload',
                'new' => $toUpload ? 'upload' : 'template',
            ]]
        );

        return redirect()->route('orderdetails', $order->id)
            ->with('success', $toUpload
                ? $product->name . ' now uses an uploaded COA. Quality Control can upload it from this page.'
                : $product->name . ' is back on the COA template.');
    }

    /**
     * Return an uploaded COA file to the QC staff who uploaded it. The COA
     * approver (QC HOD) only. The file is removed, so the line waits for a
     * corrected upload, and the uploader is emailed the reason.
     */
    public function returnCoaDocument(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        $user = auth()->user();

        if (!$user->canApproveCoaEdit()) {
            return back()->with('error', 'Only the COA approver (QC HOD) can return an uploaded COA.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Please give a reason.',
        ]);

        $product = $this->coaLine($order, $line);

        if (!$product) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        $pivot    = $product->pivot;
        $existing = $pivot->coa_document;

        if (!$existing) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'No COA file has been uploaded for ' . $product->name . '.');
        }

        $uploaderId = $pivot->coa_document_uploaded_by;
        $uploader   = $uploaderId ? User::find($uploaderId) : null;
        $reason     = trim($validated['reason']);

        $unlock = DB::transaction(function () use ($order, $product, $line, $user, $reason, $uploader, $pivot) {
            $this->updateCoaLine($order, $product, [
                'coa_document'             => null,
                'coa_document_uploaded_by' => null,
                'coa_document_uploaded_at' => null,
            ]);

            return CoaEditRequest::create([
                'order_id'                => $order->id,
                'product_id'              => $product->id,
                'order_product_id'        => $line,
                'type'                    => CoaEditRequest::TYPE_RETURN_UPLOAD,
                'requested_by'            => $user->id,
                'reason'                  => $reason,
                'status'                  => CoaEditRequest::STATUS_APPROVED,
                'decided_by'              => $user->id,
                'decided_at'              => now(),
                'previous_signatory_name' => $uploader?->fullName(),
                'previous_submitted_at'   => $pivot->coa_document_uploaded_at,
            ]);
        });

        // Same clean-up as replacing a file. The audit entry keeps its name.
        if (!$this->coaFileUsedElsewhere('coa_document', $existing, $line)) {
            foreach ([
                public_path('storage/coa_documents/' . $existing),
                storage_path('app/public/coa_documents/' . $existing),
            ] as $path) {
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }

        ActivityLogger::recordCoaChange(
            $order,
            ['coa_document' => $existing],
            ['coa_document' => null],
            $this->coaLineName($product),
            'Returned uploaded COA to QC: ' . $reason
        );

        $this->sendCoaReturnedEmail($unlock, $order, $product, $uploaderId, $user, true);

        return redirect()->route('orderdetails', $order->id)
            ->with('success', 'Uploaded COA returned to QC. The staff who uploaded it has been emailed.');
    }

    /**
     * Reject a request to edit. The COA stays locked as it is.
     */
    public function rejectCoaEdit(Order $order, int $line, CoaEditRequest $coaEditRequest)
    {
        $user = auth()->user();

        if (!$user->canApproveCoaEdit()) {
            return back()->with('error', 'Only the COA approver (QC HOD) can reject a request to edit.');
        }

        if ((int) $coaEditRequest->order_id !== (int) $order->id
            || (int) $coaEditRequest->order_product_id !== $line) {
            abort(404);
        }

        if (!$coaEditRequest->isPending()) {
            return redirect()->route('orders.coa', [$order->id, $line])
                ->with('error', 'This request has already been decided.');
        }

        $product = $this->coaLine($order, $line);
        $lineName = $product ? $this->coaLineName($product) : null;

        $coaEditRequest->update([
            'status'     => CoaEditRequest::STATUS_REJECTED,
            'decided_by' => $user->id,
            'decided_at' => now(),
        ]);

        ActivityLogger::recordCoaEvent(
            $order,
            $lineName,
            'Rejected edit request #' . $coaEditRequest->id,
            ['edit_request' => ['old' => 'pending', 'new' => 'rejected']]
        );

        return redirect()->route('orders.coa', [$order->id, $line])
            ->with('success', 'Request rejected. The COA stays locked.');
    }

    /**
     * One order line: the product as loaded through the order, carrying that
     * line's own pivot row (order_product.id = $line).
     *
     * COAs belong to lines, not products. The same product can be on one
     * order more than once (two patients, or a 100B and a 50B Exosome), so a
     * line is never looked up by product id.
     */
    private function coaLine(Order $order, int $line): ?Product
    {
        return $order->products()->wherePivot('id', $line)->first();
    }

    /**
     * Write to one order line's row and nothing else.
     *
     * updateExistingPivot() matches on product_id, so on an order with the
     * same product twice it wrote to every one of those lines. This matches
     * on the line's own id. With $unsubmittedOnly the write is skipped if the
     * COA was submitted in the meantime.
     */
    private function updateCoaLine(Order $order, Product $line, array $data, bool $unsubmittedOnly = false): int
    {
        $query = DB::table('order_product')
            ->where('id', $line->pivot->id)
            ->where('order_id', $order->id);

        if ($unsubmittedOnly) {
            $query->whereNull('coa_submitted_at');
        }

        return $query->update($data + ['updated_at' => now()]);
    }

    /**
     * Product name for the audit log, with the patient when there is one, so
     * two lines of the same product can be told apart.
     */
    private function coaLineName(Product $line): string
    {
        $patient = trim((string) ($line->pivot->patient_name ?? ''));

        return $patient !== '' ? $line->name . ' (' . $patient . ')' : $line->name;
    }

    /**
     * Whether another order line still points at this file. Before COAs were
     * per line, an upload could be written to every line of the same product,
     * so an old file may be shared; it must not be deleted from under them.
     */
    private function coaFileUsedElsewhere(string $column, string $filename, int $exceptLine): bool
    {
        return DB::table('order_product')
            ->where($column, $filename)
            ->where('id', '!=', $exceptLine)
            ->exists();
    }

    /**
     * Store a COA that QC produced outside the system.
     *
     * Products configured with coa_template = 'none' have no artwork for the
     * generator to draw on — NK Immunophenotyping test is a blood test, and
     * its analysis report comes out of the lab system already finished. This
     * accepts that PDF and attaches it to the order line instead.
     *
     * Deliberately separate from the generated-COA columns, so an uploaded
     * certificate can never overwrite a generated one or vice versa.
     */
    public function uploadCoaDocument(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        if (!$coa->userMayEdit(auth()->user())) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Only the Quality Control department can upload a COA.');
        }

        $product = $this->coaLine($order, $line);
        $orderProduct = $product;

        if (!$orderProduct) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Product not found in this order.');
        }

        // Products with no template, or lines the HOD switched to an uploaded
        // COA. Everything else uses the COA template.
        if (!$orderProduct->pivot->coa_required || !$coa->lineUsesUpload($orderProduct)) {
            return redirect()->route('orderdetails', $order->id)
                ->with('error', $product->name . ' uses the COA template. The HOD can switch it to an uploaded COA if TRACOM has a problem.');
        }

        $request->validate([
            'coa_document' => 'required|file|mimes:pdf|max:' . self::COA_DOCUMENT_MAX_KB,
        ], [
            'coa_document.required' => 'Please choose a COA file to upload.',
            'coa_document.mimes'    => 'The COA must be a PDF file.',
            'coa_document.max'      => 'The COA must not be larger than '
                                        . intdiv(self::COA_DOCUMENT_MAX_KB, 1024) . ' MB.',
        ]);

        try {
            $file = $request->file('coa_document');
            // Order, product and line in the name, so two lines of the same
            // product never share a file.
            $filename = 'coa_doc_' . $order->id . '_' . $product->id . '_' . $line . '_' . time() . '.pdf';

            $file->storeAs('public/coa_documents', $filename);

            // The storage symlink is unreliable on this host, so the file that
            // actually gets served is a plain copy under public/.
            $publicDir = public_path('storage/coa_documents');
            if (!file_exists($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            copy(
                storage_path('app/public/coa_documents/' . $filename),
                $publicDir . '/' . $filename
            );

            // Replacing an existing certificate removes the old file, so the
            // activity log becomes the only record that it ever existed.
            $existing = $orderProduct->pivot->coa_document;
            if ($existing && !$this->coaFileUsedElsewhere('coa_document', $existing, $line)) {
                $oldPublic = public_path('storage/coa_documents/' . $existing);
                if (file_exists($oldPublic)) {
                    @unlink($oldPublic);
                }
                $oldStorage = storage_path('app/public/coa_documents/' . $existing);
                if (file_exists($oldStorage)) {
                    @unlink($oldStorage);
                }
            }

            $this->updateCoaLine($order, $product, [
                'coa_document'             => $filename,
                'coa_document_uploaded_by' => auth()->id(),
                'coa_document_uploaded_at' => now(),
            ]);

            ActivityLogger::recordCoaChange(
                $order,
                ['coa_document' => $existing],
                ['coa_document' => $filename],
                $this->coaLineName($product),
                $existing ? 'Replaced uploaded COA' : 'Uploaded COA'
            );

            return redirect()->route('orderdetails', $order->id)
                ->with('success', 'COA uploaded successfully.');
        } catch (\Exception $e) {
            Log::error('COA document upload failed', [
                'order_id'         => $order->id,
                'order_product_id' => $line,
                'product_id'       => $product->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('orderdetails', $order->id)
                ->with('error', 'Error uploading COA: ' . $e->getMessage());
        }
    }

    /**
     * Store the morphology-of-cells micrograph shown on page 2.
     *
     * The image is resized to fit the slot on the certificate, so QC can
     * supply whatever the microscope produced without preparing an exact size.
     */
    public function uploadCoaMorphology(Request $request, Order $order, int $line, CoaTemplateService $coa)
    {
        $product = null;

        try {
            if (!$coa->userMayEdit(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the Quality Control department can upload a morphology image.'
                ], 403);
            }

            $product = $this->coaLine($order, $line);
            $orderProduct = $product;

            if (!$orderProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in this order.'
                ], 404);
            }

            if ($coa->isSubmitted($orderProduct->pivot)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This COA has been submitted and is locked. Request an edit to change it.'
                ], 409);
            }

            if ($coa->isUploadMode($orderProduct->pivot)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The HOD switched this product to an uploaded COA, so the COA template can\'t be used.'
                ], 409);
            }

            $request->validate([
                'morphology_image' => 'required|image|mimes:jpeg,jpg,png|max:' . self::MORPHOLOGY_MAX_KB,
            ]);

            $templateKey = $coa->resolveForOrderLine($product);

            if (!$coa->acceptsMorphologyImage($templateKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This COA template has no morphology image.'
                ], 422);
            }

            $file = $request->file('morphology_image');
            // Order, product and line in the name, so two lines of the same
            // product never share a file.
            $filename = 'coa_' . $order->id . '_' . $product->id . '_' . $line . '_' . time()
                . '.' . $file->getClientOriginalExtension();

            $file->storeAs('public/coa_morphology', $filename);

            $publicDir = public_path('storage/coa_morphology');
            if (!file_exists($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            $publicPath = $publicDir . '/' . $filename;
            copy(storage_path('app/public/coa_morphology/' . $filename), $publicPath);

            // Resize the copy that is actually served.
            $this->resizeMorphologyImage(
                $publicPath,
                $coa->get($templateKey)['coordinates']['page2']['morphology_slot']
            );

            // Remove the previous file if there was one, unless another line
            // still points at it (copied there before COAs were per line).
            $existing = $orderProduct->pivot->coa_morphology_image;
            if ($existing && !$this->coaFileUsedElsewhere('coa_morphology_image', $existing, $line)) {
                $oldPublic = public_path('storage/coa_morphology/' . $existing);
                if (file_exists($oldPublic)) {
                    @unlink($oldPublic);
                }
                $oldStorage = storage_path('app/public/coa_morphology/' . $existing);
                if (file_exists($oldStorage)) {
                    @unlink($oldStorage);
                }
            }

            // Store just the filename; the view builds the /storage/... URL.
            $this->updateCoaLine($order, $product, [
                'coa_morphology_image' => $filename,
                'coa_updated_by'       => auth()->id(),
                'coa_updated_at'       => now(),
            ], true);

            // The previous file has already been deleted from disk by this
            // point, so the log is the only remaining record of what the
            // certificate showed before.
            ActivityLogger::recordCoaChange(
                $order,
                ['coa_morphology_image' => $existing],
                ['coa_morphology_image' => $filename],
                $this->coaLineName($product),
                $existing ? 'Replaced COA morphology image' : 'Uploaded COA morphology image'
            );

            return response()->json([
                'success' => true,
                'message' => 'Morphology image uploaded.',
                'url'     => asset('storage/coa_morphology/' . $filename),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error uploading COA morphology image', [
                'order_id' => $order->id,
                'order_product_id' => $line,
                'product_id' => $product?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scale an uploaded micrograph to the slot on the certificate.
     *
     * Uses GD directly so this works on the current shared hosting without
     * adding a dependency. Aspect ratio is preserved and the result is
     * centre-cropped, so a portrait photo is not stretched across the slot.
     */
    private function resizeMorphologyImage(string $file, array $slot): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            return; // GD unavailable: keep the original upload as-is.
        }

        // The slot is a percentage of a 540x780pt page; render at 3x for print.
        $targetW = (int) round($slot['w'] / 100 * 540 * 3);
        $targetH = (int) round($slot['h'] / 100 * 780 * 3);

        if ($targetW < 10 || $targetH < 10) {
            return;
        }

        $info = @getimagesize($file);
        if (!$info) {
            return;
        }

        [$srcW, $srcH, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG  => @imagecreatefrompng($file),
            default        => null,
        };

        if (!$src) {
            return;
        }

        $srcRatio    = $srcW / $srcH;
        $targetRatio = $targetW / $targetH;

        if ($srcRatio > $targetRatio) {
            $outW = $targetW;
            $outH = (int) round($targetW / $srcRatio);
        } else {
            $outH = $targetH;
            $outW = (int) round($targetH * $srcRatio);
        }

        if ($outW < 1 || $outH < 1) {
            return;
        }

        $dst = imagecreatetruecolor($outW, $outH);

        // Preserve PNG transparency; for JPEG this is a plain resize.
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled(
            $dst, $src,
            0, 0,
            0, 0,
            $outW, $outH,
            $srcW, $srcH
        );

        if ($type === IMAGETYPE_PNG) {
            imagepng($dst, $file);
        } else {
            imagejpeg($dst, $file, 90);
        }

        imagedestroy($dst);
        imagedestroy($src);
    }

    /**
     * Current COA values for an order line, with sensible defaults.
     *
     * Patient name comes from the order line itself so QC does not retype
     * something the system already knows.
     */
    private function coaValues($orderProduct): array
    {
        $pivot = $orderProduct->pivot;

        return [
            'coa_number'        => $pivot->coa_number ?? '',
            'patient_name'      => $pivot->patient_name ?? '',
            'batch_number'      => $pivot->batch_number ?? '',
            'product_date'      => $pivot->coa_product_date ?? '',
            'mfg_date'          => $pivot->coa_mfg_date ?? '',
            'expiry_date'       => $pivot->coa_expiry_date ?? '',
            'viable_cell_count' => $pivot->coa_viable_cell_count ?? '',
            'signature_date'    => $pivot->coa_signature_date ?? '',
            'immuno_cd73'       => $pivot->coa_immuno_cd73 ?? '',
            'immuno_cd90'       => $pivot->coa_immuno_cd90 ?? '',
            'immuno_cd105'      => $pivot->coa_immuno_cd105 ?? '',
            'immuno_negative'   => $pivot->coa_immuno_negative ?? '',
            'qc_document_number'=> $pivot->qc_document_number ?? '',
            'morphology_image'  => $pivot->coa_morphology_image
                ? asset('storage/coa_morphology/' . $pivot->coa_morphology_image)
                : null,
        ];
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
            if (
                isset($request->batch_number) && !empty($request->batch_number) &&
                $existingPivot->batch_number !== $request->batch_number
            ) {
                if ($user->department === 'Cell Lab' || $user->isQualityControl() || $user->role === 'superadmin') {
                    $updateData['batch_number'] = $request->batch_number;
                } else {
                    return redirect()->back()->with('error', 'Only Cell Lab and Quality Control departments can edit batch numbers.');
                }
            } elseif ($existingPivot->batch_number) {
                $updateData['batch_number'] = $existingPivot->batch_number;
            } else {
                $updateData['batch_number'] = $request->batch_number ?? null;
            }

            // Handle QC document number permissions
            if (
                isset($request->qc_document_number) && !empty($request->qc_document_number) &&
                $existingPivot->qc_document_number !== $request->qc_document_number
            ) {
                if ($user->isQualityControl() || $user->role === 'superadmin') {
                    $updateData['qc_document_number'] = $request->qc_document_number;
                } else {
                    return redirect()->back()->with('error', 'Only Quality Control department can edit QC document numbers.');
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
            if (
                $order->status === 'new' &&
                (!empty($updateData['batch_number']) || !empty($updateData['qc_document_number']) || !empty($updateData['prepared_by']))
            ) {
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
            // Cancelling needs a reason, same as a pickup.
            'cancel_reason' => 'required_if:status,cancel|nullable|string|max:1000',
        ], [
            'cancel_reason.required_if' => 'Please enter a reason for cancelling this order.',
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
                if (!($user->isQualityControl() || $user->department === 'Cell Lab' || $user->role === 'admin' || $user->role === 'superadmin')) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Only Quality Control, Cell Lab, Admin or Superadmin can mark orders as preparing.');
                }
            }

            if ($request->status === 'ready' && $order->status !== 'ready') {
                if (!$user->isQualityControl() && $user->department !== 'Cell Lab' && $user->role !== 'admin' && $user->role !== 'superadmin') {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Only Quality Control or Cell Lab departments can mark orders as ready.');
                }
                // Set item_ready_at if not already set
                if (!$order->item_ready_at) {
                    $order->item_ready_at = now();
                }
            }

            if ($request->status === 'cancel' && $order->status !== 'cancel') {
                $order->cancel_reason = $request->cancel_reason;
                $order->cancelled_by = $user->username;
                $order->cancelled_at = now();
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
                    'temp_before_delivery' => 'required|string|max:50',
                    'temp_after_delivery' => 'required|string|max:50',
                    'delivery_photo' => 'required|image|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
                ]);

                // Parse the datetime with flexible format handling
                try {
                    $dateTimeString = trim($request->delivery_datetime);
                    
                    // Clean duplicate time patterns (e.g., "02.01.2026 12:10 PM 12:10" -> "02.01.2026 12:10 PM")
                    // Remove any duplicate time pattern at the end (handles both with and without AM/PM)
                    $dateTimeString = preg_replace('/\s+(\d{1,2}:\d{2}(\s*(AM|PM))?)\s+\d{1,2}:\d{2}$/i', ' $1', $dateTimeString);
                    $dateTimeString = trim($dateTimeString);

                    // Log the incoming datetime string for debugging
                    Log::info('Datetime parsing attempt', [
                        'original_string' => $request->delivery_datetime,
                        'cleaned_string' => $dateTimeString,
                        'order_id' => $id,
                        'user' => Auth::user()->name
                    ]);

                    // Try multiple formats to handle different flatpickr outputs
                    // Note: flatpickr format "d.m.Y h:i K" outputs "d.m.Y h:i A" (K becomes A in PHP)
                    $formats = [
                        'd.m.Y h:i A',    // 02.01.2026 12:10 PM (flatpickr format with AM/PM - PRIMARY)
                        'd.m.Y H:i',      // 31.12.2023 15:30 (24-hour format)
                        'Y-m-d H:i',      // 2023-12-31 15:30 (ISO format)
                        'd/m/Y H:i',      // 31/12/2023 15:30
                        'm/d/Y H:i',      // 12/31/2023 15:30
                        'd-m-Y H:i',      // 31-12-2023 15:30
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
                $order->temp_before_delivery = $request->temp_before_delivery;
                $order->temp_after_delivery = $request->temp_after_delivery;

                // Handle delivery photo upload
                if ($request->hasFile('delivery_photo')) {
                    $file = $request->file('delivery_photo');

                    // Generate a unique filename
                    $filename = 'delivery_' . $order->id . '_' . time() . '.' . $file->getClientOriginalExtension();

                    // Store the file in the order_photos directory
                    $path = $file->storeAs('public/order_photos', $filename);

                    // Copy to public/storage/order_photos for cPanel compatibility
                    $publicPath = public_path('storage/order_photos/' . $filename);
                    $publicDir = public_path('storage/order_photos');
                    if (!file_exists($publicDir)) {
                        mkdir($publicDir, 0755, true);
                    }
                    copy(storage_path('app/public/order_photos/' . $filename), $publicPath);

                    // Add to delivery_photos array
                    $order->addDeliveryPhoto($filename);

                    Log::info('Delivery photo uploaded', [
                        'order_id' => $order->id,
                        'filename' => $filename,
                        'path' => $path,
                        'public_path' => $publicPath
                    ]);
                }
            }

            $order->status = $request->status;
            $order->save();

            DB::commit();            
            // === Notify the ORDER CREATOR when status changes (QC update notification) ===
            // Maps internal status values to the labels staff expect to see.
            $creatorNotifyLabels = [
                'ready'     => 'Ready for Delivery',
                'delivered' => 'Order Delivered',
                'preparing' => 'In Progress',
                'cancel'    => 'Order Cancelled',
            ];

            if (
                array_key_exists($request->status, $creatorNotifyLabels)
                && $oldStatus !== $request->status
            ) {
                $notifyStatus = $request->status;
                $notifyLabel  = $creatorNotifyLabels[$request->status];

                dispatch(function () use ($order, $notifyStatus, $notifyLabel) {
                    $emailController = new EmailController();
                    $emailController->sendOrderStatusUpdateNotification($order, $notifyStatus, $notifyLabel);
                })->afterResponse();
            }
            // === end creator notification ===
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
            // Notify the ORDER CREATOR that their order is ready (creator notification)
            dispatch(function () use ($order) {
                $emailController = new EmailController();
                $emailController->sendOrderStatusUpdateNotification($order, 'ready', 'Ready for Delivery');
            })->afterResponse();

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
            if (
                !(Auth::user()->isQualityControl() ||
                    Auth::user()->department === 'Cell Lab' ||
                    Auth::user()->role === 'admin' ||
                    Auth::user()->role === 'superadmin')
            ) {
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

            foreach ($order->products as $product) {
                if ($product->pivot->status === 'ready') {
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
                'products' => $order->products->map(function ($p) {
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
        // Increase execution time and memory limit for large file uploads
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');

        try {
            $order = Order::findOrFail($orderId);

            // Check if user is admin or superadmin
            $isAdmin = Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin';

            // Allow upload if:
            // 1. Order status is 'preparing' or 'ready' (normal flow for all users)
            // 2. Admin/superadmin can upload for any status including delivered/canceled
            if (!$isAdmin && $order->status !== 'preparing' && $order->status !== 'ready') {
                return redirect()->back()->with('error', 'Photo can only be uploaded when order is in preparing or ready status.');
            }

            // Enhanced validation with better error messages for both single and multiple uploads
            $validationRules = [];
            $validationMessages = [];

            // Support both single and multiple file uploads
            if ($request->hasFile('order_photos')) {
                // Multiple files
                $validationRules['order_photos.*'] = 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:51200';
                $validationMessages = array_merge($validationMessages, [
                    'order_photos.*.required' => 'Please select valid images to upload.',
                    'order_photos.*.image' => 'All uploaded files must be images.',
                    'order_photos.*.mimes' => 'Images must be JPEG, PNG, GIF, WebP, or HEIC files.',
                    'order_photos.*.max' => 'Each image size must not exceed 50MB. Please compress your images and try again.'
                ]);
            } else {
                // Single file (backward compatibility)
                $validationRules['order_photo'] = 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:51200';
                $validationMessages = array_merge($validationMessages, [
                    'order_photo.required' => 'Please select an image to upload.',
                    'order_photo.image' => 'The uploaded file must be an image.',
                    'order_photo.mimes' => 'Image must be a JPEG, PNG, GIF, WebP, or HEIC file.',
                    'order_photo.max' => 'Image size must not exceed 50MB. Please compress your image and try again.'
                ]);
            }

            $validator = Validator::make($request->all(), $validationRules, $validationMessages);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Upload failed: ' . $validator->errors()->first());
            }

            $uploadedFiles = [];
            $totalSize = 0;
            $maxFileSize = 52428800; // 50MB in bytes

            // Handle multiple files or single file
            if ($request->hasFile('order_photos')) {
                $files = $request->file('order_photos');
                if (!is_array($files)) {
                    $files = [$files];
                }
            } else {
                $files = [$request->file('order_photo')];
            }

            // Validate and process each file
            foreach ($files as $index => $file) {
                // Additional file validation
                if (!$file || !$file->isValid()) {
                    return redirect()->back()->with('error', 'Invalid file upload at position ' . ($index + 1) . '. Please try again.');
                }

                // Check actual file size (double-check)
                if ($file->getSize() > $maxFileSize) {
                    return redirect()->back()->with('error', 'File size exceeds 50MB limit for file: ' . $file->getClientOriginalName() . '. Please compress your image and try again.');
                }

                $totalSize += $file->getSize();

                // Generate unique filename with timestamp and order ID
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = 'order_' . $order->id . '_' . time() . '_' . uniqid() . '.' . $extension;

                // Store the uploaded photo with error handling
                try {
                    $path = $file->storeAs('public/order_photos', $filename);

                    if (!$path) {
                        throw new \Exception('Failed to store uploaded file: ' . $originalName);
                    }

                    // Verify file was actually saved
                    if (!\Storage::exists($path)) {
                        throw new \Exception('File upload verification failed for: ' . $originalName);
                    }

                    // Copy to public/storage/order_photos for cPanel compatibility
                    $publicPath = public_path('storage/order_photos/' . $filename);
                    $publicDir = public_path('storage/order_photos');

                    // Create directory if it doesn't exist
                    if (!file_exists($publicDir)) {
                        mkdir($publicDir, 0755, true);
                    }

                    // Copy file to public folder
                    copy(storage_path('app/public/order_photos/' . $filename), $publicPath);

                    $uploadedFiles[] = [
                        'filename' => $filename,
                        'original_name' => $originalName,
                        'size' => $file->getSize()
                    ];

                } catch (\Exception $e) {
                    Log::error('File storage error for Order #' . $order->id . ': ' . $e->getMessage());

                    // Clean up any already uploaded files from BOTH locations
                    foreach ($uploadedFiles as $uploadedFile) {
                        \Storage::delete('public/order_photos/' . $uploadedFile['filename']);
                        // Also delete from public folder
                        $publicFile = public_path('storage/order_photos/' . $uploadedFile['filename']);
                        if (file_exists($publicFile)) {
                            unlink($publicFile);
                        }
                    }

                    return redirect()->back()->with('error', 'Failed to save uploaded image: ' . $originalName . '. Please try again.');
                }
            }

            // If this is a single file upload (backward compatibility), delete old photo
            if (!$request->hasFile('order_photos') && $order->order_photo) {
                \Storage::delete('public/order_photos/' . $order->order_photo);
                // Also delete from public folder
                $publicFile = public_path('storage/order_photos/' . $order->order_photo);
                if (file_exists($publicFile)) {
                    unlink($publicFile);
                }
            }

            // Update the order with new photos
            foreach ($uploadedFiles as $uploadedFile) {
                $order->addPhoto($uploadedFile['filename']);
            }
            $order->save();

            // Log successful upload
            $fileCount = count($uploadedFiles);
            $fileNames = implode(', ', array_column($uploadedFiles, 'filename'));
            Log::info('Photo(s) uploaded successfully for Order #' . $order->id . ' - Files: ' . $fileNames . ' - Total Size: ' . round($totalSize / 1024 / 1024, 2) . 'MB');

            $successMessage = $fileCount === 1
                ? 'Order photo uploaded successfully! (' . round($totalSize / 1024 / 1024, 2) . 'MB)'
                : $fileCount . ' order photos uploaded successfully! (Total: ' . round($totalSize / 1024 / 1024, 2) . 'MB)';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'file_count' => $fileCount,
                    'uploaded_files' => $uploadedFiles,
                    'total_size' => $totalSize
                ]);
            }

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Photo upload error for Order #' . $orderId . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while uploading the photo. Please try again.');
        }
    }



    /**
     * Delete all order photos from storage and database.
     */
    public function deleteOrderPhoto($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Block deletion for delivered orders
        if ($order->status === 'delivered') {
            return redirect()->back()->with('error', 'Cannot delete photos from delivered orders. Photos are retained for record-keeping purposes.');
        }

        // Get all photos
        $allPhotos = $order->getAllPhotos();

        // Only allow delete if order has photos
        if (empty($allPhotos)) {
            return redirect()->back()->with('error', 'No photos to delete.');
        }

        // Delete all files from storage
        foreach ($allPhotos as $photo) {
            \Storage::delete('public/order_photos/' . $photo);
            // Also delete from public folder
            $publicFile = public_path('storage/order_photos/' . $photo);
            if (file_exists($publicFile)) {
                unlink($publicFile);
            }
        }

        // Remove all photo references from the order
        $order->order_photo = null;
        $order->order_photos = null;
        $order->save();

        $photoCount = count($allPhotos);
        $successMessage = $photoCount === 1
            ? 'Order photo deleted successfully!'
            : $photoCount . ' order photos deleted successfully!';

        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * Delete a specific order photo from storage and database.
     */
    public function deleteSpecificOrderPhoto($orderId, $filename)
    {
        $order = Order::findOrFail($orderId);

        // Block deletion for delivered orders
        if ($order->status === 'delivered') {
            return redirect()->back()->with('error', 'Cannot delete photos from delivered orders. Photos are retained for record-keeping purposes.');
        }

        // Get all photos
        $allPhotos = $order->getAllPhotos();

        // Check if the photo exists
        if (!in_array($filename, $allPhotos)) {
            return redirect()->back()->with('error', 'Photo not found.');
        }

        // Delete the file from storage
        \Storage::delete('public/order_photos/' . $filename);
        // Also delete from public folder
        $publicFile = public_path('storage/order_photos/' . $filename);
        if (file_exists($publicFile)) {
            unlink($publicFile);
        }

        // Remove the photo from the order
        $order->removePhoto($filename);
        $order->save();

        return redirect()->back()->with('success', 'Photo deleted successfully!');
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
            'collection_date' => 'nullable|date',
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

            // Update delivery date, time, ready time, and collection date
            $order->pickup_delivery_date = $request->pickup_delivery_date;
            $order->pickup_delivery_time = $request->pickup_delivery_time;
            $order->item_ready_at = $request->item_ready_time;
            $order->collection_date = $request->collection_date;
            $order->save();

            // Create new datetime and ready time for comparison
            $newDateTime = Carbon::parse($request->pickup_delivery_date . ' ' . $request->pickup_delivery_time);
            $newReadyTime = Carbon::parse($request->item_ready_time)->format('g:i A');

            DB::commit();

            // [CHANGED] Send delivery-schedule-change notification to the ORDER CREATOR only
            // (was previously a multi-recipient email). Uses the detailed [ORDER UPDATE] template
            // with a Schedule Change section showing old -> new values.
            $scheduleChanges = [];
            if ($originalDateTime && $newDateTime) {
                $scheduleChanges['Delivery/Pickup Date & Time'] = [
                    'from' => $originalDateTime->format('F j, Y g:i A'),
                    'to'   => $newDateTime->format('F j, Y g:i A'),
                ];
            }
            if ($originalReadyTime && $newReadyTime) {
                $scheduleChanges['Ready Time'] = [
                    'from' => $originalReadyTime,
                    'to'   => $newReadyTime,
                ];
            }

            dispatch(function () use ($order, $scheduleChanges) {
                $emailController = new EmailController();
                $emailController->sendOrderStatusUpdateNotification(
                    $order,
                    'schedule_update',
                    'Delivery Schedule Updated',
                    $scheduleChanges
                );
            })->afterResponse();

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
                'signature_date' => 'required|date',
                'signature_time' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Parse the date and time from form
            $date = $request->signature_date;
            $time = $request->signature_time;

            // Handle 12-hour format time (e.g., "02:30 PM") from Flatpickr
            try {
                $signatureDateTime = \Carbon\Carbon::createFromFormat('Y-m-d h:i A', $date . ' ' . $time);
            } catch (\Exception $e) {
                // Fallback to 24-hour format if 12-hour parsing fails
                try {
                    $signatureDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
                } catch (\Exception $e2) {
                    // If both fail, try basic parsing
                    $signatureDateTime = \Carbon\Carbon::parse($date . ' ' . $time);
                }
            }

            // Update order with signature data
            $order->update([
                'collected_by' => $request->collected_by,
                'signature_data' => $request->signature_data,
                'signature_date' => $signatureDateTime,
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
    /**
     * Handle uploading a delivery photo by a dispatcher.
     */
    public function uploadDeliveryPhoto(Request $request, $orderId)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $order = Order::findOrFail($orderId);
            $user = Auth::user();

            // Check if user is dispatcher or admin/superadmin
            $isDispatcher = $user->department === 'Dispatcher';
            $isAdmin = $user->role === 'admin' || $user->role === 'superadmin';

            if (!$isDispatcher && !$isAdmin) {
                return redirect()->back()->with('error', 'Only dispatchers and admins can upload delivery photos.');
            }

            $validator = Validator::make($request->all(), [
                'delivery_photos.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:51200',
            ], [
                'delivery_photos.*.required' => 'Please select valid images to upload.',
                'delivery_photos.*.image' => 'All uploaded files must be images.',
                'delivery_photos.*.mimes' => 'Images must be JPEG, PNG, GIF, WebP, or HEIC files.',
                'delivery_photos.*.max' => 'Each image size must not exceed 50MB.'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput()->with('error', 'Upload failed: ' . $validator->errors()->first());
            }

            $uploadedFiles = [];
            $totalSize = 0;
            $maxFileSize = 52428800;

            if ($request->hasFile('delivery_photos')) {
                $files = $request->file('delivery_photos');
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $index => $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    if ($file->getSize() > $maxFileSize) {
                        return redirect()->back()->with('error', 'File size exceeds 50MB limit for file: ' . $file->getClientOriginalName());
                    }

                    $totalSize += $file->getSize();
                    $extension = $file->getClientOriginalExtension();
                    $filename = 'delivery_' . $order->id . '_' . time() . '_' . uniqid() . '.' . $extension;

                    try {
                        $path = $file->storeAs('public/order_photos', $filename);
                        if (!$path) {
                            throw new \Exception('Failed to store uploaded file.');
                        }

                        // Copy to public/storage/order_photos for cPanel compatibility
                        $publicPath = public_path('storage/order_photos/' . $filename);
                        $publicDir = public_path('storage/order_photos');
                        if (!file_exists($publicDir)) {
                            mkdir($publicDir, 0755, true);
                        }
                        copy(storage_path('app/public/order_photos/' . $filename), $publicPath);

                        $uploadedFiles[] = $filename;
                    } catch (\Exception $e) {
                        Log::error('Delivery photo storage error: ' . $e->getMessage());
                    }
                }
            }

            foreach ($uploadedFiles as $filename) {
                $order->addDeliveryPhoto($filename);
            }
            $order->save();

            $fileCount = count($uploadedFiles);
            $successMessage = $fileCount === 1 ? 'Delivery photo uploaded successfully!' : $fileCount . ' delivery photos uploaded successfully!';

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Delivery photo upload error for Order #' . $orderId . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while uploading. Please try again.');
        }
    }

    /**
     * Delete a specific delivery photo.
     */
    public function deleteDeliveryPhoto($orderId, $filename)
    {
        $order = Order::findOrFail($orderId);
        $user = Auth::user();

        $isDispatcher = $user->department === 'Dispatcher';
        $isAdmin = $user->role === 'admin' || $user->role === 'superadmin';

        if (!$isDispatcher && !$isAdmin) {
            return redirect()->back()->with('error', 'You do not have permission to delete delivery photos.');
        }

        if (!$order->delivery_photos || !in_array($filename, $order->delivery_photos)) {
            return redirect()->back()->with('error', 'Photo not found.');
        }

        // Delete from storage
        \Storage::delete('public/order_photos/' . $filename);
        $publicFile = public_path('storage/order_photos/' . $filename);
        if (file_exists($publicFile)) {
            unlink($publicFile);
        }

        // Remove from database
        $order->removeDeliveryPhoto($filename);
        $order->save();

        return redirect()->back()->with('success', 'Delivery photo deleted successfully!');
    }
}