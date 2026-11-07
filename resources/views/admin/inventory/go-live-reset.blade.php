<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-red-700">Inventory / Production setup</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Go-live reset</h1>
                <p class="mt-1 text-sm text-slate-600">One-time cleanup to prepare the existing product catalogue for verified production stock and prices.</p>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back to inventory</a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-5">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif

            @if ($preview['already_completed'])
                <section class="rounded-md border border-emerald-300 bg-emerald-50 p-5">
                    <h2 class="font-extrabold text-emerald-900">This one-time reset has already been completed</h2>
                    <p class="mt-1 text-sm text-emerald-800">The action is locked to prevent a second reset from removing real production activity.</p>
                </section>
            @elseif (! $resetEnabled)
                <section class="rounded-md border border-amber-300 bg-amber-50 p-5">
                    <h2 class="font-extrabold text-amber-950">Reset is safely disabled</h2>
                    <p class="mt-1 text-sm leading-6 text-amber-900">Testing can continue without risk. For the supervised pre-launch cutover only, set <code class="rounded bg-white px-1.5 py-0.5">EDUKIT_GO_LIVE_RESET_ENABLED=true</code>, clear Laravel’s cached configuration, and turn the flag off again after completion.</p>
                </section>
            @endif

            <section class="rounded-md border border-red-200 bg-white shadow-sm">
                <div class="border-b border-red-100 bg-red-50 px-5 py-4">
                    <h2 class="font-extrabold text-red-950">What this action will do</h2>
                    <p class="mt-1 text-sm leading-6 text-red-900">It deletes every order/list currently stored, its local payment attempts and order-linked notifications, all driver payout records, all inventory batches and movement history, and uploaded school-list documents. It does not contact a payment provider or reverse any external transaction.</p>
                </div>
                <div class="grid gap-5 p-5 md:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">What stays</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Product and category records, names, SKUs, descriptions, colors, brands, units, reorder levels, product images, featured flags, and supplier/user/school/driver accounts.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Product safety after reset</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">EduKit product stock, cost, and selling price become zero. Products are deactivated until staff enter verified prices and opening stock, preventing zero-price checkout.</p>
                    </div>
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-bold text-slate-900">Important scope note</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">The current database does not tag every order and inventory movement as “test.” Therefore this action deletes <strong>all</strong> stored orders/lists, not only records whose payment provider is “demo.” Supplier-offer prices and stock are separate from EduKit warehouse/display inventory and are not changed by this tool.</p>
                    </div>
                </div>
            </section>

            @if (session('reset_summary'))
                <section class="rounded-md border border-slate-200 bg-white p-5">
                    <h2 class="font-extrabold text-slate-900">Reset summary</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (session('reset_summary') as $label => $count)
                            <div class="rounded border border-slate-200 p-3"><dt class="text-xs font-semibold uppercase text-slate-500">{{ str($label)->replace('_', ' ')->headline() }}</dt><dd class="mt-1 text-xl font-extrabold text-slate-900">{{ number_format((int) $count) }}</dd></div>
                        @endforeach
                    </dl>
                </section>
            @endif

            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="reset-preview-title">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                    <div>
                        <h2 id="reset-preview-title" class="font-extrabold text-slate-900">Current data preview</h2>
                        <p class="mt-1 text-sm text-slate-500">Read-only counts. Nothing has been reset.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700">{{ $preview['already_completed'] ? 'Completed' : 'Preview only' }}</span>
                </div>
                <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'Products retained' => $preview['products'],
                        'Categories retained' => $preview['categories'],
                        'Warehouse units to clear' => $preview['warehouse_units'],
                        'Display units to clear' => $preview['display_units'],
                        'Products with prices to reset' => $preview['priced_products'],
                        'Stock batches to remove' => $preview['batches'],
                        'Stock movements to remove' => $preview['movements'],
                        'Orders/lists to delete' => $preview['orders'],
                        'Payment attempts to delete' => $preview['payment_attempts'],
                        'Driver payouts to delete' => $preview['driver_payouts'],
                    ] as $label => $count)
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-600">{{ $label }}</dt><dd class="mt-1 text-2xl font-extrabold text-slate-950">{{ number_format($count) }}</dd></div>
                    @endforeach
                </dl>
                @if ($preview['orders_by_provider'])
                    <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                        <p class="font-bold">Orders grouped by payment provider</p>
                        <p class="mt-1">@foreach ($preview['orders_by_provider'] as $provider => $count){{ $provider }}: {{ number_format($count) }}@if (! $loop->last); @endif @endforeach</p>
                    </div>
                @endif
            </section>

            @if (! $preview['already_completed'])
                <section class="rounded-md border border-red-300 bg-white p-5 shadow-sm">
                    <h2 class="font-extrabold text-red-900">Run the one-time pre-launch reset</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Only continue after all testing is finished, before accepting real customer orders, and after taking a database backup. This form does not run automatically or on page load.</p>

                    @if ($resetEnabled)
                        <form method="POST" action="{{ route('admin.inventory.go-live-reset.store') }}" class="mt-5 space-y-4">
                            @csrf
                            <label class="flex items-start gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-950">
                                <input type="checkbox" name="confirm_all_orders_are_test" value="1" required class="mt-0.5 rounded border-red-400 text-red-700 focus:ring-red-600">
                                <span>I confirm every stored order/list and payment attempt is test data, and no real customer order, payment, or driver payout must be retained.</span>
                            </label>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-semibold text-slate-800">Type <code>RESET PRE-LAUNCH TEST DATA</code>
                                    <input name="confirmation_phrase" required autocomplete="off" class="mt-1 h-11 w-full rounded-md border-slate-300 text-sm focus:border-red-600 focus:ring-red-600" placeholder="Exact confirmation phrase">
                                </label>
                                <label class="block text-sm font-semibold text-slate-800">Your current password
                                    <input name="current_password" type="password" required autocomplete="current-password" class="mt-1 h-11 w-full rounded-md border-slate-300 text-sm focus:border-red-600 focus:ring-red-600">
                                </label>
                            </div>
                            <button type="submit" @disabled(! $resetEnabled || $preview['already_completed']) onclick="return confirm('This permanently deletes all stored orders/lists and inventory history and resets product prices and stock. Continue only if this is the pre-launch test database.');" class="inline-flex min-h-11 items-center justify-center rounded-md bg-red-700 px-5 py-2.5 text-sm font-extrabold text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400">
                                Reset pre-launch data
                            </button>
                        </form>
                    @else
                        <p class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700">Execution is disabled until the one-time cutover is explicitly enabled in the environment. No reset form is available right now.</p>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-admin-layout>
