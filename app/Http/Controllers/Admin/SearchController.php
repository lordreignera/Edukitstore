<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Product;
use App\Models\School;
use App\Models\ShoppingList;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $term = trim($data['q']);
        $like = '%'.$term.'%';
        $user = $request->user();
        $results = [];

        if ($user->can('invoices.view')) {
            $results['Orders'] = ShoppingList::query()
                ->where(fn ($query) => $query->where('reference', 'like', $like)
                    ->orWhere('parent_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('school_name', 'like', $like)
                    ->orWhere('learner_name', 'like', $like))
                ->latest()->take(8)->get()
                ->map(fn (ShoppingList $order) => [
                    'title' => $order->reference,
                    'detail' => $order->parent_name.' · '.($order->school_name ?: $order->phone),
                    'url' => route('admin.invoices.show', $order),
                ]);
        }

        if ($user->can('products.view')) {
            $results['Products'] = Product::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('color', 'like', $like))
                ->latest()->take(8)->get()
                ->map(fn (Product $product) => [
                    'title' => $product->name,
                    'detail' => trim(collect([$product->sku, $product->brand, $product->color])->filter()->join(' · ')),
                    'url' => route('admin.products.show', $product),
                ]);
        }

        if ($user->can('schools.view')) {
            $results['Schools'] = School::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)
                    ->orWhere('school_code', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('contact_person', 'like', $like)
                    ->orWhere('contact_phone', 'like', $like))
                ->orderBy('name')->take(8)->get()
                ->map(fn (School $school) => [
                    'title' => $school->name,
                    'detail' => trim(collect([$school->school_code, $school->location])->filter()->join(' · ')),
                    'url' => route('admin.schools.show', $school),
                ]);
        }

        if ($user->can('suppliers.view')) {
            $results['Suppliers'] = Supplier::query()
                ->where(fn ($query) => $query->where('business_name', 'like', $like)
                    ->orWhere('contact_person', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like))
                ->latest()->take(8)->get()
                ->map(fn (Supplier $supplier) => [
                    'title' => $supplier->business_name,
                    'detail' => trim(collect([$supplier->contact_person, $supplier->email, $supplier->phone])->filter()->join(' · ')),
                    'url' => route('admin.suppliers.index', ['q' => $term]),
                ]);
        }

        if ($user->can('drivers.view')) {
            $results['Drivers'] = Driver::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('vehicle_registration', 'like', $like))
                ->latest()->take(8)->get()
                ->map(fn (Driver $driver) => [
                    'title' => $driver->name,
                    'detail' => trim(collect([$driver->phone, $driver->vehicle_registration])->filter()->join(' · ')),
                    'url' => route('admin.drivers.index', ['q' => $term]),
                ]);
        }

        return view('admin.search', [
            'term' => $term,
            'resultGroups' => collect($results)->map(fn ($items) => $items->values()),
        ]);
    }
}
