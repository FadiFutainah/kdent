<?php

namespace App\Services;

use App\Models\Plan_Item;
use App\Models\Treatment_Plan;
use App\Models\Treatment_Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreatmentPlanService
{
    public function __construct(private ExchangeRateService $exchangeRateService)
    {
    }

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

            return $plan->load([
                'items.category',
                'items.sessions',
            ]);
        });
    }


    public function searchPlans(array $filters)
    {
        $scope = $this->resolvePlanAccessScope();
        $query = $this->scopedPlansQuery($scope);

        if ($scope['role'] === 'doctor' && !empty($filters['phone_number'])) {
            $query->whereHas('patient.user', function ($q) use ($filters) {
                $q->where('phone_number', $filters['phone_number']);
            });
        }

        $name = $filters['patient_name'] ?? $filters['name'] ?? null;
        if ($scope['role'] === 'doctor' && !empty($name)) {
            $query->whereHas('patient.user', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        return $query
            ->orderByDesc('start_date')
            ->get()
            ->map(function (Treatment_Plan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'status' => $plan->status,
                    'progress_percent' => $plan->progress_percent,
                ];
            })
            ->values();
    }

    public function getMyPlans()
    {
        $scope = $this->resolvePlanAccessScope();

        return $this->scopedPlansQuery($scope)
            ->orderByDesc('start_date')
            ->get()
            ->map(function (Treatment_Plan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'status' => $plan->status,
                    'progress_percent' => $plan->progress_percent,
                ];
            })
            ->values();
    }

    public function getPlanDetails(int $planId)
    {
        $scope = $this->resolvePlanAccessScope();

        $plan = Treatment_Plan::with(['items.category'])
            ->where($scope['column'], $scope['id'])
            ->where('id', $planId)
            ->firstOrFail();

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'status' => $plan->status,
            'progress_percent' => $plan->progress_percent,
            'items' => $plan->items->map(function (Plan_Item $item) {
                return [
                    'id' => $item->id,
                    'category_id' => $item->category_id,
                    'category_name' => $item->category?->name,
                    'status' => $item->status,
                ];
            })->values(),
        ];
    }

    public function getPlanItemDetails(int $planId, int $itemId)
    {
        $scope = $this->resolvePlanAccessScope();

        $plan = Treatment_Plan::where($scope['column'], $scope['id'])
            ->where('id', $planId)
            ->firstOrFail();

        $item = Plan_Item::with(['category', 'sessions'])
            ->where('plan_id', $plan->id)
            ->where('id', $itemId)
            ->firstOrFail();

        return [
            'id' => $item->id,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
            'price_usd' => $item->price_usd,
            'price_syp' => $item->price_syp,
            'target_teeth' => $item->target_teeth,
            'status' => $item->status,
            'sessions' => $item->sessions->map(function (Treatment_Session $session) {
                return [
                    'id' => $session->id,
                    'name' => $session->name,
                    'status' => $session->status,
                ];
            })->values(),
        ];
    }

    public function getSessionDetails(int $planId, int $itemId, int $sessionId)
    {
        $scope = $this->resolvePlanAccessScope();

        $plan = Treatment_Plan::where($scope['column'], $scope['id'])
            ->where('id', $planId)
            ->firstOrFail();

        $item = Plan_Item::where('plan_id', $plan->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $session = Treatment_Session::with(['exchangeRate', 'earning'])
            ->where('plan_item_id', $item->id)
            ->where('id', $sessionId)
            ->firstOrFail();

        return [
            'id' => $session->id,
            'name' => $session->name,
            'status' => $session->status,
            'session_date' => $session->session_date,
            'rprice_usd' => $session->rprice_usd,
            'rprice_syp' => $session->rprice_syp,
            'clinical_notes' => $session->clinical_notes,
            'is_last_session' => $session->is_last_session,
            'exchange_rate' => $session->exchangeRate ? [
                'id' => $session->exchangeRate->id,
                'rate' => $session->exchangeRate->rate,
                'fetched_at' => $session->exchangeRate->fetched_at,
            ] : null,
            'earning' => $session->earning ? [
                'id' => $session->earning->id,
                'amount_usd' => $session->earning->amount_usd,
                'amount_syp' => $session->earning->amount_syp,
                'percentage' => $session->earning->percentage,
            ] : null,
        ];
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

            return $plan->load([
                'items.category',
                'items.sessions',
            ]);
        });
    }

    public function addPlanItem(int $planId, array $data)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $plan = Treatment_Plan::where('id', $planId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $usdProvided = array_key_exists('price_usd', $data) && !is_null($data['price_usd']);
        $sypProvided = array_key_exists('price_syp', $data) && !is_null($data['price_syp']);

        if (empty($data['category_id']) || (!$usdProvided && !$sypProvided)) {
            throw new \Exception('يجب تحديد اسم العلاج والسعر المتوقع');
        }

        $payload = $this->applyItemExchangeRate($data);

        $item = Plan_Item::create([
            'plan_id' => $plan->id,
            'category_id' => $payload['category_id'],
            'price_usd' => $payload['price_usd'],
            'price_syp' => $payload['price_syp'] ?? null,
            'target_teeth' => $payload['target_teeth'] ?? null,
            'status' => $payload['status'] ?? 'in_progress',
        ]);

        return $item->load('category', 'sessions');
    }

    public function updatePlanItem(int $planId, int $itemId, array $data)
    {
        $doctor = Auth::user()->doctor;

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
            $updates = $this->applyItemExchangeRate($updates);
        }

        if ($updates) {
            $item->update($updates);
        }

        return $item->fresh()->load('category', 'sessions');
    }

    public function deletePlanItem(int $planId, int $itemId)
    {
        $doctor = Auth::user()->doctor;

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

    private function applyItemExchangeRate(array $data): array
    {
        $hasPriceContext = array_key_exists('price_usd', $data) || array_key_exists('price_syp', $data);

        if (!$hasPriceContext) {
            return $data;
        }

        $usdProvided = array_key_exists('price_usd', $data) && !is_null($data['price_usd']);
        $sypProvided = array_key_exists('price_syp', $data) && !is_null($data['price_syp']);

        if (!$usdProvided && !$sypProvided) {
            return $data;
        }

        $rateRecord = $this->exchangeRateService->getCurrentUsdToSypRate();
        $rate = (float) $rateRecord->rate;

        if ($usdProvided && !$sypProvided) {
            $data['price_syp'] = round(((float) $data['price_usd']) * $rate, 2);
        }

        if (!$usdProvided && $sypProvided && $rate > 0) {
            $data['price_usd'] = round(((float) $data['price_syp']) / $rate, 2);
        }

        return $data;
    }

    private function resolvePlanAccessScope(): array
    {
        $user = Auth::user();

        if ($user->hasRole('doctor')) {
            $doctor = $user->doctor;
            if (!$doctor) {
                throw new \Exception('هذا المستخدم ليس دكتور');
            }

            return [
                'role' => 'doctor',
                'column' => 'doctor_id',
                'id' => $doctor->id,
            ];
        }

        else if ($user->hasRole('patient')) {
            $patient = $user->patient;
            if (!$patient) {
                throw new \Exception('هذا المستخدم ليس مريض');
            }

            return [
                'role' => 'patient',
                'column' => 'patient_id',
                'id' => $patient->id,
            ];
        }

        throw new \Exception('ليس لديك صلاحية للوصول إلى الخطط العلاجية');
    }

    private function scopedPlansQuery(array $scope): Builder
    {
        return Treatment_Plan::query()->where($scope['column'], $scope['id']);
    }
}
