<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $districtId = (string) $request->query('district_id');
        $status = (string) $request->query('status');

        $schools = School::query()
            ->with('district')
            ->withCount('shoppingLists')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($schoolQuery) use ($search) {
                    $schoolQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('school_code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('contact_phone', 'like', "%{$search}%");
                });
            })
            ->when($districtId !== '', fn ($query) => $query->where('district_id', $districtId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'hidden', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.schools.index', [
            'schools' => $schools,
            'districts' => District::active()->orderBy('name')->get(),
            'search' => $search,
            'selectedDistrictId' => $districtId,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.schools.create', [
            'school' => new School(['is_active' => true, 'delivery_fee' => 0, 'distance_from_warehouse_km' => 0]),
            'districts' => District::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $district = District::findOrFail($data['district_id']);

        School::create($data + [
            'slug' => $this->uniqueSlug($data['name'], $district),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.schools.index')->with('status', 'School created.');
    }

    public function show(School $school): View
    {
        return view('admin.schools.show', [
            'school' => $school->load('district')->loadCount('shoppingLists')->load([
                'shoppingLists' => fn ($query) => $query->latest()->limit(10),
            ]),
        ]);
    }

    public function edit(School $school): View
    {
        return view('admin.schools.edit', [
            'school' => $school->load('district'),
            'districts' => District::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $data = $this->validatedData($request, $school);
        $district = District::findOrFail($data['district_id']);

        $data['slug'] = $school->name === $data['name'] && (int) $school->district_id === (int) $data['district_id']
            ? $school->slug
            : $this->uniqueSlug($data['name'], $district, $school);
        $data['is_active'] = $request->boolean('is_active');

        $school->update($data);

        return redirect()->route('admin.schools.index')->with('status', 'School updated.');
    }

    public function destroy(School $school): RedirectResponse
    {
        $school->delete();

        return redirect()->route('admin.schools.index')->with('status', 'School deleted. Existing invoices keep the saved school name.');
    }

    private function validatedData(Request $request, ?School $school = null): array
    {
        return $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
            'name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('schools', 'name')
                    ->where(fn ($query) => $query->where('district_id', $request->integer('district_id')))
                    ->ignore($school),
            ],
            'school_code' => ['nullable', 'string', 'max:80', Rule::unique('schools', 'school_code')->ignore($school)],
            'location' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'distance_from_warehouse_km' => ['required', 'numeric', 'min:0', 'max:99999'],
            'delivery_fee' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, District $district, ?School $school = null): string
    {
        $base = Str::slug($name.' '.$district->name);
        $slug = $base;
        $count = 2;

        while (School::where('slug', $slug)->when($school, fn ($query) => $query->whereKeyNot($school->id))->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
