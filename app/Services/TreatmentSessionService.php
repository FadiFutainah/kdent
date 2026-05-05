<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor_Earning;
use App\Models\Plan_Item;
use App\Models\Treatment_Session;
use Carbon\Carbon;
use App\Models\Invoice;
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
            'appointment_id' => null,
            'exchange_rate_id' => $payload['exchange_rate_id'] ?? null,
            'name' => $payload['name'] ?? null,
            'rprice_usd' => $payload['rprice_usd'] ?? null,
            'rprice_syp' => $payload['rprice_syp'] ?? null,
            'session_date' => null,
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

        if ($session->status === 'completed') {
            throw new \DomainException('لا يمكن تعديل الجلسة بعد إنهائها');
        }

        $updates = [];
        $fields = [
            'name',
            'rprice_usd',
            'rprice_syp',
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

        if ($session->status === 'completed') {
            throw new \DomainException('هذه الجلسة منتهية مسبقا');
        }

        $appointment = $this->findConfirmedAppointmentForToday($session->planItem);

        if (!$appointment) {
            throw new \DomainException('لا يوجد موعد مؤكد اليوم لإكمال هذه الجلسة');
        }

        $updates = [
            'status' => 'completed',
            'appointment_id' => $appointment->id,
            'session_date' => $appointment->appointment_date?->toDateString(),
        ];

        $session->update($updates);

        $appointment->update(['status' => 'completed']);

        $session = $session->fresh();
        $priceUpdates = [];

        if (!is_null($session->rprice_usd)) {
            $priceUpdates['rprice_usd'] = $session->rprice_usd;
        }

        if (!is_null($session->rprice_syp)) {
            $priceUpdates['rprice_syp'] = $session->rprice_syp;
        }

        if ($priceUpdates) {
            $session->update($this->applyExchangeRate($priceUpdates));
        }

        $this->syncStatuses($session->planItem);
        $this->syncDoctorEarning($session->fresh());
        ///////////////
        $invoice = Invoice::where('plan_id', $session->planItem->plan_id)->first();
      if ($invoice && $invoice->type === 'patient') {
              app(\App\Services\InvoiceService::class)
            ->addSession($invoice, $session);
}

/////////////////////

        return $session->fresh();
    }

    private function applyExchangeRate(array $data): array
    {
        $hasPriceContext = array_key_exists('rprice_usd', $data) || array_key_exists('rprice_syp', $data);

        if (!$hasPriceContext) {
            return $data;
        }

        $usdProvided = array_key_exists('rprice_usd', $data) && !is_null($data['rprice_usd']);
        $sypProvided = array_key_exists('rprice_syp', $data) && !is_null($data['rprice_syp']);

        if (!$usdProvided && !$sypProvided) {
            return $data;
        }

        $rateRecord = $this->exchangeRateService->getCurrentUsdToSypRate();
        $rate = (float) $rateRecord->rate;
        $data['exchange_rate_id'] = $rateRecord->id;

        if ($usdProvided && !$sypProvided) {
            $data['rprice_syp'] = round(((float) $data['rprice_usd']) * $rate, 2);
        }

        if (!$usdProvided && $sypProvided && $rate > 0) {
            $data['rprice_usd'] = round(((float) $data['rprice_syp']) / $rate, 2);
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

        $session->loadMissing('planItem.plan.doctor', 'exchangeRate');
        $doctor = $session->planItem?->plan?->doctor;

        if (!$doctor) {
            throw new \Exception('تعذر تحديد دكتور الجلسة من الخطة');
        }

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
                'doctor_id' => $doctor->id,
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

    private function findConfirmedAppointmentForToday(Plan_Item $planItem): ?Appointment
    {
        $appointments = Appointment::where('patient_id', $planItem->plan->patient_id)
            ->where('doctor_id', $planItem->plan->doctor_id)
            ->whereDate('appointment_date', Carbon::today())
            ->where('status', 'confirmed')
            ->orderBy('appointment_date')
            ->get(['id', 'appointment_date', 'status']);

        foreach ($appointments as $appointment) {
            $isLinked = Treatment_Session::where('appointment_id', $appointment->id)->exists();

            if (!$isLinked) {
                return $appointment;
            }
        }

        return null;
    }
}
