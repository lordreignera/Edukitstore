<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Services\InventoryService;
use App\Services\MarketplaceSourceService;
use App\Services\SchoolDeliveryService;
use App\Services\SupplierSaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index(SchoolDeliveryService $delivery, MarketplaceSourceService $sources): View
    {
        $cart = session('cart', []);
        $productIds = array_keys($cart);

        $products = Product::query()
            ->with('category', 'approvedSupplierOffers.supplier')
            ->whereIn('id', $productIds)
            ->get()
            ->map(function (Product $product) use ($cart, $sources) {
                $offerId = session('cart_sources.'.$product->id);
                $source = $sources->source($product, $offerId ? (int) $offerId : null);
                $product->cart_quantity = $cart[$product->id] ?? 0;
                $product->cart_source = $source;
                $product->cart_unit_price = $source['price'];
                $product->cart_line_total = $source['price'] * $product->cart_quantity;

                return $product;
            });

        $subtotal = $products->sum('cart_line_total');
        $schools = $delivery->activeSchools();
        $supplierFeeProfiles = $products->filter(fn ($product) => $product->cart_source['type'] === 'supplier')
            ->map(fn ($product) => $product->cart_source['offer']->supplier)
            ->unique('id')->values()->map(fn ($supplier) => [
                'district' => $supplier->district,
                'local_fee' => $supplier->local_delivery_fee,
                'other_fee' => $supplier->other_district_delivery_fee,
            ]);
        $hasEdukitItems = $products->contains(fn ($product) => $product->cart_source['type'] === 'edukit');

        return view('website.cart.index', compact('products', 'subtotal', 'schools', 'supplierFeeProfiles', 'hasEdukitItems'));
    }

    public function store(Request $request, Product $product, MarketplaceSourceService $sources): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $source = $sources->source($product, $request->integer('supplier_offer_id') ?: null);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.$source['quantity']],
            'supplier_offer_id' => ['nullable', 'integer'],
        ]);
        $quantity = (int) ($data['quantity'] ?? 1);

        $cart = session('cart', []);
        $cart[$product->id] = min($source['quantity'], ($cart[$product->id] ?? 0) + $quantity);

        session(['cart' => $cart]);
        session(['cart_sources.'.$product->id => $source['offer']?->id]);

        return back()->with('status', "{$product->name} added to cart.");
    }

    public function update(Request $request, Product $product, MarketplaceSourceService $sources): RedirectResponse
    {
        $source = $sources->source($product, session('cart_sources.'.$product->id));
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$source['quantity']],
        ]);

        $cart = session('cart', []);
        $cart[$product->id] = (int) $data['quantity'];

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} quantity updated.");
    }

    public function submit(Request $request, SchoolDeliveryService $delivery): RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return back()->withErrors(['cart' => 'Add at least one product before submitting your cart.']);
        }

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ] + $delivery->validationRules());

        $data = $delivery->applyTo($data);

        $products = Product::query()
            ->active()
            ->with('approvedSupplierOffers.supplier')
            ->whereIn('id', array_keys($cart))
            ->get();

        $sourceService = app(MarketplaceSourceService::class);
        $items = $products->map(function (Product $product) use ($cart, $sourceService): array {
            $quantity = (int) ($cart[$product->id] ?? 0);
            $source = $sourceService->source($product, session('cart_sources.'.$product->id));
            $unitPrice = (int) $source['price'];

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_cost' => (int) ($source['offer']?->supplier_price ?? $product->cost_price),
                'unit_price' => $unitPrice,
                'fulfilment_source' => $source['type'],
                'supplier_offer_id' => $source['offer']?->id,
                'supplier_id' => $source['offer']?->supplier_id,
                'source_label' => $source['label'],
                'quantity' => $quantity,
                'line_total' => $unitPrice * $quantity,
            ];
        })->filter(fn (array $item): bool => $item['quantity'] > 0)->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => 'The products in your cart are no longer available.']);
        }

        foreach ($items as $item) {
            $product = $products->firstWhere('id', $item['product_id']);

            $available = $item['fulfilment_source'] === 'supplier'
                ? (int) $product?->approvedSupplierOffers->firstWhere('id', $item['supplier_offer_id'])?->quantity_available
                : (int) $product?->stock_quantity;

            if (! $product || $available < $item['quantity']) {
                return back()->withErrors(['cart' => "{$item['name']} has only ".number_format($available).' available from the selected source.']);
            }
        }

        if ($items->contains(fn ($item) => $item['fulfilment_source'] === 'supplier') && $data['delivery_preference'] !== 'school') {
            return back()->withErrors(['delivery_preference' => 'Supplier-direct products must be delivered to a selected school.']);
        }

        $hasEdukitItems = $items->contains(fn ($item) => $item['fulfilment_source'] === 'edukit');
        $supplierIds = $items->where('fulfilment_source', 'supplier')->pluck('supplier_id')->unique();
        $destinationDistrict = \App\Models\District::find($data['district_id']);
        $supplierDeliveryFee = \App\Models\Supplier::whereIn('id', $supplierIds)->get()->sum(
            fn ($supplier) => strcasecmp((string) $supplier->district, (string) $destinationDistrict?->name) === 0
                ? $supplier->local_delivery_fee
                : $supplier->other_district_delivery_fee
        );
        $data['delivery_fee'] = ($hasEdukitItems ? (int) $data['delivery_fee'] : 0) + $supplierDeliveryFee;

        $itemsSubtotal = $items->sum('line_total');

        $shoppingList = ShoppingList::create($data + [
            'source' => ShoppingList::SOURCE_CART,
            'reference' => ShoppingList::nextReference(),
            'cart_items' => $items->all(),
            'items_subtotal' => $itemsSubtotal,
            'estimated_total' => $itemsSubtotal + $data['delivery_fee'],
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        $shoppingList->lineItems()->createMany($items->map(fn (array $item): array => [
            'product_id' => $item['product_id'],
            'supplier_offer_id' => $item['supplier_offer_id'],
            'supplier_id' => $item['supplier_id'],
            'fulfilment_source' => $item['fulfilment_source'],
            'product_name' => $item['name'],
            'sku' => $item['sku'],
            'quantity' => $item['quantity'],
            'unit_cost' => $item['unit_cost'],
            'unit_price' => $item['unit_price'],
            'line_total' => $item['line_total'],
            'cost_total' => 0,
            'profit_total' => 0,
        ])->all());

        session()->forget('cart');
        session()->forget('cart_sources');

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Your order total is ready. Review the school delivery fee and continue to payment.');
    }

    public function quote(string $reference): View
    {
        $shoppingList = ShoppingList::with('assignedDriver', 'school.district', 'lineItems')->where('reference', $reference)->firstOrFail();

        return view('website.quote', compact('shoppingList'));
    }

    public function pay(Request $request, string $reference, InventoryService $inventory, SupplierSaleService $supplierSales): RedirectResponse
    {
        $shoppingList = ShoppingList::where('reference', $reference)->firstOrFail();

        abort_unless($shoppingList->status === ShoppingList::STATUS_QUOTED && $shoppingList->estimated_total, 404);

        $inventory->ensureInvoiceHasDisplayStock($shoppingList);
        $supplierSales->ensureStock($shoppingList);

        $secretKey = config('services.flutterwave.secret_key');

        if (! $secretKey) {
            return back()->withErrors(['payment' => 'Flutterwave checkout is not connected yet. Add the Flutterwave secret key, then this invoice can be paid online.']);
        }

        $txRef = $shoppingList->reference.'-'.Str::upper(Str::random(6));
        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $shoppingList->estimated_total,
                'currency' => 'UGX',
                'redirect_url' => route('website.payments.flutterwave.callback'),
                'customer' => [
                    'email' => $this->customerEmail($shoppingList),
                    'name' => $shoppingList->parent_name,
                    'phonenumber' => $shoppingList->phone,
                ],
                'customizations' => [
                    'title' => 'EduKit Invoice '.$shoppingList->reference,
                    'description' => 'School supplies invoice including delivery/convenience fee.',
                ],
                'meta' => [
                    'shopping_list_id' => $shoppingList->id,
                    'reference' => $shoppingList->reference,
                ],
            ]);

        $checkoutUrl = $response->json('data.link');

        if (! $response->successful() || ! $checkoutUrl) {
            return back()->withErrors(['payment' => 'Flutterwave could not start checkout. Please try again or contact EduKit support.']);
        }

        $shoppingList->update([
            'payment_status' => ShoppingList::PAYMENT_PENDING,
            'payment_provider' => 'flutterwave',
            'payment_reference' => $txRef,
        ]);

        return redirect()->away($checkoutUrl);
    }

    public function flutterwaveCallback(Request $request, InventoryService $inventory, SupplierSaleService $supplierSales): RedirectResponse
    {
        $txRef = (string) $request->query('tx_ref');
        $transactionId = (string) $request->query('transaction_id');

        abort_if($txRef === '', 404);

        $shoppingList = ShoppingList::where('payment_reference', $txRef)->firstOrFail();

        if ($request->query('status') !== 'successful' || $transactionId === '') {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'The payment was not completed. You can try again from this invoice.']);
        }

        $secretKey = config('services.flutterwave.secret_key');

        if (! $secretKey) {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'Flutterwave verification is not connected yet.']);
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        $data = $response->json('data', []);
        $amount = (float) ($data['amount'] ?? 0);

        $verified = $response->successful()
            && $response->json('status') === 'success'
            && ($data['status'] ?? null) === 'successful'
            && ($data['tx_ref'] ?? null) === $txRef
            && strtoupper((string) ($data['currency'] ?? '')) === 'UGX'
            && $amount >= (float) $shoppingList->estimated_total;

        if (! $verified) {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'Flutterwave payment verification failed. Please contact EduKit support if money was deducted.']);
        }

        if ($shoppingList->payment_status !== ShoppingList::PAYMENT_PAID) {
            DB::transaction(function () use ($shoppingList, $inventory, $supplierSales): void {
                $lockedInvoice = ShoppingList::whereKey($shoppingList->id)->lockForUpdate()->firstOrFail();
                if ($lockedInvoice->payment_status === ShoppingList::PAYMENT_PAID) {
                    return;
                }

                $inventory->recordPaidCartSale($lockedInvoice);
                $supplierSales->recordPaidSale($lockedInvoice);
                $lockedInvoice->update([
                    'payment_status' => ShoppingList::PAYMENT_PAID,
                    'payment_provider' => 'flutterwave',
                    'paid_at' => now(),
                ]);
            });
        }

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Payment received. EduKit can now begin fulfilment.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session()->forget('cart_sources.'.$product->id);

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} removed from cart.");
    }

    private function customerEmail(ShoppingList $shoppingList): string
    {
        if ($shoppingList->email && filter_var($shoppingList->email, FILTER_VALIDATE_EMAIL)) {
            return $shoppingList->email;
        }

        return 'invoice-'.$shoppingList->id.'@edukit.local';
    }
}
