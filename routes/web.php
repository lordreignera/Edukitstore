<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DeliveryFeeReportController as AdminDeliveryFeeReportController;
use App\Http\Controllers\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\GoLiveResetController as AdminGoLiveResetController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProfitReportController as AdminProfitReportController;
use App\Http\Controllers\Admin\PartnerPerformanceReportController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SchoolController as AdminSchoolController;
use App\Http\Controllers\Admin\ShoppingListController as AdminShoppingListController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\SupplierOfferController as AdminSupplierOfferController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\DeliveryController as DriverDeliveryController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\RegistrationController as CustomerRegistrationController;
use App\Http\Controllers\School\DashboardController as SchoolDashboardController;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboardController;
use App\Http\Controllers\Supplier\EarningsController as SupplierEarningsController;
use App\Http\Controllers\Supplier\OfferController as SupplierOfferController;
use App\Http\Controllers\Supplier\StockController as SupplierStockController;
use App\Http\Controllers\Website\CartController as WebsiteCartController;
use App\Http\Controllers\Website\DriverOnboardingController as WebsiteDriverOnboardingController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\PageController as WebsitePageController;
use App\Http\Controllers\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Website\ShoppingListController as WebsiteShoppingListController;
use App\Http\Controllers\Website\SupplierOnboardingController as WebsiteSupplierOnboardingController;
use App\Http\Controllers\Website\TrackOrderController as WebsiteTrackOrderController;
use App\Http\Controllers\WorkspaceGuideController;
use App\Support\WorkspaceRoute;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('website.home');
Route::post('/session/heartbeat', fn () => response()->noContent())
    ->middleware('throttle:30,1')
    ->name('session.heartbeat');
Route::get('/products', [WebsiteProductController::class, 'index'])->name('website.products.index');
Route::get('/products/suggest', [WebsiteProductController::class, 'suggest'])->middleware('throttle:120,1')->name('website.products.suggest');
Route::get('/products/{product:slug}', [WebsiteProductController::class, 'show'])->name('website.products.show');
Route::get('/cart', [WebsiteCartController::class, 'index'])->name('website.cart.index');
Route::post('/cart/submit', [WebsiteCartController::class, 'submit'])->middleware('throttle:10,1')->name('website.cart.submit');
Route::post('/cart/{product:slug}', [WebsiteCartController::class, 'store'])->name('website.cart.store');
Route::patch('/cart/{product:slug}', [WebsiteCartController::class, 'update'])->name('website.cart.update');
Route::delete('/cart/{product:slug}', [WebsiteCartController::class, 'destroy'])->name('website.cart.destroy');
Route::get('/quote/{reference}', [WebsiteCartController::class, 'quote'])->name('website.quote.show');
Route::post('/quote/{reference}/pay', [WebsiteCartController::class, 'pay'])->middleware('throttle:5,1')->name('website.quote.pay');
Route::post('/quote/{reference}/received', [WebsiteTrackOrderController::class, 'acknowledgeReceipt'])->middleware('throttle:10,1')->name('website.quote.received');
Route::get('/payments/flutterwave/callback', [WebsiteCartController::class, 'flutterwaveCallback'])->middleware('throttle:60,1')->name('website.payments.flutterwave.callback');
Route::get('/upload-list', [WebsiteShoppingListController::class, 'create'])->name('website.upload-list');
Route::post('/upload-list', [WebsiteShoppingListController::class, 'store'])->middleware('throttle:5,1')->name('website.upload-list.store');
Route::get('/track-order', [WebsiteTrackOrderController::class, 'index'])->name('website.track-order');
Route::get('/customer/register', [CustomerRegistrationController::class, 'create'])->middleware('guest')->name('customer.register');
Route::post('/customer/register', [CustomerRegistrationController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('customer.register.store');
Route::get('/for-suppliers', [WebsiteSupplierOnboardingController::class, 'create'])->name('website.suppliers');
Route::post('/for-suppliers', [WebsiteSupplierOnboardingController::class, 'store'])->middleware('throttle:5,1')->name('website.suppliers.store');
Route::get('/for-drivers', [WebsiteDriverOnboardingController::class, 'create'])->name('website.drivers');
Route::post('/for-drivers', [WebsiteDriverOnboardingController::class, 'store'])->middleware('throttle:5,1')->name('website.drivers.store');
Route::view('/join', 'website.join')->name('website.join');
Route::get('/register', fn () => redirect()->route('website.join'));
Route::get('/for-schools', [WebsitePageController::class, 'show'])->defaults('page', 'schools')->name('website.schools');
Route::get('/help', [WebsitePageController::class, 'show'])->defaults('page', 'help')->name('website.help');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
])->group(function () {
    Route::get('/change-password', function () {
        if (! auth()->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    })->name('password.change');

    Route::get('/dashboard', function () {
        $workspaceRoute = WorkspaceRoute::for(auth()->user());

        if ($workspaceRoute !== 'dashboard') {
            return redirect()->route($workspaceRoute);
        }

        return view('dashboard');
    })->name('dashboard');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->middleware('throttle:10,1')->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:10,1')->name('push-subscriptions.destroy');

    Route::get('/customer', CustomerDashboardController::class)
        ->middleware(['role:customer', 'verified'])
        ->name('customer.dashboard');

    Route::get('/user-guide', [WorkspaceGuideController::class, 'index'])->name('user-guide');
    Route::get('/user-guide/download', [WorkspaceGuideController::class, 'download'])->name('user-guide.download');

    Route::middleware('role:delivery-person')->prefix('driver')->name('driver.')->group(function () {
        Route::get('/', DriverDashboardController::class)->name('dashboard');
        Route::patch('availability', [DriverDashboardController::class, 'availability'])->name('availability');
        Route::get('deliveries', [DriverDeliveryController::class, 'index'])->name('deliveries.index');
        Route::patch('deliveries/{shoppingList}/start', [DriverDeliveryController::class, 'startJourney'])->name('deliveries.start');
        Route::patch('deliveries/{shoppingList}/reached', [DriverDeliveryController::class, 'reached'])->name('deliveries.reached');
    });

    Route::middleware('role:supplier')->prefix('supplier')->name('supplier.')->group(function () {
        Route::get('/', SupplierDashboardController::class)->name('dashboard');
        Route::get('stock', [SupplierStockController::class, 'index'])->name('stock.index');
        Route::get('products', [SupplierOfferController::class, 'index'])->name('offers.index');
        Route::get('products/create', [SupplierOfferController::class, 'create'])->name('offers.create');
        Route::post('products', [SupplierOfferController::class, 'store'])->name('offers.store');
        Route::post('products/{offer}/restock', [SupplierOfferController::class, 'restock'])->name('offers.restock');
        Route::get('earnings', [SupplierEarningsController::class, 'index'])->name('earnings.index');
    });

    Route::middleware('role:finance-admin')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', AdminProfitReportController::class)->name('dashboard');
        Route::get('profit/export', [AdminProfitReportController::class, 'export'])->middleware('permission:reports.profit.view')->name('profit.export');
        Route::get('delivery-fees', AdminDeliveryFeeReportController::class)->middleware('permission:reports.profit.view')->name('delivery-fees');
        Route::get('delivery-fees/export', [AdminDeliveryFeeReportController::class, 'export'])->middleware('permission:reports.profit.view')->name('delivery-fees.export');
    });

    Route::middleware('role:school-admin|school-receiver')->prefix('school')->name('school.')->group(function () {
        Route::get('/', SchoolDashboardController::class)->name('dashboard');
    });

    Route::middleware('role_or_permission:super-admin|admin|admin.access')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->middleware('permission:admin.dashboard.view')->name('dashboard');
        Route::get('search', [App\Http\Controllers\Admin\SearchController::class, '__invoke'])->name('search');

        Route::get('notifications', [AdminNotificationController::class, 'index'])->middleware('permission:invoices.view')->name('notifications.index');
        Route::post('notifications/{notification}/open', [AdminNotificationController::class, 'open'])->middleware('permission:invoices.view')->name('notifications.open');
        Route::get('invoices', [AdminInvoiceController::class, 'index'])->middleware('permission:invoices.view')->name('invoices.index');
        Route::get('invoices/export', [AdminInvoiceController::class, 'export'])->middleware('permission:invoices.view')->name('invoices.export');
        Route::get('invoices/{invoice}/print', [AdminInvoiceController::class, 'print'])->middleware('permission:invoices.view')->name('invoices.print');
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->middleware('permission:invoices.view')->name('invoices.show');
        Route::patch('invoices/{invoice}', [AdminInvoiceController::class, 'update'])->middleware('permission:invoices.update')->name('invoices.update');
        Route::patch('invoices/{invoice}/fulfilment', [AdminInvoiceController::class, 'packAndAssign'])->middleware('permission:invoices.update')->name('invoices.fulfilment');
        Route::patch('invoices/{invoice}/manual-refund', [AdminInvoiceController::class, 'recordManualRefund'])->middleware('permission:invoices.update')->name('invoices.manual-refund');
        Route::patch('invoices/{invoice}/resolve-paid-stock', [AdminInvoiceController::class, 'resolvePaidStock'])->middleware('permission:invoices.resolve-stock')->name('invoices.resolve-paid-stock');

        Route::get('inventory', [AdminInventoryController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.index');
        Route::get('inventory/go-live-reset', [AdminGoLiveResetController::class, 'index'])->middleware('role:super-admin')->name('inventory.go-live-reset');
        Route::post('inventory/go-live-reset', [AdminGoLiveResetController::class, 'store'])->middleware('role:super-admin')->name('inventory.go-live-reset.store');
        Route::get('inventory/warehouse', [AdminInventoryController::class, 'warehouse'])->middleware('permission:inventory.view')->name('inventory.warehouse');
        Route::get('inventory/display', [AdminInventoryController::class, 'display'])->middleware('permission:inventory.view')->name('inventory.display');
        Route::get('inventory/sales', [AdminInventoryController::class, 'sales'])->middleware('permission:inventory.view')->name('inventory.sales');
        Route::get('reports/profit', AdminProfitReportController::class)->middleware('permission:reports.profit.view')->name('reports.profit');
        Route::get('reports/profit/export', [AdminProfitReportController::class, 'export'])->middleware('permission:reports.profit.view')->name('reports.profit.export');
        Route::get('reports/driver-performance', [PartnerPerformanceReportController::class, 'drivers'])->middleware('permission:reports.performance.view')->name('reports.driver-performance');
        Route::get('reports/driver-performance/export', [PartnerPerformanceReportController::class, 'exportDrivers'])->middleware('permission:reports.performance.view')->name('reports.driver-performance.export');
        Route::get('reports/supplier-performance', [PartnerPerformanceReportController::class, 'suppliers'])->middleware('permission:reports.performance.view')->name('reports.supplier-performance');
        Route::get('reports/supplier-performance/export', [PartnerPerformanceReportController::class, 'exportSuppliers'])->middleware('permission:reports.performance.view')->name('reports.supplier-performance.export');
        Route::get('reports/driver-payouts', [PartnerPerformanceReportController::class, 'payouts'])->middleware('permission:reports.driver-payouts.view')->name('reports.driver-payouts');
        Route::get('reports/driver-payouts/export', [PartnerPerformanceReportController::class, 'exportPayouts'])->middleware('permission:reports.driver-payouts.view')->name('reports.driver-payouts.export');
        Route::post('reports/driver-payouts', [PartnerPerformanceReportController::class, 'recordPayout'])->middleware('permission:reports.driver-payouts.record')->name('reports.driver-payouts.store');
        Route::get('reports/delivery-fees', AdminDeliveryFeeReportController::class)->middleware('permission:reports.profit.view')->name('reports.delivery-fees');
        Route::get('reports/delivery-fees/export', [AdminDeliveryFeeReportController::class, 'export'])->middleware('permission:reports.profit.view')->name('reports.delivery-fees.export');
        Route::get('inventory/export', [AdminInventoryController::class, 'export'])->middleware('permission:inventory.export')->name('inventory.export');
        Route::get('inventory/import-template', [AdminInventoryController::class, 'template'])->middleware('permission:inventory.export')->name('inventory.template');
        Route::post('inventory/import', [AdminInventoryController::class, 'import'])->middleware('permission:inventory.import')->name('inventory.import');
        Route::post('inventory/{product}/opening', [AdminInventoryController::class, 'opening'])->middleware('permission:inventory.manage')->name('inventory.opening');
        Route::post('inventory/{product}/intake', [AdminInventoryController::class, 'intake'])->middleware('permission:inventory.manage')->name('inventory.intake');
        Route::post('inventory/{product}/transfer', [AdminInventoryController::class, 'transfer'])->middleware('permission:inventory.manage')->name('inventory.transfer');
        Route::patch('inventory/movements/{inventoryMovement}', [AdminInventoryController::class, 'updateMovement'])->middleware('permission:inventory.manage')->name('inventory.movements.update');
        Route::delete('inventory/movements/{inventoryMovement}', [AdminInventoryController::class, 'destroyMovement'])->middleware('permission:inventory.manage')->name('inventory.movements.destroy');

        Route::get('products', [AdminProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
        Route::get('products/create', [AdminProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
        Route::post('products', [AdminProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
        Route::get('products/{product}/edit', [AdminProductController::class, 'edit'])->middleware('permission:products.update')->name('products.edit');
        Route::put('products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.update')->name('products.update');
        Route::patch('products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.update')->name('products.update.patch');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');

        Route::get('product-categories', [AdminProductCategoryController::class, 'index'])->middleware('permission:categories.view')->name('product-categories.index');
        Route::get('product-categories/create', [AdminProductCategoryController::class, 'create'])->middleware('permission:categories.create')->name('product-categories.create');
        Route::post('product-categories', [AdminProductCategoryController::class, 'store'])->middleware('permission:categories.create')->name('product-categories.store');
        Route::get('product-categories/{product_category}', [AdminProductCategoryController::class, 'show'])->middleware('permission:categories.view')->name('product-categories.show');
        Route::get('product-categories/{product_category}/edit', [AdminProductCategoryController::class, 'edit'])->middleware('permission:categories.update')->name('product-categories.edit');
        Route::put('product-categories/{product_category}', [AdminProductCategoryController::class, 'update'])->middleware('permission:categories.update')->name('product-categories.update');
        Route::patch('product-categories/{product_category}', [AdminProductCategoryController::class, 'update'])->middleware('permission:categories.update')->name('product-categories.update.patch');
        Route::delete('product-categories/{product_category}', [AdminProductCategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('product-categories.destroy');

        Route::get('schools', [AdminSchoolController::class, 'index'])->middleware('permission:schools.view')->name('schools.index');
        Route::get('schools/create', [AdminSchoolController::class, 'create'])->middleware('permission:schools.create')->name('schools.create');
        Route::post('schools', [AdminSchoolController::class, 'store'])->middleware('permission:schools.create')->name('schools.store');
        Route::get('schools/{school}', [AdminSchoolController::class, 'show'])->middleware('permission:schools.view')->name('schools.show');
        Route::get('schools/{school}/edit', [AdminSchoolController::class, 'edit'])->middleware('permission:schools.update')->name('schools.edit');
        Route::put('schools/{school}', [AdminSchoolController::class, 'update'])->middleware('permission:schools.update')->name('schools.update');
        Route::patch('schools/{school}', [AdminSchoolController::class, 'update'])->middleware('permission:schools.update')->name('schools.update.patch');
        Route::delete('schools/{school}', [AdminSchoolController::class, 'destroy'])->middleware('permission:schools.delete')->name('schools.destroy');

        Route::get('suppliers', [AdminSupplierController::class, 'index'])->middleware('permission:suppliers.view')->name('suppliers.index');
        Route::get('suppliers/create', [AdminSupplierController::class, 'create'])->middleware('permission:suppliers.create')->name('suppliers.create');
        Route::post('suppliers', [AdminSupplierController::class, 'store'])->middleware('permission:suppliers.create')->name('suppliers.store');
        Route::patch('suppliers/{supplier}', [AdminSupplierController::class, 'update'])->middleware('permission:suppliers.update')->name('suppliers.update');
        Route::patch('suppliers/{supplier}/approve', [AdminSupplierController::class, 'approve'])->middleware('permission:suppliers.approve')->name('suppliers.approve');
        Route::patch('suppliers/{supplier}/suspend', [AdminSupplierController::class, 'suspend'])->middleware('permission:suppliers.suspend')->name('suppliers.suspend');
        Route::patch('suppliers/{supplier}/reinstate', [AdminSupplierController::class, 'reinstate'])->middleware('permission:suppliers.suspend')->name('suppliers.reinstate');
        Route::get('suppliers/{supplier}/download', [AdminSupplierController::class, 'download'])->middleware('permission:suppliers.download')->name('suppliers.download');

        Route::get('supplier-products', [AdminSupplierOfferController::class, 'index'])->middleware('permission:supplier-products.view')->name('supplier-offers.index');
        Route::patch('supplier-products/{offer}/approve', [AdminSupplierOfferController::class, 'approve'])->middleware('permission:supplier-products.approve')->name('supplier-offers.approve');
        Route::patch('supplier-products/{offer}/reject', [AdminSupplierOfferController::class, 'reject'])->middleware('permission:supplier-products.reject')->name('supplier-offers.reject');

        Route::get('shopping-lists', [AdminShoppingListController::class, 'index'])->middleware('permission:shopping-lists.view')->name('shopping-lists.index');
        Route::get('shopping-lists/{shoppingList}', [AdminShoppingListController::class, 'show'])->middleware('permission:shopping-lists.view')->name('shopping-lists.show');
        Route::get('shopping-lists/{shoppingList}/download', [AdminShoppingListController::class, 'download'])->middleware('permission:shopping-lists.download')->name('shopping-lists.download');

        Route::get('drivers', [AdminDriverController::class, 'index'])->middleware('permission:drivers.view')->name('drivers.index');
        Route::get('drivers/create', [AdminDriverController::class, 'create'])->middleware('permission:drivers.create')->name('drivers.create');
        Route::post('drivers', [AdminDriverController::class, 'store'])->middleware('permission:drivers.create')->name('drivers.store');
        Route::patch('drivers/{driver}', [AdminDriverController::class, 'update'])->middleware('permission:drivers.update')->name('drivers.update');
        Route::patch('drivers/{driver}/approve', [AdminDriverController::class, 'approve'])->middleware('permission:drivers.approve')->name('drivers.approve');
        Route::patch('drivers/{driver}/unavailable', [AdminDriverController::class, 'markUnavailable'])->middleware('permission:drivers.availability')->name('drivers.unavailable');
        Route::patch('drivers/{driver}/available', [AdminDriverController::class, 'markAvailable'])->middleware('permission:drivers.availability')->name('drivers.available');
        Route::get('drivers/{driver}/download', [AdminDriverController::class, 'download'])->middleware('permission:drivers.download')->name('drivers.download');

        Route::get('users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('users/create', [AdminUserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::patch('users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.update')->name('users.update.patch');
        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])->middleware('permission:users.deactivate')->name('users.status');
        Route::post('users/{user}/password-link', [AdminUserController::class, 'sendPasswordLink'])->middleware('permission:users.reset-password')->name('users.password-link');

        Route::get('roles', [AdminRoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
        Route::get('roles/create', [AdminRoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
        Route::post('roles', [AdminRoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
        Route::get('roles/{role}/edit', [AdminRoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
        Route::put('roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
        Route::patch('roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update.patch');
        Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

        Route::get('permissions', [AdminPermissionController::class, 'index'])->middleware('permission:permissions.view')->name('permissions.index');
        Route::get('permissions/create', [AdminPermissionController::class, 'create'])->middleware('permission:permissions.create')->name('permissions.create');
        Route::post('permissions', [AdminPermissionController::class, 'store'])->middleware('permission:permissions.create')->name('permissions.store');
        Route::get('permissions/{permission}/edit', [AdminPermissionController::class, 'edit'])->middleware('permission:permissions.update')->name('permissions.edit');
        Route::put('permissions/{permission}', [AdminPermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
        Route::patch('permissions/{permission}', [AdminPermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update.patch');
        Route::delete('permissions/{permission}', [AdminPermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('permissions.destroy');
    });
});
