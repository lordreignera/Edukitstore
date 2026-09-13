<section class="mx-auto grid max-w-7xl gap-4 px-4 py-5 sm:px-6 md:grid-cols-3 lg:px-8">
    <a href="{{ route('website.products.index') }}" class="group grid min-h-[150px] grid-cols-[1fr_118px] overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:border-emerald-400">
        <div class="p-5">
            <p class="text-[11px] font-black uppercase text-emerald-700">Shop now</p>
            <p class="mt-1 text-[20px] font-black leading-7 text-[#07215f]">Daily school essentials</p>
            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Browse books, stationery, bags and hygiene items.</p>
            <span class="mt-4 inline-flex rounded-md bg-emerald-600 px-4 py-2 text-xs font-black text-white group-hover:bg-emerald-700">Open shop</span>
        </div>
        <div class="h-full w-full bg-cover bg-center" style="background-image: url('/images/products/school_equipment.jpeg')"></div>
    </a>

    <a href="{{ route('website.upload-list') }}" class="group grid min-h-[150px] grid-cols-[1fr_118px] overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:border-sky-400">
        <div class="p-5">
            <p class="text-[11px] font-black uppercase text-blue-700">List service</p>
            <p class="mt-1 text-[20px] font-black leading-7 text-[#07215f]">Upload and let us quote</p>
            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Admin adds delivery fee and sends the invoice.</p>
            <span class="mt-4 inline-flex rounded-md bg-[#1674d1] px-4 py-2 text-xs font-black text-white group-hover:bg-[#07215f]">Upload list</span>
        </div>
        <div class="h-full w-full bg-cover bg-center" style="background-image: url('/images/products/shoopinggcart.jpeg')"></div>
    </a>

    <a href="{{ route('website.track-order') }}" class="group grid min-h-[150px] grid-cols-[1fr_118px] overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:border-amber-400">
        <div class="p-5">
            <p class="text-[11px] font-black uppercase text-amber-700">After review</p>
            <p class="mt-1 text-[20px] font-black leading-7 text-[#07215f]">Pay when invoice is ready</p>
            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Track status using your invoice and contact.</p>
            <span class="mt-4 inline-flex rounded-md bg-[#07215f] px-4 py-2 text-xs font-black text-white group-hover:bg-emerald-700">Track order</span>
        </div>
        <div class="h-full w-full bg-cover bg-center" style="background-image: url('/images/products/all.jpeg')"></div>
    </a>
</section>
