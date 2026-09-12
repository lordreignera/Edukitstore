<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\AccountProvisioner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class SupplierOnboardingController extends Controller
{
    public function create(): View
    {
        return view('website.suppliers.apply');
    }

    public function store(Request $request, AccountProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:suppliers,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
        $password = $data['password'];
        unset($data['password'], $data['password_confirmation']);

        DB::transaction(function () use ($data, $password, $provisioner) {
            $user = $provisioner->createPending($data['email'], $data['contact_person'], $password, 'supplier');

            Supplier::create($data + [
                'user_id' => $user->id,
                'source' => 'website',
                'submitted_at' => now(),
                'is_approved' => false,
                'is_active' => false,
            ]);
        });

        return redirect()
            ->route('website.suppliers')
            ->with('status', 'Your application and login details have been received. You can sign in after EduKit approves your business.');
    }
}
