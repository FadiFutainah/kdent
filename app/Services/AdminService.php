<?php
namespace App\Services;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;

class AdminService
{
    public function createEmployee($data)
    {
         return DB::transaction(function () use ($data) {

        $allowedRoles = ['doctor', 'accountant','secretary','storekeeper'];

        if (!in_array($data['role'], $allowedRoles)) {
            throw new \Exception("نوع المستخدم غير صالح");
        }

        // 1️⃣ إنشاء User
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'is_verified' => true
        ]);

        // 2️⃣ assign role
        $user->assignRole($data['role']);

        // 3️⃣ doctor profile
        if ($data['role'] === 'doctor') {

            if (empty($data['specialization_id'])) {
                throw new \Exception("يجب تحديد الاختصاص للطبيب");
            }

            Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $data['specialization_id'],
                'percentage' => $data['percentage'] ?? 0,
                'is_active' => true
            ]);
        }

        return [
            'message' => 'تم إنشاء الموظف بنجاح',
            'user_id' => $user->id,
            'role' => $data['role']
        ];
    });

}}