<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndSuperAdminSeeder::class,
            UgandaDistrictSeeder::class,
            UgandaSchoolSeeder::class,
            EduKitProductSeeder::class,
            OpeningStockSeeder::class,
        ]);
    }
}
