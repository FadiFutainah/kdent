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

    public function store(Request $request, int $itemId)
    {
        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'name' => 'nullable|string',
            'rprice_usd' => 'nullable|numeric|min:0',
            'rprice_syp' => 'nullable|numeric|min:0',
            'session_date' => 'nullable|date',
            'status' => 'nullable|in:in_progress',
            'clinical_notes' => 'nullable|string',
            'is_last_session' => 'nullable|boolean',
        ]);

        $data['plan_item_id'] = $itemId;

        return response()->json(
            $this->service->createSession($data)
        );
    }

    public function update(Request $request, int $sessionId)
    {
        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'name' => 'nullable|string',
            'rprice_usd' => 'nullable|numeric|min:0',
            'rprice_syp' => 'nullable|numeric|min:0',
            'session_date' => 'nullable|date',
            'status' => 'prohibited',
            'clinical_notes' => 'nullable|string',
            'is_last_session' => 'nullable|boolean',
        ]);

        try {
            return response()->json(
                $this->service->updateSession($sessionId, $data)
            );
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function complete(Request $request, int $sessionId)
    {
        try {
            return response()->json(
                $this->service->completeSession($sessionId)
            );
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
