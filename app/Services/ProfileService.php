<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class ProfileService
{
    public function getProfile()
    {
        $user = Auth::user();
        $role = $user->getRoleNames()->first();

        // Doctor
        if ($user->hasRole('doctor')) {

            $user->load('doctor.specialization');

            return [
                'name'          => $user->name,
                'email'         => $user->email,
                'phone_number'  => $user->phone_number,
                'date_of_birth' => $user->date_of_birth,

                'specialization' => $user->doctor?->specialization?->name,
                'percentage'     => $user->doctor?->percentage,
                'role' => $role,
            ];
        }

        // Patient
        if ($user->hasRole('patient')) {

            $user->load('patient');

            return [
                'name'          => $user->name,
                'phone_number'  => $user->phone_number,
                'date_of_birth' => $user->date_of_birth,
                'email'         => $user->email,
                'gender'        => $user->patient?->gender,
                'address'       => $user->patient?->address,
                'occupation'    => $user->patient?->occupation,
                'role' => $role,
            ];
        }

        // Accountant, Secretary, Inventory, Admin
        return [
            'name'          => $user->name,
            'email'         => $user->email,
            'phone_number'  => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'role' => $role,
        ];
    }

    public function updateProfile(array $data)
    {
        $user = Auth::user();

        $user->update(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
        ], function ($value) {
            return !is_null($value) && $value !== '';
        }));

        if ($user->hasRole('patient')) {

            $patientData = array_filter([
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'occupation' => $data['occupation'] ?? null,
            ], function ($value) {
                return !is_null($value) && $value !== '';
            });

            if (!empty($patientData)) {
                $user->patient()->update($patientData);
            }
        }

        // رجّع نفس تنسيق getProfile
        return $this->getProfile();
    }

}