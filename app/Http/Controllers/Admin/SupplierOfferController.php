<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Services\ProductCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierOfferController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status');
        $offers = SupplierOffer::with('supplier', 'product', 'category')
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.supplier-offers.index', [
            'offers' => $offers,
            'status' => $status,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'image_path']),
        ]);
    }

    public function approve(Request $request, SupplierOffer $offer, ProductCodeGenerator $codes): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'customer_price' => ['required', 'numeric', function (string $attribute, mixed $value, \Closure $fail) use ($offer): void {
                if ((float) $value <= (float) $offer->supplier_price) {
                    $fail('The customer price must be greater than the supplier price.');
                }
            }],
            'approved_quantity' => ['required', 'integer', 'min:1', 'max:'.$offer->pending_quantity],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($offer, $data, $codes): void {
            $offer = SupplierOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if ($offer->status !== SupplierOffer::STATUS_PENDING || $offer->pending_quantity < $data['approved_quantity']) {
                throw ValidationException::withMessages(['approved_quantity' => 'This stock request has already changed. Refresh and try again.']);
            }

            $product = isset($data['product_id']) ? Product::find($data['product_id']) : $offer->product;
            if ($product && SupplierOffer::where('supplier_id', $offer->supplier_id)
                ->where('product_id', $product->id)
                ->whereKeyNot($offer->id)
                ->exists()) {
                throw ValidationException::withMessages(['product_id' => 'This supplier already has an offer for the selected master product. Use its restock action instead.']);
            }
            if (! $product) {
                $product = Product::create([
                    'product_category_id' => $offer->product_category_id,
                    'name' => $offer->submitted_name,
                    'slug' => $codes->uniqueSlug($offer->submitted_name),
                    'sku' => $codes->next(),
                    'description' => $offer->submitted_description,
                    'brand' => $offer->submitted_brand,
                    'unit' => $offer->submitted_unit,
                    'cost_price' => $offer->supplier_price,
                    'price' => $data['customer_price'],
                    'image_path' => $offer->submitted_image_path,
                    'is_active' => true,
                    'is_featured' => false,
                ]);
            }

            $newQuantity = $offer->quantity_available + (int) $data['approved_quantity'];

            $offer->update([
                'product_id' => $product->id,
                'customer_price' => $data['customer_price'],
                'quantity_available' => $newQuantity,
                'pending_quantity' => $offer->pending_quantity - (int) $data['approved_quantity'],
                'status' => $offer->pending_quantity === (int) $data['approved_quantity']
                    ? SupplierOffer::STATUS_APPROVED
                    : SupplierOffer::STATUS_PENDING,
                'review_notes' => $data['review_notes'] ?? null,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            if ((float) $product->price <= 0 || (float) $data['customer_price'] < (float) $product->price) {
                $product->update(['price' => $data['customer_price'], 'is_active' => true]);
            }
        });

        return back()->with('status', 'Supplier stock approved and available on the website.');
    }

    public function reject(Request $request, SupplierOffer $offer): RedirectResponse
    {
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']]);
        $offer->update(['status' => SupplierOffer::STATUS_REJECTED, 'review_notes' => $data['review_notes']]);
        return back()->with('status', 'Supplier submission rejected.');
    }
}
