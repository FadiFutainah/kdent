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

        $session = Treatment_Session::create([
            'plan_item_id'   => $planItem->id,
            'appointment_id' => null,
            'name'           => $data['name'] ?? null,
            'session_date'   => null,
            'status'         => 'in_progress',
        ]);

        $this->syncStatuses($planItem);

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

        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
        }

        if ($updates) {
            $session->update($updates);
        }

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
            throw new \DomainException('هذه الجلسة منتهية مسبقاً');
        }

        $appointment = $this->findConfirmedAppointmentForToday($session->planItem);

        if (!$appointment) {
            throw new \DomainException('لا يوجد موعد مؤكد اليوم لإكمال هذه الجلسة');
        }

        $session->update([
            'status'         => 'completed',
            'appointment_id' => $appointment->id,
            'session_date'   => $appointment->appointment_date->toDateString(),
        ]);

        $appointment->update(['status' => 'completed']);

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


    private function syncStatuses(Plan_Item $planItem): void
    {
        $hasOpen = Treatment_Session::where('plan_item_id', $planItem->id)
            ->where('status', '!=', 'completed')
            ->exists();

        $hasSessions = Treatment_Session::where('plan_item_id', $planItem->id)->exists();

        $planItem->update([
            'status' => ($hasSessions && !$hasOpen) ? 'completed' : 'in_progress',
        ]);

        $plan = $planItem->plan;

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