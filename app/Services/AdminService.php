<?php
namespace App\Services;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;
use Exception;

class AdminService
{
    public function createEmployee($data)
    {
        // 1️⃣ تحقق من الرول
        $allowedRoles = ['doctor', 'accountant','secretary','storekeeper'];

        if (!in_array($data['role'], $allowedRoles)) {
            throw new Exception("نوع المستخدم غير صالح");
        }

        // 2️⃣ إنشاء User
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'is_verified' => true // الموظفين ما بدهم OTP
        ]);

        // 3️⃣ إعطاء Role
        $user->assignRole($data['role']);

        // 4️⃣ إذا Doctor → ننشئ سجل doctor
        if ($data['role'] === 'doctor') {

            if (empty($data['specialization_id'])) {
                throw new Exception("يجب تحديد الاختصاص للطبيب");
            }

            Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $data['specialization_id'],
                'is_active' => true
            ]);
        }

        return [
            'message' => 'تم إنشاء الموظف بنجاح',
            'user_id' => $user->id,
            'role' => $data['role']
        ];
    }

}