<?php
namespace App\Repositories;
use App\Models\User;
class UserRepository {
    public function findByIdentifier($identifier) {
    // يبحث عن المستخدم سواء كان المدخل إيميل أو رقم هاتف
    return User::where('email', $identifier)
               ->orWhere('phone_number', $identifier)
               ->first();
    }

    public function createPatient(array $data) {
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'password' => bcrypt($data['password']),
            'is_verified' => false // المريض يحتاج تفعيل
        ]);
        // $user->assignRole('patient'); 
        // return $user;
    }

    public function updateOTP($user, $code) {
        $user->update([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function markAsVerified($user) {
        $user->update(['is_verified' => true, 'otp_code' => null]);
    }
}