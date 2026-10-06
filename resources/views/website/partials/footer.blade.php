<footer class="border-t border-[#dce8f2] bg-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-[13px] font-semibold leading-6 text-[#173267] sm:grid-cols-2 sm:px-6 lg:grid-cols-[1.45fr_0.7fr_0.9fr_0.9fr_1fr_1fr] lg:px-8">
        <div>
            <img src="/images/website/edukit-store-logo.png" alt="EduKit Store" class="h-14 w-auto sm:h-16">
            <p class="mt-3 max-w-xs text-slate-600">EduKit connects parents, schools, suppliers and delivery partners to make education more accessible for every child in Uganda.</p>
        </div>

        <div>
            <p class="font-black text-[#07215f]">Quick Links</p>
            <div class="mt-3 grid gap-1.5">
                <a href="{{ route('website.home') }}" class="hover:text-emerald-700">Home</a>
                <a href="{{ route('website.products.index') }}" class="hover:text-emerald-700">Shop</a>
                <a href="{{ route('website.upload-list') }}" class="hover:text-emerald-700">Upload List</a>
                <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">Schools</a>
                <a href="{{ route('website.track-order') }}" class="hover:text-emerald-700">Track Order</a>
                <a href="{{ route('website.help') }}" class="hover:text-emerald-700">Help</a>
            </div>
        </div>

        <div>
            <p class="font-black text-[#07215f]">For Suppliers</p>
            <div class="mt-3 grid gap-1.5">
                <a href="{{ route('website.suppliers') }}" class="hover:text-emerald-700">Become a Supplier</a>
                <a href="{{ route('login') }}" class="hover:text-emerald-700">Supplier Login</a>
                <a href="{{ route('website.suppliers') }}" class="hover:text-emerald-700">Supplier application details</a>
                <a href="{{ route('website.drivers') }}" class="hover:text-emerald-700">Become a Delivery Partner</a>
            </div>
        </div>

        <div>
            <p class="font-black text-[#07215f]">For Schools</p>
            <div class="mt-3 grid gap-1.5">
                <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">School partnerships (coming soon)</a>
                <a href="{{ route('login') }}" class="hover:text-emerald-700">Staff sign in</a>
            </div>
        </div>

        <div>
            <p class="font-black text-[#07215f]">Contact Us</p>
            <div class="mt-3 grid gap-2 text-slate-600">
                <p class="flex items-center gap-2"><x-ui.icon name="phone" size="size-4" /> <span>+256 700 123456</span></p>
                <p class="flex items-center gap-2"><x-ui.icon name="mail" size="size-4" /> <span>support@edukit.ug</span></p>
                <p class="flex items-center gap-2"><x-ui.icon name="map-pin" size="size-4" /> <span>Kampala, Uganda</span></p>
            </div>
        </div>

        <div>
            <p class="font-black text-[#07215f]">Mobile Apps</p>
            <div class="mt-3 grid max-w-40 gap-2">
                <div class="rounded bg-black px-3 py-2 text-white shadow-sm">
                    <span class="flex items-center gap-2 text-[9px] uppercase leading-none text-white/70"><x-ui.icon name="play-store" size="size-4" /> Android</span>
                    <span class="block text-sm font-black leading-tight">Coming soon</span>
                </div>
                <div class="rounded bg-black px-3 py-2 text-white shadow-sm">
                    <span class="flex items-center gap-2 text-[9px] uppercase leading-none text-white/70"><x-ui.icon name="app-store" size="size-4" /> iOS</span>
                    <span class="block text-sm font-black leading-tight">Coming soon</span>
                </div>
            </div>
            <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">Mobile apps are being prepared for parents, suppliers and delivery teams.</p>
        </div>
    </div>

    <div class="border-t border-slate-100">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-[12px] font-semibold text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} EduKit. All rights reserved.</p>
            <p>Better education. Brighter futures.</p>
        </div>
    </div>
</footer>
