<?php

namespace App\Http\Controllers;

use App\Services\TreatmentSessionService;
use Illuminate\Http\Request;

class TreatmentSessionController extends Controller
{
    protected $service;

    public function __construct(TreatmentSessionService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_item_id' => 'required|exists:plan_items,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'rprice_usd' => 'nullable|numeric|min:0',
            'rprice_syp' => 'nullable|numeric|min:0',
            'session_date' => 'required|date',
            'status' => 'nullable|in:in_progress,completed',
            'clinical_notes' => 'nullable|string',
            'is_last_session' => 'nullable|boolean',
        ]);

        return response()->json(
            $this->service->createSession($data)
        );
    }

    public function update(Request $request, int $sessionId)
    {
        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'rprice_usd' => 'nullable|numeric|min:0',
            'rprice_syp' => 'nullable|numeric|min:0',
            'session_date' => 'nullable|date',
            'status' => 'nullable|in:in_progress,completed',
            'clinical_notes' => 'nullable|string',
            'is_last_session' => 'nullable|boolean',
        ]);

        return response()->json(
            $this->service->updateSession($sessionId, $data)
        );
    }

    public function complete(Request $request, int $sessionId)
    {
        $data = $request->validate([
            'clinical_notes' => 'nullable|string',
            'is_last_session' => 'nullable|boolean',
        ]);

        return response()->json(
            $this->service->completeSession($sessionId, $data)
        );
    }
}
