<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\AccountProvisioner;
use App\Support\DocumentStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $source = (string) $request->query('source');

        $suppliers = Supplier::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($supplierQuery) use ($search) {
                    $supplierQuery->where('business_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true)->where('is_active', true))
            ->when($status === 'suspended', fn ($query) => $query->where('is_approved', true)->where('is_active', false))
            ->when(in_array($source, ['website', 'admin'], true), fn ($query) => $query->where('source', $source))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'supplierForm' => new Supplier(['is_active' => true]),
            'search' => $search,
            'status' => $status,
            'source' => $source,
        ]);
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

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validatedData($request, $supplier);

        DB::transaction(function () use ($supplier, $data) {
            $supplier->update($data);
            $supplier->user?->update([
                'name' => $data['contact_person'] ?: $data['business_name'],
                'email' => $data['email'],
            ]);
        });

        return back()->with('status', 'Supplier details updated.');
    }

    public function approve(Supplier $supplier, AccountProvisioner $provisioner): RedirectResponse
    {
        if (! $supplier->email) {
            return back()->withErrors(['supplier' => 'Add an email address before approving this supplier.']);
        }

        $supplierChosePassword = (bool) $supplier->user_id;
        $user = $provisioner->provision(
            $supplier->email,
            $supplier->contact_person ?: $supplier->business_name,
            'supplier',
            auth()->id(),
            $supplier->user_id
        );

        $supplier->update([
            'user_id' => $user->id,
            'is_approved' => true,
            'is_active' => true,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        if ($supplierChosePassword) {
            $message = 'Supplier approved. Their existing login is now active.';
        } else {
            $message = $provisioner->sendSetupLink($user)
                ? 'Supplier approved and a password setup link was sent.'
                : 'Supplier approved. Send the password setup link from Users & Roles.';
        }

        return back()->with('status', $message);
    }

    public function suspend(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => false]);
        $supplier->user?->update(['is_active' => false]);

        if ($supplier->user_id) {
            DB::table('sessions')->where('user_id', $supplier->user_id)->delete();
        }

        return back()->with('status', 'Supplier suspended.');
    }

    public function reinstate(Supplier $supplier): RedirectResponse
    {
        abort_unless($supplier->is_approved && $supplier->user_id, 422, 'Only approved suppliers with an account can be reinstated.');

        $supplier->update(['is_active' => true]);
        $supplier->user->update(['is_active' => true]);

        return back()->with('status', 'Supplier and account reinstated.');
    }

    public function download(Supplier $supplier): StreamedResponse
    {
        $disk = DocumentStorage::disk();

        abort_unless($supplier->verification_document_path && Storage::disk($disk)->exists($supplier->verification_document_path), 404);

        return Storage::disk($disk)->download($supplier->verification_document_path, $supplier->verification_document_name);
    }

    private function validatedData(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($supplier?->user_id),
                Rule::unique('suppliers', 'email')->ignore($supplier),
            ],
            'district' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'product_categories' => ['nullable', 'string', 'max:1000'],
            'supply_capacity' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

}
