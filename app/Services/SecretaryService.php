<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\Doctor_Schedule;
use Illuminate\Support\Facades\DB;

class SecretaryService
{
    public function getDoctorPatients(int $doctorId)
    {
        return Patient::whereHas('treatmentPlans', function ($planQuery) use ($doctorId) {
            $planQuery->where('doctor_id', $doctorId)
                ->whereHas('items.sessions', function ($sessionQuery) {
                    $sessionQuery->where('status', 'completed');
                });
        })
            ->distinct()
            ->get()
            ->map(function (Patient $patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->user?->name,
                    'phone_number' => $patient->user?->phone_number,
                ];
            })
            ->values();
    }

    public function getDoctorTodayAppointments(int $doctorId)
    {
        return Appointment::with('patient.user')
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', Carbon::today())
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('appointment_date')
            ->get()
            ->map(function (Appointment $appointment) {
                return [
                    'patient_name' => $appointment->patient?->user?->name,
                    'time' => optional($appointment->appointment_date)->format('H:i'),
                ];
            })
            ->values();
    }

    public function createPatientBySecretary(string $name, string $phoneNumber)
    {
        return DB::transaction(function () use ($name, $phoneNumber) {

            // 1. تحقق إذا المستخدم موجود
            $user = User::where('phone_number', $phoneNumber)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'phone_number' => $phoneNumber,
                    'password' => Hash::make('11111111'), // default password
                    'is_verified' => true, // مهم: مفعل مباشرة
                ]);

                $user->assignRole('patient');
            }

            // 2. تحقق إذا عنده patient profile
            if (!$user->patient) {
                Patient::create([
                    'user_id' => $user->id,
                ]);
            } else {
                $patient = $user->patient;
            }
            $user->refresh();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'is_verified' => $user->is_verified,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'patient' => [
                'id' => $user->patient?->id,
                ],
            ];
        });
    }




}
