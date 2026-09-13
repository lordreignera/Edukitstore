<section class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">Shop by category</h2>
                <p class="mt-1 text-sm font-medium text-slate-600">Start with the main school supply groups.</p>
            </div>
            <a href="{{ route('website.products.index') }}" class="hidden text-sm font-black text-emerald-700 hover:text-emerald-900 sm:block">View all categories</a>
        </div>

        <div class="no-scrollbar mt-6 grid auto-cols-[132px] grid-flow-col gap-3 overflow-x-auto pb-2 sm:auto-cols-fr sm:grid-flow-row sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-9">
            @foreach ($categories as $category)
                @php($image = $categoryArtwork[$category->name] ?? $categoryImages->get($category->id))
                <a href="{{ route('website.products.index', ['category' => $category->slug]) }}" class="group rounded-md border border-[#dbe8f3] bg-white p-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-500 hover:shadow-md">
                    <div class="mx-auto grid aspect-square w-20 place-items-center overflow-hidden rounded-md bg-[#f1f8ff]">
                        <x-ui.responsive-image
                            :src="$image"
                            :alt="$category->name"
                            :label="$category->name"
                            image-class="h-full w-full object-contain p-2"
                            fallback-class="grid h-full w-full place-items-center px-2 text-center text-[10px] font-black leading-4 text-emerald-700"
                        />
                    </div>
                    <p class="mt-3 min-h-9 text-[11px] font-black leading-4 text-[#07215f] group-hover:text-emerald-700">{{ $category->name }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
