<?php

namespace App\Services;

use App\Models\Doctor_Earning;
use App\Models\Plan_Item;
use App\Models\Treatment_Session;
use Illuminate\Support\Facades\Auth;

class TreatmentSessionService
{
    public function __construct(private ExchangeRateService $exchangeRateService)
    {
    }

    public function createSession(array $data)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $planItem = Plan_Item::with('plan')
            ->where('id', $data['plan_item_id'])
            ->firstOrFail();

        if ($planItem->plan->doctor_id !== $doctor->id) {
            throw new \Exception('لا تملك صلاحية إنشاء جلسة لهذا العنصر');
        }

        $payload = $this->applyExchangeRate($data);

        $session = Treatment_Session::create([
            'plan_item_id' => $planItem->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $payload['appointment_id'] ?? null,
            'exchange_rate_id' => $payload['exchange_rate_id'] ?? null,
            'name' => $payload['name'] ?? null,
            'rprice_usd' => $payload['rprice_usd'] ?? null,
            'rprice_syp' => $payload['rprice_syp'] ?? null,
            'session_date' => $payload['session_date'] ?? null,
            'status' => $payload['status'] ?? 'in_progress',
            'clinical_notes' => $payload['clinical_notes'] ?? null,
            'is_last_session' => $payload['is_last_session'] ?? false,
        ]);

        $this->syncStatuses($planItem);
        $this->syncDoctorEarning($session->fresh());

        return $session;
    }

    public function updateSession(int $sessionId, array $data)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $session = Treatment_Session::with('planItem.plan')
            ->where('id', $sessionId)
            ->firstOrFail();

        if ($session->planItem->plan->doctor_id !== $doctor->id) {
            throw new \Exception('لا تملك صلاحية تعديل هذه الجلسة');
        }

        $updates = [];
        $fields = [
            'appointment_id',
            'name',
            'rprice_usd',
            'rprice_syp',
            'session_date',
            'status',
            'clinical_notes',
            'is_last_session',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if ($updates) {
            $updates = $this->applyExchangeRate($updates);
            $session->update($updates);
        }

        $this->syncStatuses($session->planItem);
        $this->syncDoctorEarning($session->fresh());

        return $session->fresh();
    }

    public function completeSession(int $sessionId)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $session = Treatment_Session::with('planItem.plan')
            ->where('id', $sessionId)
            ->firstOrFail();

        if ($session->planItem->plan->doctor_id !== $doctor->id) {
            throw new \Exception('لا تملك صلاحية إنهاء هذه الجلسة');
        }

        $updates = ['status' => 'completed'];

        $session->update($updates);

        $session = $session->fresh();
        $session->update($this->applyExchangeRate($session->toArray()));

        $this->syncStatuses($session->planItem);
        $this->syncDoctorEarning($session->fresh());

        return $session->fresh();
    }

    private function applyExchangeRate(array $data): array
    {
        $hasPriceContext = array_key_exists('rprice_usd', $data) || array_key_exists('rprice_syp', $data);

        if (!$hasPriceContext) {
            return $data;
        }

        $rateRecord = $this->exchangeRateService->getCurrentUsdToSypRate();
        $data['exchange_rate_id'] = $rateRecord->id;

        if (array_key_exists('rprice_usd', $data)) {
            $usd = $data['rprice_usd'];
            $sypProvided = array_key_exists('rprice_syp', $data) && !is_null($data['rprice_syp']);

            if (!is_null($usd) && !$sypProvided) {
                $data['rprice_syp'] = round(((float) $usd) * (float) $rateRecord->rate, 2);
            }
        }

        return $data;
    }

    private function syncDoctorEarning(Treatment_Session $session): void
    {
        // Keep earnings table consistent with real execution state.
        if ($session->status !== 'completed') {
            Doctor_Earning::where('treatment_session_id', $session->id)->delete();
            return;
        }

        $doctor = $session->doctor;
        $percentage = (float) ($doctor->percentage ?? 0);

        $amountUsd = null;
        if (!is_null($session->rprice_usd)) {
            $amountUsd = round(((float) $session->rprice_usd * $percentage) / 100, 2);
        }

        $exchangeRate = $session->exchangeRate ?? ($session->exchange_rate_id ? $session->exchangeRate()->first() : null);
        if (!$exchangeRate) {
            $exchangeRate = $this->exchangeRateService->getCurrentUsdToSypRate();
        }

        $amountSyp = null;
        if (!is_null($amountUsd)) {
            $amountSyp = round($amountUsd * (float) $exchangeRate->rate, 2);
        }

        Doctor_Earning::updateOrCreate(
            ['treatment_session_id' => $session->id],
            [
                'doctor_id' => $session->doctor_id,
                'exchange_rate_id' => $exchangeRate->id,
                'percentage' => $percentage,
                'amount_usd' => $amountUsd ?? 0,
                'amount_syp' => $amountSyp,
                'earning_date' => $session->session_date ?? now(),
            ]
        );
    }

    private function syncStatuses(Plan_Item $planItem): void
    {
        $hasAnySession = Treatment_Session::where('plan_item_id', $planItem->id)->exists();

        if (!$hasAnySession) {
            $planItem->update(['status' => 'in_progress']);
        } else {
            $hasOpenSessions = Treatment_Session::where('plan_item_id', $planItem->id)
                ->where('status', '!=', 'completed')
                ->exists();

            $planItem->update([
                'status' => $hasOpenSessions ? 'in_progress' : 'completed',
            ]);
        }

        $plan = $planItem->plan;
        $hasAnyItems = Plan_Item::where('plan_id', $plan->id)->exists();

        if (!$hasAnyItems) {
            $plan->update(['status' => 'in_progress']);
            return;
        }

        $hasOpenItems = Plan_Item::where('plan_id', $plan->id)
            ->where('status', '!=', 'completed')
            ->exists();

        $plan->update([
            'status' => $hasOpenItems ? 'in_progress' : 'completed',
        ]);
    }
}
