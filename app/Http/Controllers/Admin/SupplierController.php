<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::latest()->paginate(15);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('admin.suppliers.create', ['supplier' => new Supplier()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validatedData($request));

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier added. Approve them when verification is complete.');
    }

    public function approve(Supplier $supplier): RedirectResponse
    {
        $supplier->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'Supplier approved.');
    }

    public function suspend(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => false]);

        return back()->with('status', 'Supplier suspended.');
    }

    public function download(Supplier $supplier): StreamedResponse
    {
        abort_unless($supplier->verification_document_path && Storage::exists($supplier->verification_document_path), 404);

        return Storage::download($supplier->verification_document_path, $supplier->verification_document_name);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'product_categories' => ['nullable', 'string', 'max:1000'],
            'supply_capacity' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
