<x-admin-layout>
    <x-slot name="title">Supplier Products</x-slot>
    <x-slot name="header">
        <div><h1 class="text-2xl font-extrabold text-[#071d4f]">Supplier products and stock</h1><p class="mt-1 text-sm text-slate-500">Approve supplier-owned stock for direct website fulfilment.</p></div>
    </x-slot>
    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px] space-y-5">
        @if(session('status'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif
        <div class="flex flex-wrap gap-2">
            @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.supplier-offers.index', $key ? ['status' => $key] : []) }}" class="rounded-md border px-3 py-2 text-xs font-bold {{ $status === $key ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="grid gap-4">
            @forelse($offers as $offer)
                <article class="rounded-md border border-slate-200 bg-white p-5 shadow-sm"><div class="grid gap-5 xl:grid-cols-[1fr_440px]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-extrabold text-[#071d4f]">{{ $offer->submitted_name }}</h2><span class="rounded px-2 py-1 text-xs font-bold {{ $offer->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($offer->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ ucfirst($offer->status) }}</span><span class="rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $offer->product_id ? 'Master catalogue item' : 'New product proposal' }}</span></div>
                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ $offer->supplier->business_name }}</p>
                        <div class="mt-4 flex flex-wrap gap-4">
                            @if($offer->product?->image_url)<figure><img src="{{ $offer->product->image_url }}" alt="Master product" class="h-32 w-32 rounded-md border border-slate-200 object-contain"><figcaption class="mt-1 text-xs font-semibold text-slate-500">Master image</figcaption></figure>@endif
                            @if($offer->submitted_image_path)<figure><img src="{{ $offer->image_url }}" alt="Supplier proposal" class="h-32 w-32 rounded-md border border-amber-300 object-contain"><figcaption class="mt-1 text-xs font-semibold text-amber-700">Image awaiting review</figcaption></figure>@endif
                        </div>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                            <div><dt class="text-xs font-bold text-slate-500">Supplier price</dt><dd class="font-bold">UGX {{ number_format($offer->supplier_price) }}</dd></div>
                            <div><dt class="text-xs font-bold text-slate-500">Total submitted</dt><dd class="font-bold">{{ number_format($offer->quantity_submitted) }}</dd></div>
                            <div><dt class="text-xs font-bold text-slate-500">Awaiting approval</dt><dd class="font-bold text-amber-700">{{ number_format($offer->pending_quantity) }}</dd></div>
                            <div><dt class="text-xs font-bold text-slate-500">Available</dt><dd class="font-bold text-emerald-700">{{ number_format($offer->quantity_available) }}</dd></div>
                        </dl>
                        @if($offer->submitted_description)<p class="mt-4 text-sm text-slate-600">{{ $offer->submitted_description }}</p>@endif
                    </div>
                    <div>
                        @if($offer->status === 'pending')
                            <form method="POST" action="{{ route('admin.supplier-offers.approve', $offer) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                                <div class="sm:col-span-2"><label class="text-xs font-bold">Master catalogue mapping</label><select name="product_id" class="mt-1 w-full rounded-md border-slate-300 text-sm"><option value="">Approve as a new master product</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected($offer->product_id === $product->id)>{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">New proposals retain the reviewed supplier image. Existing products always retain the master image.</p></div>
                                <div><label class="text-xs font-bold">Approved quantity</label><input name="approved_quantity" type="number" min="1" max="{{ $offer->pending_quantity }}" value="{{ $offer->pending_quantity }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm"></div>
                                <div><label class="text-xs font-bold">Customer price (UGX)</label><input name="customer_price" type="number" min="{{ (int) $offer->supplier_price + 1 }}" value="{{ $offer->customer_price }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm"></div>
                                <div class="sm:col-span-2"><label class="text-xs font-bold">Review note</label><input name="review_notes" class="mt-1 w-full rounded-md border-slate-300 text-sm"></div>
                                <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-bold text-white sm:col-span-2">Approve for website</button>
                            </form>
                            <form method="POST" action="{{ route('admin.supplier-offers.reject', $offer) }}" class="mt-3 flex gap-2">@csrf @method('PATCH')<input name="review_notes" required placeholder="Reason for rejection" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm"><button class="rounded-md border border-red-300 px-4 text-sm font-bold text-red-700">Reject</button></form>
                        @else
                            <div class="rounded-md bg-slate-50 p-4 text-sm"><p><strong>Customer price:</strong> {{ $offer->customer_price ? 'UGX '.number_format($offer->customer_price) : '-' }}</p><p class="mt-2"><strong>Master product:</strong> {{ $offer->product?->name ?? '-' }}</p>@if($offer->review_notes)<p class="mt-2 text-slate-600">{{ $offer->review_notes }}</p>@endif</div>
                        @endif
                    </div>
                </div></article>
            @empty
                <p class="rounded-md border border-slate-200 bg-white p-10 text-center text-slate-500">No supplier submissions found.</p>
            @endforelse
        </div>
        {{ $offers->links() }}
    </div></div>
</x-admin-layout>
