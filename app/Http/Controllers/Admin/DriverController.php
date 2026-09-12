<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\AccountProvisioner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $availability = (string) $request->query('availability');
        $source = (string) $request->query('source');

        $drivers = Driver::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($driverQuery) use ($search) {
                    $driverQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('vehicle_registration', 'like', "%{$search}%");
                });
            })
            ->when($status === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true))
            ->when($availability === 'available', fn ($query) => $query->where('is_available', true))
            ->when($availability === 'unavailable', fn ($query) => $query->where('is_available', false))
            ->when(in_array($source, ['website', 'admin'], true), fn ($query) => $query->where('source', $source))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.drivers.index', [
            'drivers' => $drivers,
            'driverForm' => new Driver(),
            'search' => $search,
            'status' => $status,
            'availability' => $availability,
            'source' => $source,
        ]);
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

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $data = $this->validatedData($request, $driver);

        DB::transaction(function () use ($driver, $data) {
            $driver->update($data);
            $driver->user?->update(['name' => $data['name'], 'email' => $data['email']]);
        });

        return back()->with('status', 'Delivery partner details updated.');
    }

    public function approve(Driver $driver, AccountProvisioner $provisioner): RedirectResponse
    {
        if (! $driver->email) {
            return back()->withErrors(['driver' => 'Add an email address before approving this delivery partner.']);
        }

        $user = $provisioner->provision($driver->email, $driver->name, 'delivery-person', auth()->id(), $driver->user_id);

        $driver->update([
            'user_id' => $user->id,
            'is_approved' => true,
            'is_available' => true,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $message = $provisioner->sendSetupLink($user)
            ? 'Delivery partner approved and a password setup link was sent.'
            : 'Delivery partner approved. Send the password setup link from Users & Roles.';

        return back()->with('status', $message);
    }

    public function markUnavailable(Driver $driver): RedirectResponse
    {
        $driver->update(['is_available' => false]);

        return back()->with('status', 'Driver marked unavailable.');
    }

    public function markAvailable(Driver $driver): RedirectResponse
    {
        abort_unless($driver->is_approved && $driver->user?->is_active, 422, 'Activate the approved delivery partner account before making them available.');

        $driver->update(['is_available' => true]);

        return back()->with('status', 'Delivery partner marked available.');
    }

    public function download(Driver $driver): StreamedResponse
    {
        abort_unless($driver->verification_document_path && Storage::exists($driver->verification_document_path), 404);

        return Storage::download($driver->verification_document_path, $driver->verification_document_name);
    }

    private function validatedData(Request $request, ?Driver $driver = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($driver?->user_id),
                Rule::unique('drivers', 'email')->ignore($driver),
            ],
            'district' => ['nullable', 'string', 'max:120'],
            'vehicle_type' => ['nullable', 'string', 'max:120'],
            'vehicle_registration' => ['nullable', 'string', 'max:120'],
            'payment_phone' => ['nullable', 'string', 'max:40'],
        ]);
    }
}
