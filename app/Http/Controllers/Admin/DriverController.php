<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(): View
    {
        $drivers = Driver::latest()->paginate(15);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create(): View
    {
        return view('admin.drivers.create', ['driver' => new Driver()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Driver::create($this->validatedData($request));

        return redirect()->route('admin.drivers.index')->with('status', 'Driver added. Approve them before assigning deliveries.');
    }

    public function approve(Driver $driver): RedirectResponse
    {
        $driver->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'Driver approved.');
    }

    public function markUnavailable(Driver $driver): RedirectResponse
    {
        $driver->update(['is_available' => false]);

        return back()->with('status', 'Driver marked unavailable.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'vehicle_type' => ['nullable', 'string', 'max:120'],
            'vehicle_registration' => ['nullable', 'string', 'max:120'],
            'payment_phone' => ['nullable', 'string', 'max:40'],
        ]);
    }
}
