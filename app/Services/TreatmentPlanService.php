<?php

namespace App\Services;

use App\Models\Plan_Item;
use App\Models\Treatment_Plan;
use App\Models\Treatment_Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreatmentPlanService
{
    public function createPlan(array $data)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        return DB::transaction(function () use ($data, $doctor) {
            $plan = Treatment_Plan::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctor->id,
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $plan->load([
                'items.category',
                'items.sessions',
            ]);
        });
    }

    public function getPatientPlans(int $patientId)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        return Treatment_Plan::with([
            'items.category',
            'items.sessions',
        ])
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->orderByDesc('start_date')
            ->get();
    }

    public function searchPlans(array $filters)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $query = Treatment_Plan::with([
          //  'patient.user',
            'items.category',
            'items.sessions',
        ])->where('doctor_id', $doctor->id);

        if (!empty($filters['phone_number'])) {
            $query->whereHas('patient.user', function ($q) use ($filters) {
                $q->where('phone_number', $filters['phone_number']);
            });
        }

        if (!empty($filters['patient_name'])) {
            $name = $filters['patient_name'];
            $query->whereHas('patient.user', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        return $query
            ->orderByDesc('start_date')
            ->get();
    }

    public function updatePlan(int $planId, array $data)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $plan = Treatment_Plan::where('id', $planId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        return DB::transaction(function () use ($plan, $data) {
            $planUpdates = [];

            if (array_key_exists('name', $data)) {
                $planUpdates['name'] = $data['name'];
            }

            if (array_key_exists('start_date', $data)) {
                $planUpdates['start_date'] = $data['start_date'];
            }

            if (array_key_exists('notes', $data)) {
                $planUpdates['notes'] = $data['notes'];
            }

            if ($planUpdates) {
                $plan->update($planUpdates);
            }

            return $plan->load([
                'items.category',
                'items.sessions',
            ]);
        });
    }

    public function addPlanItem(int $planId, array $data)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $plan = Treatment_Plan::where('id', $planId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        if (empty($data['category_id']) || !array_key_exists('price_usd', $data)) {
            throw new \Exception('يجب تحديد اسم العلاج والسعر المتوقع');
        }

        $item = Plan_Item::create([
            'plan_id' => $plan->id,
            'category_id' => $data['category_id'],
            'price_usd' => $data['price_usd'],
            'price_syp' => $data['price_syp'] ?? null,
            'target_teeth' => $data['target_teeth'] ?? null,
            'status' => $data['status'] ?? 'in_progress',
        ]);

        return $item->load('category', 'sessions');
    }

    public function updatePlanItem(int $planId, int $itemId, array $data)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $plan = Treatment_Plan::where('id', $planId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $item = Plan_Item::where('plan_id', $plan->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $updates = [];

        if (array_key_exists('category_id', $data)) {
            $updates['category_id'] = $data['category_id'];
        }

        if (array_key_exists('price_usd', $data)) {
            $updates['price_usd'] = $data['price_usd'];
        }

        if (array_key_exists('price_syp', $data)) {
            $updates['price_syp'] = $data['price_syp'];
        }

        if (array_key_exists('target_teeth', $data)) {
            $updates['target_teeth'] = $data['target_teeth'];
        }

        if (array_key_exists('status', $data)) {
            $updates['status'] = $data['status'];
        }

        if ($updates) {
            $item->update($updates);
        }

        return $item->fresh()->load('category', 'sessions');
    }

    public function deletePlanItem(int $planId, int $itemId)
    {
        $doctor = Auth::user()->doctor->first();

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $plan = Treatment_Plan::where('id', $planId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $item = Plan_Item::where('plan_id', $plan->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->delete();

        return ['message' => 'تم حذف المرحلة بنجاح'];
    }
}
