<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 items-center justify-center rounded-md border border-transparent bg-[#071d4f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
