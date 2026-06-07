<?php

namespace App\Http\Controllers;

use App\Services\DoctorFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorFinanceController extends Controller
{
    protected $service;

    public function __construct(DoctorFinanceService $service)
    {
        $this->service = $service;
    }

    public function recordPayment(Request $request, int $doctorId)
    {
        $data = $request->validate([
            'amount_usd' => 'nullable|numeric|gt:0|required_without:amount_syp',
            'amount_syp' => 'nullable|numeric|gt:0|required_without:amount_usd',
            'payment_date' => 'nullable|date',
        ]);

        try {
            return response()->json(
                $this->service->recordPayment($doctorId, $data)
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function summary(int $doctorId)
    {
        return response()->json(
            $this->service->getDoctorSummary($doctorId)
        );
    }

    public function mySummary()
    {
        return response()->json(
            $this->service->getMySummary()
        );
    }

    public function doctorPlansDues()
    {
        $doctor = Auth::user()->doctor;

        return response()->json([
            'data' => $this->service->getDoctorPlansDues($doctor->id)
        ]);
    }

    
}
