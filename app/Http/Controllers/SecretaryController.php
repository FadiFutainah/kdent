<?php

namespace App\Http\Controllers;

use App\Services\SecretaryService;

class SecretaryController extends Controller
{
    public function __construct(private SecretaryService $service)
    {
    }

    public function doctorPatients(int $doctorId)
    {
        return response()->json(
            $this->service->getDoctorPatients($doctorId)
        );
    }

    public function doctorTodayAppointments(int $doctorId)
    {
        return response()->json(
            $this->service->getDoctorTodayAppointments($doctorId)
        );
    }

}
