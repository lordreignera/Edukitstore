<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class SchoolDeliveryService
{
    public function activeSchools(): Collection
    {
        return $this->activeSchoolQuery()
            ->orderBy('name')
            ->get();
    }

    public function validationRules(): array
    {
        return [
            'school_id' => ['nullable', 'required_if:delivery_preference,school', Rule::exists('schools', 'id')->where('is_active', true)],
            'learner_name' => ['nullable', 'required_if:delivery_preference,school', 'string', 'max:160'],
            'class_level' => ['nullable', 'required_if:delivery_preference,school', 'string', 'max:80'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_preference' => ['required', 'in:school,pickup'],
        ];
    }

    public function applyTo(array $data): array
    {
        if (($data['delivery_preference'] ?? 'school') === 'school') {
            $school = $this->activeSchoolQuery()->findOrFail($data['school_id']);

            $data['district_id'] = $school->district_id;
            $data['school_id'] = $school->id;
            $data['school_name'] = $school->name;
            $data['delivery_location'] = $school->location ?: $school->district?->name;
            $data['delivery_fee'] = $school->delivery_fee;

            return $data;
        }

        $data['district_id'] = null;
        $data['school_id'] = null;
        $data['school_name'] = 'Warehouse pickup';
        $data['delivery_location'] = $data['delivery_location'] ?: 'EduKit warehouse pickup';
        $data['delivery_fee'] = 0;

        return $data;
    }

    private function activeSchoolQuery(): Builder
    {
        return School::query()
            ->with('district')
            ->active()
            ->whereHas('district', fn ($query) => $query->active());
    }
}
