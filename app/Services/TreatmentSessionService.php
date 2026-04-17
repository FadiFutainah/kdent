<?php

namespace App\Services;

use App\Models\Plan_Item;
use App\Models\Treatment_Session;
use Illuminate\Support\Facades\Auth;

class TreatmentSessionService
{
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

        return Treatment_Session::create([
            'plan_item_id' => $planItem->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'rprice_usd' => $data['rprice_usd'] ?? null,
            'rprice_syp' => $data['rprice_syp'] ?? null,
            'session_date' => $data['session_date'],
            'status' => $data['status'] ?? 'in_progress',
            'clinical_notes' => $data['clinical_notes'] ?? null,
            'is_last_session' => $data['is_last_session'] ?? false,
        ]);
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
            $session->update($updates);
        }

        return $session;
    }

    public function completeSession(int $sessionId, array $data = [])
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

        if (array_key_exists('clinical_notes', $data)) {
            $updates['clinical_notes'] = $data['clinical_notes'];
        }

        if (array_key_exists('is_last_session', $data)) {
            $updates['is_last_session'] = $data['is_last_session'];
        }

        $session->update($updates);

        return $session;
    }
}
