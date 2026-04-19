<?php

namespace App\Services;

use App\Models\doctor;
use App\Models\Doctor_Earning;
use App\Models\Doctor_Payment;
use Illuminate\Support\Facades\Auth;

class DoctorFinanceService
{
    public function recordPayment(int $doctorId, array $data)
    {
        $doctor = doctor::findOrFail($doctorId);

        return Doctor_Payment::create([
            'doctor_id' => $doctor->id,
            'amount_usd' => $data['amount_usd'] ?? null,
            'amount_syp' => $data['amount_syp'] ?? null,
            'payment_date' => $data['payment_date'] ?? now(),
        ]);
    }

    public function getDoctorSummary(int $doctorId)
    {
        doctor::findOrFail($doctorId);

        $totalDueUsd = (float) Doctor_Earning::where('doctor_id', $doctorId)->sum('amount_usd');
        $totalDueSyp = (float) Doctor_Earning::where('doctor_id', $doctorId)->sum('amount_syp');

        $totalPaidUsd = (float) Doctor_Payment::where('doctor_id', $doctorId)->sum('amount_usd');
        $totalPaidSyp = (float) Doctor_Payment::where('doctor_id', $doctorId)->sum('amount_syp');

        return [
            'doctor_id' => $doctorId,
            'totals' => [
                'due_usd' => $totalDueUsd,
                'due_syp' => $totalDueSyp,
                'paid_usd' => $totalPaidUsd,
                'paid_syp' => $totalPaidSyp,
                'remaining_usd' => max($totalDueUsd - $totalPaidUsd, 0),
                'remaining_syp' => max($totalDueSyp - $totalPaidSyp, 0),
            ],
        ];
    }

    public function getMySummary()
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        return $this->getDoctorSummary($doctor->id);
    }
}
