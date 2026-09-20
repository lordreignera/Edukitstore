<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\District;
use App\Services\AccountProvisioner;
use App\Support\DocumentStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DriverOnboardingController extends Controller
{
    public function create(): View
    {
        return view('website.drivers.apply', [
            'districts' => District::active()->orderBy('name')->get(['name']),
        ]);
    }

    public function store(Request $request, AccountProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:drivers,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'district' => ['required', 'string', 'max:120', Rule::exists('districts', 'name')->where('is_active', true)],
            'vehicle_type' => ['required', 'string', 'max:120'],
            'vehicle_registration' => ['required', 'string', 'max:120'],
            'payment_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'verification_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:8192'],
        ]);

        if ($request->hasFile('verification_document')) {
            $file = $request->file('verification_document');
            $data['verification_document_path'] = $file->store('driver-documents', DocumentStorage::disk());
            $data['verification_document_name'] = $file->getClientOriginalName();
        }

        unset($data['verification_document']);
        $password = $data['password'];
        unset($data['password'], $data['password_confirmation']);

        DB::transaction(function () use ($data, $password, $provisioner) {
            $user = $provisioner->createPending($data['email'], $data['name'], $password, 'delivery-person');

            Driver::create($data + [
                'user_id' => $user->id,
                'source' => 'website',
                'submitted_at' => now(),
                'is_approved' => false,
                'is_available' => false,
            ]);
        });

        return redirect()->route('website.drivers')->with(
            'status',
            'Your application and login details have been received. You can sign in after EduKit approves your delivery account.'
        );
    }

}
