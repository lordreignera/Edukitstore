<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierOnboardingController extends Controller
{
    public function create(): View
    {
        return view('website.suppliers.apply');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'district' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'product_categories' => ['required', 'string', 'max:1000'],
            'supply_capacity' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'verification_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:8192'],
        ]);

        if ($request->hasFile('verification_document')) {
            $file = $request->file('verification_document');

            $data['verification_document_path'] = $file->store('supplier-documents');
            $data['verification_document_name'] = $file->getClientOriginalName();
        }

        unset($data['verification_document']);

        Supplier::create($data + [
            'source' => 'website',
            'submitted_at' => now(),
            'is_approved' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('website.suppliers')
            ->with('status', 'Your supplier application has been received. EduKit will verify your business before approval.');
    }
}
