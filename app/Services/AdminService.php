<?php
namespace App\Services;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
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
                'doctor_id' => $user->doctor->id,
                'specialization' => $user->doctor->specialization?->name,
                'percentage' => $user->doctor->percentage,
            ] : null,
        ]);
    });
}

    public function deleteUser(int $userId): array
    {
        $user = User::findOrFail($userId);

        if ($user->hasRole('admin')) {
            throw new \Exception('لا يمكن حذف الأدمن');
        }

        DB::transaction(function () use ($user) {
            // 1️⃣ إذا كان طبيب، احذف ملف الطبيب (Soft Delete)
            if ($user->doctor) {
                $user->doctor->delete();
            }

            // 2️⃣ إذا كان مريض، احذف ملف المريض (Soft Delete)
            if ($user->patient) {
                $user->patient->delete();
            }
            
            // 3️⃣ احذف المستخدم نفسه (Soft Delete)
            $user->delete();
        });

        return [
            'message' => 'User and associated profile deleted successfully'
        ];
    }

    public function restoreUser(int $userId): array
    {
        $user = User::withTrashed()->findOrFail($userId);

        DB::transaction(function () use ($user) {
            // 1️⃣ استعادة المستخدم
            $user->restore();

            // 2️⃣ استعادة سجل الطبيب إن وجد
            $doctor = Doctor::withTrashed()->where('user_id', $user->id)->first();
            if ($doctor) {
                $doctor->restore();
            }

            // 3️⃣ استعادة سجل المريض إن وجد
            $patient = Patient::withTrashed()->where('user_id', $user->id)->first();
            if ($patient) {
                $patient->restore();
            }
        });

        return [
            'message' => 'User and associated profile restored successfully'
        ];
    }

    public function getDeletedUsers()
    {
        return User::onlyTrashed()
            ->with('roles')
            ->latest('deleted_at')
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->roles->first()?->name,
                    'deleted_at' => $user->deleted_at,
                ];
            });
    }


    public function updateDoctorInfo(int $doctorId, array $data)
    {
        $doctor = Doctor::with('specialization')
            ->findOrFail($doctorId);

        $doctor->update(array_filter([
            'percentage' => $data['percentage'] ?? null,
            'specialization_id' => $data['specialization_id'] ?? null,
        ], fn ($value) => !is_null($value)));

        $doctor->load('specialization');

        return [
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->user?->name,
            'percentage' => $doctor->percentage,
            'specialization_id' => $doctor->specialization_id,
            'specialization' => $doctor->specialization?->name,
        ];
    }
    
}