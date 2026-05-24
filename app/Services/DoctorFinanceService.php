<?php

namespace App\Services;

use App\Models\doctor;
use App\Models\Doctor_Earning;
use App\Models\Doctor_Payment;
use Illuminate\Support\Facades\Auth;

class DoctorFinanceService
{
    public function __construct(private ExchangeRateService $exchangeRateService)
    {
    }

    public function recordPayment(int $doctorId, array $data)
    {
        $doctor = doctor::findOrFail($doctorId);

        $amountUsdProvided = array_key_exists('amount_usd', $data) && !is_null($data['amount_usd']);
        $amountSypProvided = array_key_exists('amount_syp', $data) && !is_null($data['amount_syp']);

        if (!$amountUsdProvided && !$amountSypProvided) {
            
            throw new \InvalidArgumentException('يجب إرسال مبلغ بالدولار أو بالليرة على الأقل');
        }

        $amountUsd = $amountUsdProvided ? (float) $data['amount_usd'] : null;
        $amountSyp = $amountSypProvided ? (float) $data['amount_syp'] : null;
        $rateRecord = $this->exchangeRateService->getCurrentUsdToSypRate();
        $rate = (float) $rateRecord->rate;

        if (($amountUsdProvided && !$amountSypProvided) || (!$amountUsdProvided && $amountSypProvided)) {
            if ($amountUsdProvided && !$amountSypProvided) {
                $amountSyp = round($amountUsd * $rate, 2);
            }

            if (!$amountUsdProvided && $amountSypProvided && $rate > 0) {
                $amountUsd = round($amountSyp / $rate, 2);
            }
        }

        $payment = Doctor_Payment::create([
            'doctor_id' => $doctor->id,
            'exchange_rate_id' => $rateRecord->id,
            'amount_usd' => $amountUsd,
            'amount_syp' => $amountSyp ?? round($amountUsd * $rate, 2),
            'payment_date' => $data['payment_date'] ?? now(),
        ]);

        return [
            'id' => $payment->id,
            'doctor_id' => $payment->doctor_id,
            'doctor_name' => $doctor->user?->name,
            'exchange_rate_id' => $payment->exchange_rate_id,
            'exchange_rate' => $rateRecord->rate,
            'amount_usd' => $payment->amount_usd,
            'amount_syp' => $payment->amount_syp,
            'payment_date' => $payment->payment_date,
            'updated_at' => $payment->updated_at,
            'created_at' => $payment->created_at,
        ];
    }

    public function getDoctorSummary(int $doctorId)
    {
        doctor::findOrFail($doctorId);

        $totalDueUsd = (float) Doctor_Earning::where('doctor_id', $doctorId)->sum('amount_usd');
        $totalPaidUsd = (float) Doctor_Payment::where('doctor_id', $doctorId)->sum('amount_usd');

        $totalDueSyp = (float) Doctor_Earning::where('doctor_id', $doctorId)->sum('amount_syp');
        $totalPaidSyp = (float) Doctor_Payment::where('doctor_id', $doctorId)->sum('amount_syp');

        $missingDueSyp = Doctor_Earning::where('doctor_id', $doctorId)
            ->whereNull('amount_syp')
            ->get();

        foreach ($missingDueSyp as $earning) {
            $earningRate = $earning->exchangeRate?->rate;
            if (!$earningRate && $earning->treatmentSession) {
                $earningRate = $earning->treatmentSession->exchangeRate?->rate;
            }

            if ($earningRate) {
                $totalDueSyp += round(((float) $earning->amount_usd) * (float) $earningRate, 2);
            }
        }

        $missingPaidSyp = Doctor_Payment::where('doctor_id', $doctorId)
            ->whereNull('amount_syp')
            ->get();

        foreach ($missingPaidSyp as $payment) {
            $paymentRate = $payment->exchangeRate?->rate;
            if ($paymentRate) {
                $totalPaidSyp += round(((float) $payment->amount_usd) * (float) $paymentRate, 2);
            }
        }

        $rateRecord = $this->exchangeRateService->getCurrentUsdToSypRate();
        $rate = (float) $rateRecord->rate;

        $remainingUsd = max($totalDueUsd - $totalPaidUsd, 0);
        $remainingSyp = round($remainingUsd * $rate, 2);

        return [
            'doctor_id' => $doctorId,
            'totals' => [
                'due_usd' => $totalDueUsd,
                'due_syp' => $totalDueSyp,
                'paid_usd' => $totalPaidUsd,
                'paid_syp' => $totalPaidSyp,
                'remaining_usd' => $remainingUsd,
                'remaining_syp' => $remainingSyp,
            ],
        ];
    }

    public function getMySummary()
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return [
                'success' => false,
                'message' => "هذا المستخدم ليس دكتور"
            ];
            //throw new \Exception('هذا المستخدم ليس دكتور');
        }

        return $this->getDoctorSummary($doctor->id);
    }
}
