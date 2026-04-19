<?php

namespace App\Http\Controllers;

use App\Services\DoctorFinanceService;
use Illuminate\Http\Request;

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
            'amount_usd' => 'nullable|numeric|min:0',
            'amount_syp' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        return response()->json(
            $this->service->recordPayment($doctorId, $data)
        );
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
}
