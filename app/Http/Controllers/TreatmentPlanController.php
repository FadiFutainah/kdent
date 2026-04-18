<?php

namespace App\Http\Controllers;

use App\Services\TreatmentPlanService;
use Illuminate\Http\Request;

class TreatmentPlanController extends Controller
{
    protected $service;

    public function __construct(TreatmentPlanService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'name' => 'required|string',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return response()->json(
            $this->service->createPlan($data)
        );
    }

    public function patientPlans(int $patientId)
    {
        return response()->json(
            $this->service->getPatientPlans($patientId)
        );
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|min:2',
            'phone_number' => 'nullable|string|min:6',
        ]);

        if (empty($data['name']) && empty($data['phone_number'])) {
            return response()->json([
                'message' => 'يجب إدخال اسم المريض أو رقم الهاتف للبحث',
            ], 422);
        }

        return response()->json(
            $this->service->searchPlans($data)
        );
    }

    public function update(Request $request, int $planId)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        return response()->json(
            $this->service->updatePlan($planId, $data)
        );
    }

    public function addItem(Request $request, int $planId)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:treatment_categories,id',
            'price_usd' => 'required|numeric|min:0',
            'price_syp' => 'nullable|numeric|min:0',
            'target_teeth' => 'nullable|string',
            'status' => 'nullable|in:in_progress,completed',
        ]);

        return response()->json(
            $this->service->addPlanItem($planId, $data)
        );
    }

    public function updateItem(Request $request, int $planId, int $itemId)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:treatment_categories,id',
            'price_usd' => 'nullable|numeric|min:0',
            'price_syp' => 'nullable|numeric|min:0',
            'target_teeth' => 'nullable|string',
            'status' => 'nullable|in:in_progress,completed',
        ]);

        return response()->json(
            $this->service->updatePlanItem($planId, $itemId, $data)
        );
    }

    public function deleteItem(int $planId, int $itemId)
    {
        return response()->json(
            $this->service->deletePlanItem($planId, $itemId)
        );
    }
}
