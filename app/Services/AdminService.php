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

        // $allowedRoles = ['doctor', 'accountant','secretary','storekeeper'];

        // if (!in_array($data['role'], $allowedRoles)) {
        //     throw new \Exception("نوع المستخدم غير صالح");
        // }
          if (
            $data['role'] === 'doctor' &&
            empty($data['specialization_id'])
        ) {
            return [
                'success' => false,
                'message' => 'يجب تحديد الاختصاص للطبيب'
            ];
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
}
public function getEmployees()
{
    $employees = User::with([
        'roles',
        'doctor.specialization'
    ])
    ->whereHas('roles', function ($q) {
        $q->whereIn('name', [
            'doctor',
            'accountant',
            'secretary',
            'storekeeper'
        ]);
    })
    ->latest()
    ->get();

    return $employees->map(function ($user) {

       return array_filter([
            'id' => $user->id,
            'name' => $user->name,
            'phone_number' => $user->phone_number,
            'email' => $user->email,

            'role' => $user->roles->first()?->name,

            'doctor_info' => $user->doctor ? [
                'specialization' => $user->doctor->specialization?->name,
                'percentage' => $user->doctor->percentage,
            ] : null,
        ]);
    });
}
}