<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SupplierOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    public function index(): View
    {
        $supplier = $this->supplier();
        $offers = $supplier->offers()->with('product.category', 'category')->latest()->paginate(15);

        return view('supplier.offers.index', compact('supplier', 'offers'));
    }

    public function create(): View
    {
        $supplier = $this->supplier();

        return view('supplier.offers.create', [
            'supplier' => $supplier,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'categories' => ProductCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = $this->supplier();
        $data = $request->validate([
            'product_id' => [
                'nullable',
                'exists:products,id',
                Rule::unique('supplier_offers', 'product_id')->where('supplier_id', $supplier->id),
            ],
            'product_category_id' => ['nullable', 'required_without:product_id', 'exists:product_categories,id'],
            'submitted_name' => ['nullable', 'required_without:product_id', 'string', 'max:255'],
            'submitted_description' => ['nullable', 'string', 'max:3000'],
            'submitted_brand' => ['nullable', 'string', 'max:120'],
            'submitted_unit' => ['nullable', 'string', 'max:80'],
            'supplier_price' => ['required', 'numeric', 'min:1'],
            'quantity_submitted' => ['required', 'integer', 'min:1'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'required_without:product_id', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=500,min_height=500'],
        ]);

        if ($data['product_id'] ?? null) {
            $product = Product::findOrFail($data['product_id']);
            $data['submitted_name'] = $product->name;
            $data['product_category_id'] = $product->product_category_id;
            $data['submitted_description'] = $product->description;
            $data['submitted_brand'] = $product->brand;
            $data['submitted_unit'] = $product->unit;
        }

        unset($data['image']);
        $data['submitted_image_path'] = ($data['product_id'] ?? null)
            ? null
            : $request->file('image')?->store('supplier-products', Product::imageDisk());

        $supplier->offers()->create($data + [
            'pending_quantity' => $data['quantity_submitted'],
            'quantity_available' => 0,
            'status' => SupplierOffer::STATUS_PENDING,
            'direct_fulfilment' => true,
        ]);

        return redirect()->route('supplier.offers.index')->with('status', 'Product and stock submitted for EduKit approval.');
    }

    public function restock(Request $request, SupplierOffer $offer): RedirectResponse
    {
        $supplier = $this->supplier();
        abort_unless($offer->supplier_id === $supplier->id && $offer->status === SupplierOffer::STATUS_APPROVED, 403);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $offer->update([
            'quantity_submitted' => $offer->quantity_submitted + $data['quantity'],
            'pending_quantity' => $offer->pending_quantity + $data['quantity'],
            'status' => SupplierOffer::STATUS_PENDING,
            'review_notes' => 'Restock request: '.number_format($data['quantity']).' units.',
        ]);

        return back()->with('status', 'Restock submitted for approval. Existing available stock remains live.');
    }

    private function supplier()
    {
        $supplier = auth()->user()->supplier;
        abort_unless($supplier?->is_approved && $supplier->is_active, 403);
        return $supplier;
    }
}
