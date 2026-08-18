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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }


    /**
     * Validation rules shared by store() and update().
     *
     * Everything on the form is compulsory. The one role-dependent rule is the
     * COA template: only a superadmin may leave it unset (the "ask when
     * generating" state), because only a superadmin can resolve that state
     * later on the COA screen. Admins must commit to a certificate up front.
     */
    private function productRules(): array
    {
        $isSuperadmin = auth()->user()->role === 'superadmin';

        $coaKeys = array_keys(app(\App\Services\CoaTemplateService::class)->productChoices());
        $coaKeys[] = 'none';

        return [
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'coa_template' => $isSuperadmin
                ? ['nullable', \Illuminate\Validation\Rule::in(array_merge($coaKeys, ['']))]
                : ['required', \Illuminate\Validation\Rule::in($coaKeys)],
            // Sent as a hidden 0 plus the checkbox, so an unticked box still
            // arrives as a value.
            'requires_patient_details' => 'nullable|boolean',
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
        return [
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
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // Check if the request wants JSON
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($product);
        }
        
        // Otherwise show the HTML view
        $product->load('orders');
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
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
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}