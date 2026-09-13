<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UgandaSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/uganda_schools.csv');

        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'r');

        if (! $handle) {
            return;
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return;
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, array_pad($row, count($headers), null));

            if (! $record || blank($record['name'] ?? null) || blank($record['district'] ?? null)) {
                continue;
            }

            $district = District::firstOrCreate(
                ['name' => trim((string) $record['district'])],
                [
                    'slug' => Str::slug((string) $record['district']),
                    'is_active' => true,
                ],
            );

            $school = School::firstOrNew([
                'district_id' => $district->id,
                'name' => trim((string) $record['name']),
            ]);

            $school->fill([
                'slug' => $school->slug ?: $this->uniqueSlug(trim((string) $record['name']), $district->name),
                'school_code' => blank($record['school_code'] ?? null) ? null : trim((string) $record['school_code']),
                'location' => blank($record['location'] ?? null) ? null : trim((string) $record['location']),
                'contact_person' => blank($record['contact_person'] ?? null) ? null : trim((string) $record['contact_person']),
                'contact_phone' => blank($record['contact_phone'] ?? null) ? null : trim((string) $record['contact_phone']),
                'contact_email' => blank($record['contact_email'] ?? null) ? null : trim((string) $record['contact_email']),
                'distance_from_warehouse_km' => (float) ($record['distance_from_warehouse_km'] ?? 0),
                'delivery_fee' => (int) ($record['delivery_fee'] ?? 0),
                'is_active' => true,
                'notes' => blank($record['notes'] ?? null) ? null : trim((string) $record['notes']),
            ])->save();
        }

        fclose($handle);
    }

    private function uniqueSlug(string $schoolName, string $districtName): string
    {
        $base = Str::slug($schoolName.' '.$districtName);
        $slug = $base;
        $count = 2;

        while (School::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
