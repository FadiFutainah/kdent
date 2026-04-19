<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
         DB::table('specializations')->insert([
        [
            'name' => 'تقويم الأسنان',
            'description' => 'تصحيح ترتيب الأسنان والفكين',
        ],
        [
            'name' => 'زراعة الأسنان',
            'description' => 'تعويض الأسنان المفقودة بزرعات',
        ],
        [
            'name' => 'تنظيف الأسنان',
            'description' => 'إزالة الجير وتلميع الأسنان',
        ],
    ]);

    //    // أولاً نجيب الاختصاصات
    // $specializations = DB::table('specializations')->get();

    // foreach ($specializations as $index => $spec) {

    //     // 1️⃣ إنشاء user
    //     $userId = DB::table('users')->insertGetId([
    //         'name' => 'Doctor ' . $spec->name,
    //         'phone_number' => '09999999' . $index,
    //         'password' => Hash::make('123456'),
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);

    //     // 2️⃣ إنشاء doctor
    //     DB::table('doctors')->insert([
    //         'user_id' => $userId,
    //         'specialization_id' => $spec->id,
    //         'is_active' => true,
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);
    // }
    //  DB::table('doctor_schedules')->insert([

    //     // الاثنين صباحي
    //     [
    //         'doctor_id' => 1,
    //         'day' => 'mon',
    //         'start_time' => '09:00',
    //         'end_time' => '12:00',
    //     ],

    //     // الاثنين مسائي
    //     [
    //         'doctor_id' => 1,
    //         'day' => 'mon',
    //         'shift' => 'evening',
    //         'start_time' => '16:00',
    //         'end_time' => '19:00',
    //     ],

    //     // الثلاثاء صباحي
    //     [
    //         'doctor_id' => 1,
    //         'day' => 'tue',
    //         'shift' => 'morning',
    //         'start_time' => '10:00',
    //         'end_time' => '14:00',
    //     ],

    // ]);
    }
}
