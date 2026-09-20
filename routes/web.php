<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProfitReportController as AdminProfitReportController;
use App\Http\Controllers\Admin\SchoolController as AdminSchoolController;
use App\Http\Controllers\Admin\ShoppingListController as AdminShoppingListController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\SupplierOfferController as AdminSupplierOfferController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Driver\DeliveryController as DriverDeliveryController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboardController;
use App\Http\Controllers\Supplier\StockController as SupplierStockController;
use App\Http\Controllers\Supplier\OfferController as SupplierOfferController;
use App\Http\Controllers\Supplier\EarningsController as SupplierEarningsController;
use App\Http\Controllers\Website\CartController as WebsiteCartController;
use App\Http\Controllers\Website\DriverOnboardingController as WebsiteDriverOnboardingController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\PageController as WebsitePageController;
use App\Http\Controllers\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Website\ShoppingListController as WebsiteShoppingListController;
use App\Http\Controllers\Website\SupplierOnboardingController as WebsiteSupplierOnboardingController;
use App\Http\Controllers\Website\TrackOrderController as WebsiteTrackOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('website.home');
Route::get('/products', [WebsiteProductController::class, 'index'])->name('website.products.index');
Route::get('/products/{product:slug}', [WebsiteProductController::class, 'show'])->name('website.products.show');
Route::get('/cart', [WebsiteCartController::class, 'index'])->name('website.cart.index');
Route::post('/cart/submit', [WebsiteCartController::class, 'submit'])->name('website.cart.submit');
Route::post('/cart/{product:slug}', [WebsiteCartController::class, 'store'])->name('website.cart.store');
Route::patch('/cart/{product:slug}', [WebsiteCartController::class, 'update'])->name('website.cart.update');
Route::delete('/cart/{product:slug}', [WebsiteCartController::class, 'destroy'])->name('website.cart.destroy');
Route::get('/quote/{reference}', [WebsiteCartController::class, 'quote'])->name('website.quote.show');
Route::post('/quote/{reference}/pay', [WebsiteCartController::class, 'pay'])->name('website.quote.pay');
Route::get('/payments/flutterwave/callback', [WebsiteCartController::class, 'flutterwaveCallback'])->name('website.payments.flutterwave.callback');
Route::get('/upload-list', [WebsiteShoppingListController::class, 'create'])->name('website.upload-list');
Route::post('/upload-list', [WebsiteShoppingListController::class, 'store'])->name('website.upload-list.store');
Route::get('/track-order', [WebsiteTrackOrderController::class, 'index'])->name('website.track-order');
Route::post('/track-order', [WebsiteTrackOrderController::class, 'lookup'])->name('website.track-order.lookup');
Route::get('/for-suppliers', [WebsiteSupplierOnboardingController::class, 'create'])->name('website.suppliers');
Route::post('/for-suppliers', [WebsiteSupplierOnboardingController::class, 'store'])->name('website.suppliers.store');
Route::get('/for-drivers', [WebsiteDriverOnboardingController::class, 'create'])->name('website.drivers');
Route::post('/for-drivers', [WebsiteDriverOnboardingController::class, 'store'])->name('website.drivers.store');
Route::view('/join', 'website.join')->name('website.join');
Route::get('/register', fn () => redirect()->route('website.join'));
Route::get('/for-schools', [WebsitePageController::class, 'show'])->defaults('page', 'schools')->name('website.schools');
Route::get('/help', [WebsitePageController::class, 'show'])->defaults('page', 'help')->name('website.help');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'password.changed',
])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()?->hasAnyRole(['super-admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if (auth()->user()?->hasRole('delivery-person')) {
            return redirect()->route('driver.dashboard');
        }

        if (auth()->user()?->hasRole('supplier')) {
            return redirect()->route('supplier.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::middleware('role:delivery-person')->prefix('driver')->name('driver.')->group(function () {
        Route::get('/', DriverDashboardController::class)->name('dashboard');
        Route::patch('availability', [DriverDashboardController::class, 'availability'])->name('availability');
        Route::get('deliveries', [DriverDeliveryController::class, 'index'])->name('deliveries.index');
        Route::patch('deliveries/{shoppingList}/confirm', [DriverDeliveryController::class, 'confirm'])->name('deliveries.confirm');
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

    Route::middleware('role:super-admin|admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('invoices/{invoice}', [AdminInvoiceController::class, 'update'])->name('invoices.update');
        Route::get('inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
        Route::get('reports/profit', AdminProfitReportController::class)->name('reports.profit');
        Route::get('inventory/export', [AdminInventoryController::class, 'export'])->name('inventory.export');
        Route::get('inventory/import-template', [AdminInventoryController::class, 'template'])->name('inventory.template');
        Route::post('inventory/import', [AdminInventoryController::class, 'import'])->name('inventory.import');
        Route::post('inventory/{product}/opening', [AdminInventoryController::class, 'opening'])->name('inventory.opening');
        Route::post('inventory/{product}/intake', [AdminInventoryController::class, 'intake'])->name('inventory.intake');
        Route::post('inventory/{product}/transfer', [AdminInventoryController::class, 'transfer'])->name('inventory.transfer');
        Route::patch('inventory/movements/{inventoryMovement}', [AdminInventoryController::class, 'updateMovement'])->name('inventory.movements.update');
        Route::delete('inventory/movements/{inventoryMovement}', [AdminInventoryController::class, 'destroyMovement'])->name('inventory.movements.destroy');
        Route::resource('products', AdminProductController::class);
        Route::resource('product-categories', AdminProductCategoryController::class);
        Route::resource('schools', AdminSchoolController::class);
        Route::resource('suppliers', AdminSupplierController::class)->only(['index', 'create', 'store']);
        Route::patch('suppliers/{supplier}', [AdminSupplierController::class, 'update'])->name('suppliers.update');
        Route::patch('suppliers/{supplier}/approve', [AdminSupplierController::class, 'approve'])->name('suppliers.approve');
        Route::patch('suppliers/{supplier}/suspend', [AdminSupplierController::class, 'suspend'])->name('suppliers.suspend');
        Route::patch('suppliers/{supplier}/reinstate', [AdminSupplierController::class, 'reinstate'])->name('suppliers.reinstate');
        Route::get('suppliers/{supplier}/download', [AdminSupplierController::class, 'download'])->name('suppliers.download');
        Route::get('supplier-products', [AdminSupplierOfferController::class, 'index'])->name('supplier-offers.index');
        Route::patch('supplier-products/{offer}/approve', [AdminSupplierOfferController::class, 'approve'])->name('supplier-offers.approve');
        Route::patch('supplier-products/{offer}/reject', [AdminSupplierOfferController::class, 'reject'])->name('supplier-offers.reject');
        Route::get('shopping-lists', [AdminShoppingListController::class, 'index'])->name('shopping-lists.index');
        Route::get('shopping-lists/{shoppingList}', [AdminShoppingListController::class, 'show'])->name('shopping-lists.show');
        Route::get('shopping-lists/{shoppingList}/download', [AdminShoppingListController::class, 'download'])->name('shopping-lists.download');
        Route::resource('drivers', AdminDriverController::class)->only(['index', 'create', 'store']);
        Route::patch('drivers/{driver}', [AdminDriverController::class, 'update'])->name('drivers.update');
        Route::patch('drivers/{driver}/approve', [AdminDriverController::class, 'approve'])->name('drivers.approve');
        Route::patch('drivers/{driver}/unavailable', [AdminDriverController::class, 'markUnavailable'])->name('drivers.unavailable');
        Route::patch('drivers/{driver}/available', [AdminDriverController::class, 'markAvailable'])->name('drivers.available');
        Route::get('drivers/{driver}/download', [AdminDriverController::class, 'download'])->name('drivers.download');
        Route::resource('users', AdminUserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
        Route::post('users/{user}/password-link', [AdminUserController::class, 'sendPasswordLink'])->name('users.password-link');
    });
});
