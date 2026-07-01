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
    $categories = [
        [
            'name' => 'التشخيص',
            'price_usd' => 10,
        ],
        [
            'name' => 'التحضير',
            'price_usd' => 20,
        ],
        [
            'name' => 'حشو',
            'price_usd' => 30,
        ],
    ];

    foreach ($categories as $category) {
        DB::table('treatment_categories')->updateOrInsert(
            ['name' => $category['name']],
            $category
        );
    }
}
}
