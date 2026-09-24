<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();
        
        // Handle search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by Active / Inactive
        if (in_array($request->get('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        // Filter by what the product is used for (Order / Pickup)
        if (array_key_exists($request->get('usage_type'), Product::USAGE_TYPES)) {
            $query->where('usage_type', $request->get('usage_type'));
        }

        // Date range filtering removed along with its form field: it filtered on
        // created_at, which is not a useful question to ask of a product list.
        
        // Handle stock status filtering
        if ($request->has('stock_status') && !empty($request->stock_status) && $request->stock_status != 'all') {
            if ($request->stock_status == 'in_stock') {
                $query->where('stock', '>', 0);
            } elseif ($request->stock_status == 'low_stock') {
                $query->whereBetween('stock', [1, 10]);
            } elseif ($request->stock_status == 'out_of_stock') {
                $query->where('stock', '<=', 0);
            }
        }
        
        $products = $query->latest()->paginate(10);
        return view('settings.product', compact('products'));
    }


    /**
     * Validation rules shared by store() and update().
     *
     * Everything on the form is compulsory. The one role-dependent rule is the
     * COA template: only a superadmin may leave it unset (the "ask when
     * generating" state), because only a superadmin can resolve that state
     * later on the COA screen. Admins must commit to a certificate up front.
     *
     * Pickup items have no price, stock or COA, so those fields are excluded
     * from validation when usage_type is 'pickup' and productAttributes()
     * fills in fixed values instead.
     */
    private function productRules(): array
    {
        $isSuperadmin = auth()->user()->role === 'superadmin';

        $coaKeys = array_keys(app(\App\Services\CoaTemplateService::class)->productChoices());
        $coaKeys[] = 'none';

        $pickupSkip = 'exclude_if:usage_type,' . Product::USAGE_PICKUP;

        return [
            'usage_type'  => ['required', \Illuminate\Validation\Rule::in(array_keys(Product::USAGE_TYPES))],
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => $pickupSkip . '|required|numeric|min:0',
            'stock'       => $pickupSkip . '|required|integer|min:0',
            'coa_template' => $isSuperadmin
                ? [$pickupSkip, 'nullable', \Illuminate\Validation\Rule::in(array_merge($coaKeys, ['']))]
                : [$pickupSkip, 'required', \Illuminate\Validation\Rule::in($coaKeys)],
            // Sent as a hidden 0 plus the checkbox, so an unticked box still
            // arrives as a value.
            'requires_patient_details' => $pickupSkip . '|nullable|boolean',
            'is_active'                => 'nullable|boolean',
        ];
    }

    /**
     * Build the writable attributes from validated input.
     *
     * Assigning field by field rather than passing $request->all() to
     * Product::create/update, so a hand-crafted POST cannot reach a column the
     * form never offered.
     */
    private function productAttributes(array $validated): array
    {
        if ($validated['usage_type'] === Product::USAGE_PICKUP) {
            // Stock 0 also keeps pickup items out of every "stock > 0" product
            // list on the order side.
            return [
                'usage_type'               => Product::USAGE_PICKUP,
                'is_active'                => (bool) ($validated['is_active'] ?? true),
                'name'                     => $validated['name'],
                'description'              => $validated['description'],
                'price'                    => 0,
                'stock'                    => 0,
                'coa_template'             => 'none',
                'requires_patient_details' => false,
            ];
        }

        return [
            'usage_type'               => Product::USAGE_ORDER,
            'is_active'                => (bool) ($validated['is_active'] ?? true),
            'name'                     => $validated['name'],
            'description'              => $validated['description'],
            'price'                    => $validated['price'],
            'stock'                    => $validated['stock'],
            'coa_template'             => $validated['coa_template'] ?? '',
            'requires_patient_details' => (bool) ($validated['requires_patient_details'] ?? false),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->productRules());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Product::create($this->productAttributes($validator->validated()));

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), $this->productRules());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product->update($this->productAttributes($validator->validated()));

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // A product that appears on any order or pickup cannot be deleted.
        // order_product.product_id is ON DELETE CASCADE, so deleting the product
        // silently strips the item from past orders and they end up showing
        // "No products". pickup_items.product_id is ON DELETE RESTRICT, which
        // would instead fail with a database error.
        $orderCount = \Illuminate\Support\Facades\DB::table('order_product')
            ->where('product_id', $product->id)
            ->count();
        $pickupCount = \App\Models\PickupItem::where('product_id', $product->id)->count();

        if ($orderCount > 0 || $pickupCount > 0) {
            $used = [];
            if ($orderCount > 0) {
                $used[] = $orderCount . ' order' . ($orderCount > 1 ? 's' : '');
            }
            if ($pickupCount > 0) {
                $used[] = $pickupCount . ' pickup' . ($pickupCount > 1 ? 's' : '');
            }

            return redirect()->route('products.index')
                ->with('error', 'This product is used on ' . implode(' and ', $used)
                    . ' and cannot be deleted. Deleting it would remove the item from those records.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}