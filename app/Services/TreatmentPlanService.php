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
        $doctor = Auth::user()->doctor;

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

            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (empty($itemData['category_id']) || !array_key_exists('price_usd', $itemData)) {
                        throw new \Exception('يجب تحديد اسم العلاج والسعر المتوقع');
                    }

                    $item = Plan_Item::create([
                        'plan_id' => $plan->id,
                        'category_id' => $itemData['category_id'],
                        'price_usd' => $itemData['price_usd'],
                        'price_syp' => $itemData['price_syp'] ?? null,
                        'target_teeth' => $itemData['target_teeth'] ?? null,
                        'status' => $itemData['status'] ?? 'in_progress',
                        'sequence' => $itemData['sequence'] ?? 1,
                    ]);

                    if (!empty($itemData['sessions'])) {
                        foreach ($itemData['sessions'] as $sessionData) {
                            Treatment_Session::create([
                                'plan_item_id' => $item->id,
                                'doctor_id' => $doctor->id,
                                'appointment_id' => $sessionData['appointment_id'] ?? null,
                                'rprice_usd' => $sessionData['rprice_usd'] ?? null,
                                'rprice_syp' => $sessionData['rprice_syp'] ?? null,
                                'session_date' => $sessionData['session_date'] ?? null,
                                'status' => $sessionData['status'] ?? 'in_progress',
                                'clinical_notes' => $sessionData['clinical_notes'] ?? null,
                                'is_last_session' => $sessionData['is_last_session'] ?? false,
                            ]);
                        }
                    }
                }
            }

            return $plan->load([
                'items.category',
                'items.sessions',
            ]);
        });
    }

    public function getPatientPlans(int $patientId)
    {
        $doctor = Auth::user()->doctor;

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

    public function updatePlan(int $planId, array $data)
    {
        $doctor = Auth::user()->doctor;

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

            if (!empty($data['delete_item_ids'])) {
                Plan_Item::where('plan_id', $plan->id)
                    ->whereIn('id', $data['delete_item_ids'])
                    ->delete();
            }

            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (!empty($itemData['id'])) {
                        $item = Plan_Item::where('plan_id', $plan->id)
                            ->where('id', $itemData['id'])
                            ->firstOrFail();

                        $itemUpdates = [];

                        if (array_key_exists('category_id', $itemData)) {
                            $itemUpdates['category_id'] = $itemData['category_id'];
                        }

                        if (array_key_exists('price_usd', $itemData)) {
                            $itemUpdates['price_usd'] = $itemData['price_usd'];
                        }

                        if (array_key_exists('price_syp', $itemData)) {
                            $itemUpdates['price_syp'] = $itemData['price_syp'];
                        }

                        if (array_key_exists('target_teeth', $itemData)) {
                            $itemUpdates['target_teeth'] = $itemData['target_teeth'];
                        }

                        if (array_key_exists('status', $itemData)) {
                            $itemUpdates['status'] = $itemData['status'];
                        }

                        if (array_key_exists('sequence', $itemData)) {
                            $itemUpdates['sequence'] = $itemData['sequence'] ?? 1;
                        }

                        if ($itemUpdates) {
                            $item->update($itemUpdates);
                        }
                    } else {
                        if (empty($itemData['category_id']) || !array_key_exists('price_usd', $itemData)) {
                            throw new \Exception('يجب تحديد اسم العلاج والسعر المتوقع');
                        }

                        Plan_Item::create([
                            'plan_id' => $plan->id,
                            'category_id' => $itemData['category_id'],
                            'price_usd' => $itemData['price_usd'],
                            'price_syp' => $itemData['price_syp'] ?? null,
                            'target_teeth' => $itemData['target_teeth'] ?? null,
                            'status' => $itemData['status'] ?? 'in_progress',
                            'sequence' => $itemData['sequence'] ?? 1,
                        ]);
                    }
                }
            }

            return $plan->load([
                'items.category',
                'items.sessions',
            ]);
        });
    }
}
