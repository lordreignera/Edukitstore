@php
    $profileUser = auth()->user();
    $adminAccessPermissionExists = \Spatie\Permission\Models\Permission::query()
        ->where('name', 'admin.access')
        ->where('guard_name', 'web')
        ->exists();
    $isAdminWorkspace = $profileUser->hasAnyRole(['super-admin', 'admin'])
        || ($adminAccessPermissionExists && $profileUser->can('admin.access'));
    $portalName = 'EduKit Workspace';
    $navigation = [];

    if ($profileUser->hasRole('delivery-person')) {
        $portalName = 'Delivery Partner';
        $navigation = [
            ['label' => 'Dashboard', 'route' => 'driver.dashboard', 'pattern' => 'driver.dashboard', 'icon' => 'home'],
            ['label' => 'My Deliveries', 'route' => 'driver.deliveries.index', 'pattern' => 'driver.deliveries.*', 'icon' => 'drivers'],
        ];
    } elseif ($profileUser->hasRole('supplier')) {
        $portalName = 'Supplier Portal';
        $navigation = config('portals.supplier_navigation', []);
    } elseif ($profileUser->hasRole('finance-admin')) {
        $portalName = 'Finance Portal';
        $navigation = [
            ['label' => 'Profit report', 'route' => 'finance.dashboard', 'pattern' => 'finance.dashboard', 'icon' => 'chart'],
        ];
    } elseif ($profileUser->hasAnyRole(['school-admin', 'school-receiver'])) {
        $isSchoolAdmin = $profileUser->hasRole('school-admin');
        $portalName = $isSchoolAdmin ? 'School Administrator' : 'School Receiver';
        $navigation = [
            ['label' => 'Dashboard', 'route' => 'school.dashboard', 'pattern' => 'school.dashboard', 'icon' => 'home'],
            ['label' => 'Order tracking help', 'route' => 'website.track-order', 'pattern' => 'website.track-order', 'icon' => 'drivers'],
        ];

        if ($isSchoolAdmin) {
            $navigation[] = ['label' => 'Browse products', 'route' => 'website.products.index', 'pattern' => 'website.products.*', 'icon' => 'products'];
            $navigation[] = ['label' => 'Upload a list', 'route' => 'website.upload-list', 'pattern' => 'website.upload-list', 'icon' => 'upload'];
        }
    } elseif ($profileUser->hasRole('customer')) {
        $portalName = 'Customer Account';
        $navigation = [
            ['label' => 'My Orders', 'route' => 'customer.dashboard', 'pattern' => 'customer.dashboard', 'icon' => 'invoice'],
            ['label' => 'Shop supplies', 'route' => 'website.products.index', 'pattern' => 'website.products.*', 'icon' => 'products'],
            ['label' => 'Upload a list', 'route' => 'website.upload-list', 'pattern' => 'website.upload-list', 'icon' => 'upload'],
        ];
    }
@endphp

@if ($isAdminWorkspace)
    <x-admin-layout>
        <x-slot name="header">
            <div>
                <p class="text-sm font-bold text-orange-700">Account</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Profile settings</h1>
                <p class="mt-1 text-sm text-slate-500">Update your account details and security settings.</p>
            </div>
        </x-slot>

        @include('profile.partials.settings')
    </x-admin-layout>
@elseif ($navigation)
    <x-portal-layout title="Profile settings" :portal-name="$portalName" :navigation="$navigation">
        <x-slot name="header">
            <div>
                <p class="text-sm font-bold text-orange-700">Account</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Profile settings</h1>
                <p class="mt-1 text-sm text-slate-500">Update your account details and security settings.</p>
            </div>
        </x-slot>

        @include('profile.partials.settings')
    </x-portal-layout>
@else
    <x-app-layout>
        <x-slot name="header">
            <h2 class="text-xl font-semibold leading-tight text-[#071d4f]">Profile settings</h2>
        </x-slot>

        @include('profile.partials.settings')
    </x-app-layout>
@endif
