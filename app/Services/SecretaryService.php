<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;

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
}
