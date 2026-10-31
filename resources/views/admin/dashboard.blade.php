<x-admin-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-[#071d4f]">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Welcome back, {{ str(auth()->user()->name)->before(' ') }}. Here is what needs attention today.</p>
            </div>
            <div class="inline-flex w-fit items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">
                <x-ui.icon name="clock" size="size-4" />
                {{ now()->format('D, d M Y') }}
            </div>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @if (session('status'))
                <div class="flex items-center gap-3 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-800">
                    <x-ui.icon name="check" size="size-5" />
                    {{ session('status') }}
                </div>
            @endif

            <x-dashboard-guide
                title="Administrator workflow"
                intro="Work through the queues in order: keep the catalogue and stock accurate, review partner and customer requests, then monitor fulfilment and settlement."
                :steps="[
                    ['title' => 'Maintain the catalogue', 'description' => 'Create categories and products before stock or customer orders are processed.', 'route' => 'admin.products.index', 'action' => 'Open products', 'permission' => 'products.view'],
                    ['title' => 'Keep stock accurate', 'description' => 'Record opening stock, intake and transfers, then watch low-stock and display-stock indicators.', 'route' => 'admin.inventory.index', 'action' => 'Open inventory', 'permission' => 'inventory.view'],
                    ['title' => 'Approve partners', 'description' => 'Review supplier and driver applications before their accounts can be used for fulfilment.', 'route' => 'admin.suppliers.index', 'action' => 'Review suppliers', 'permission' => 'suppliers.view'],
                    ['title' => 'Review supplier offers', 'description' => 'Approve or reject supplier products and stock proposals before they become available for customer orders.', 'route' => 'admin.supplier-offers.index', 'action' => 'Review offers', 'permission' => 'supplier-products.view'],
                    ['title' => 'Process customer requests', 'description' => 'Review shopping lists and quotes, verify payment or exceptions, and resolve paid stock before delivery.', 'route' => 'admin.shopping-lists.index', 'action' => 'Open shopping lists', 'permission' => 'shopping-lists.view'],
                    ['title' => 'Monitor delivery', 'description' => 'Use invoices to check fulfilment and delivery progress; assigned drivers start journeys and record arrival.', 'route' => 'admin.invoices.index', 'action' => 'Open invoices', 'permission' => 'invoices.view'],
                    ['title' => 'Manage access and results', 'description' => 'Use users, roles and permissions to control access, then use the profit report for performance review.', 'route' => 'admin.users.index', 'action' => 'Open users', 'permission' => 'users.view'],
                ]"
                :notes="[
                    'Supplier and driver logins become usable after their application or account is approved and active.',
                    'Do not treat a quote as ready for delivery until payment and stock checks have been completed.',
                ]"
            />

            @include('admin.partials.dashboard.stat-cards')

            @include('admin.partials.dashboard.insights')

            <div class="grid gap-5 xl:grid-cols-3">
                @include('admin.partials.dashboard.catalogue-table')
                @include('admin.partials.dashboard.review-queue')
            </div>

            @include('admin.partials.dashboard.recent-activity')
        </div>
    </div>
</x-admin-layout>
