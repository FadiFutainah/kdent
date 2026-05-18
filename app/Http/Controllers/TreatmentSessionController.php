<?php

namespace App\Http\Controllers;

use App\Services\TreatmentSessionService;
use Illuminate\Http\Request;

class TreatmentSessionController extends Controller
{
    public function __construct(private TreatmentSessionService $service)
    {
    }

    public function store(Request $request, int $itemId)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
        ]);

        $data['plan_item_id'] = $itemId;

        return response()->json($this->service->createSession($data));
    }

    public function update(Request $request, int $sessionId)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
        ]);

        try {
            return response()->json($this->service->updateSession($sessionId, $data));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(int $sessionId)
    {
        try {
            return response()->json($this->service->completeSession($sessionId));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}