<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentCatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'التشخيص',
            'التحضير',
            'حشو',
        ];

        foreach ($names as $name) {
            DB::table('treatment_categories')->updateOrInsert(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
