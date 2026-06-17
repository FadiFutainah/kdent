<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الأدوار (Roles)
      $adminRole = Role::firstOrCreate(['name' => 'admin','guard_name' => 'api']);
      Role::create(['name' => 'doctor', 'guard_name' => 'api']);
      Role::create(['name' => 'patient', 'guard_name' => 'api']);
      Role::create(['name' => 'accountant', 'guard_name' => 'api']);
      Role::create(['name' => 'secretary', 'guard_name' => 'api']);
      Role::create(['name' => 'storekeeper', 'guard_name' => 'api']);

        // 2. إنشاء حساب الأدمن
        $admin = User::updateOrCreate(
            ['email' => 'admin@karamdent.com'], // البحث عن الحساب بهذا الإيميل لمنع التكرار
            [
                'name' => 'Admin Manager',
                'password' => bcrypt('12345678'), // كلمة مرور قوية
                'is_verified' => true, // الأدمن مفعل تلقائياً
              //  'phone_number' => '0900000000', // رقم افتراضي
            ]
        );

        // 3. إسناد دور الأدمن له
        $admin->assignRole($adminRole);

        $this->command->info('Admin account created successfully!');
    
    }

}
