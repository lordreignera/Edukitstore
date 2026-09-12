<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ShoppingListController as AdminShoppingListController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Driver\DeliveryController as DriverDeliveryController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\CartController as WebsiteCartController;
use App\Http\Controllers\Website\PageController as WebsitePageController;
use App\Http\Controllers\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Website\ShoppingListController as WebsiteShoppingListController;
use App\Http\Controllers\Website\SupplierOnboardingController as WebsiteSupplierOnboardingController;
use App\Http\Controllers\Website\DriverOnboardingController as WebsiteDriverOnboardingController;
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
            return redirect()->route('driver.deliveries.index');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::middleware('role:delivery-person')->prefix('driver')->name('driver.')->group(function () {
        Route::get('deliveries', [DriverDeliveryController::class, 'index'])->name('deliveries.index');
        Route::patch('deliveries/{shoppingList}/confirm', [DriverDeliveryController::class, 'confirm'])->name('deliveries.confirm');
    });

    Route::middleware('role:super-admin|admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('invoices/{invoice}', [AdminInvoiceController::class, 'update'])->name('invoices.update');
        Route::resource('products', AdminProductController::class);
        Route::resource('product-categories', AdminProductCategoryController::class);
        Route::resource('suppliers', AdminSupplierController::class)->only(['index', 'create', 'store']);
        Route::patch('suppliers/{supplier}', [AdminSupplierController::class, 'update'])->name('suppliers.update');
        Route::patch('suppliers/{supplier}/approve', [AdminSupplierController::class, 'approve'])->name('suppliers.approve');
        Route::patch('suppliers/{supplier}/suspend', [AdminSupplierController::class, 'suspend'])->name('suppliers.suspend');
        Route::patch('suppliers/{supplier}/reinstate', [AdminSupplierController::class, 'reinstate'])->name('suppliers.reinstate');
        Route::get('suppliers/{supplier}/download', [AdminSupplierController::class, 'download'])->name('suppliers.download');
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
