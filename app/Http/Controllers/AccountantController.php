<?php

namespace App\Http\Controllers;

use App\Services\DoctorFinanceService;

class AccountantController extends Controller
{
    public function __construct(
        private DoctorFinanceService $service
    ) {}

    public function doctorPlansDues(int $doctorId)
    {
        return response()->json([
            'data' => $this->service->getDoctorPlansDues($doctorId)
        ]);
    }
}