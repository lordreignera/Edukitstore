<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ShoppingListController as AdminShoppingListController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\CartController as WebsiteCartController;
use App\Http\Controllers\Website\PageController as WebsitePageController;
use App\Http\Controllers\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Website\ShoppingListController as WebsiteShoppingListController;
use App\Http\Controllers\Website\SupplierOnboardingController as WebsiteSupplierOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('website.home');
Route::get('/products', [WebsiteProductController::class, 'index'])->name('website.products.index');
Route::get('/products/{product:slug}', [WebsiteProductController::class, 'show'])->name('website.products.show');
Route::get('/cart', [WebsiteCartController::class, 'index'])->name('website.cart.index');
Route::post('/cart/{product:slug}', [WebsiteCartController::class, 'store'])->name('website.cart.store');
Route::delete('/cart/{product:slug}', [WebsiteCartController::class, 'destroy'])->name('website.cart.destroy');
Route::get('/upload-list', [WebsiteShoppingListController::class, 'create'])->name('website.upload-list');
Route::post('/upload-list', [WebsiteShoppingListController::class, 'store'])->name('website.upload-list.store');
Route::get('/track-order', [WebsitePageController::class, 'show'])->defaults('page', 'track-order')->name('website.track-order');
Route::get('/for-suppliers', [WebsiteSupplierOnboardingController::class, 'create'])->name('website.suppliers');
Route::post('/for-suppliers', [WebsiteSupplierOnboardingController::class, 'store'])->name('website.suppliers.store');
Route::get('/for-schools', [WebsitePageController::class, 'show'])->defaults('page', 'schools')->name('website.schools');
Route::get('/help', [WebsitePageController::class, 'show'])->defaults('page', 'help')->name('website.help');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::middleware('role:super-admin|admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::post('product-categories', [AdminProductCategoryController::class, 'store'])->name('product-categories.store');
        Route::resource('suppliers', AdminSupplierController::class)->only(['index', 'create', 'store']);
        Route::patch('suppliers/{supplier}/approve', [AdminSupplierController::class, 'approve'])->name('suppliers.approve');
        Route::patch('suppliers/{supplier}/suspend', [AdminSupplierController::class, 'suspend'])->name('suppliers.suspend');
        Route::get('suppliers/{supplier}/download', [AdminSupplierController::class, 'download'])->name('suppliers.download');
        Route::get('shopping-lists', [AdminShoppingListController::class, 'index'])->name('shopping-lists.index');
        Route::get('shopping-lists/{shoppingList}', [AdminShoppingListController::class, 'show'])->name('shopping-lists.show');
        Route::patch('shopping-lists/{shoppingList}', [AdminShoppingListController::class, 'update'])->name('shopping-lists.update');
        Route::get('shopping-lists/{shoppingList}/download', [AdminShoppingListController::class, 'download'])->name('shopping-lists.download');
        Route::resource('drivers', AdminDriverController::class)->only(['index', 'create', 'store']);
        Route::patch('drivers/{driver}/approve', [AdminDriverController::class, 'approve'])->name('drivers.approve');
        Route::patch('drivers/{driver}/unavailable', [AdminDriverController::class, 'markUnavailable'])->name('drivers.unavailable');
    });
});
