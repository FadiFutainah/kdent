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
            'items' => 'nullable|array',
            'items.*.category_id' => 'nullable|exists:treatment_categories,id',
            'items.*.price_usd' => 'nullable|numeric|min:0',
            'items.*.price_syp' => 'nullable|numeric|min:0',
            'items.*.target_teeth' => 'nullable|string',
            'items.*.status' => 'nullable|in:in_progress,completed',
            'items.*.sequence' => 'nullable|integer|min:1',
            'items.*.sessions' => 'nullable|array',
            'items.*.sessions.*.appointment_id' => 'nullable|exists:appointments,id',
            'items.*.sessions.*.rprice_usd' => 'nullable|numeric|min:0',
            'items.*.sessions.*.rprice_syp' => 'nullable|numeric|min:0',
            'items.*.sessions.*.session_date' => 'nullable|date',
            'items.*.sessions.*.status' => 'nullable|in:in_progress,completed',
            'items.*.sessions.*.clinical_notes' => 'nullable|string',
            'items.*.sessions.*.is_last_session' => 'nullable|boolean',
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

    public function update(Request $request, int $planId)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'delete_item_ids' => 'nullable|array',
            'delete_item_ids.*' => 'integer|exists:plan_items,id',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|integer|exists:plan_items,id',
            'items.*.category_id' => 'nullable|exists:treatment_categories,id',
            'items.*.price_usd' => 'nullable|numeric|min:0',
            'items.*.price_syp' => 'nullable|numeric|min:0',
            'items.*.target_teeth' => 'nullable|string',
            'items.*.status' => 'nullable|in:in_progress,completed',
            'items.*.sequence' => 'nullable|integer|min:1',
        ]);

        return response()->json(
            $this->service->updatePlan($planId, $data)
        );
    }
}
