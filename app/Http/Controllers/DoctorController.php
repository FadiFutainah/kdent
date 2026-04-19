<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DoctorService;
class DoctorController extends Controller
{
    protected $service;

    public function __construct(DoctorService $service)
    {
        $this->service = $service;
    }
  public function addAvailableTime(Request $request)
{
    $data = $request->validate([
        'day' => 'required|string',
        'start_time' => 'required',
        'end_time' => 'required',
    ]);

    return response()->json(
        $this->service->addAvailableTime($data)
    );
}
    // تعديل
    public function updateAvailableTime(Request $request, $id)
    {
        $data = $request->validate([
            'day' => 'required|in:sun,mon,tue,wed,thu,fri,sat',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $result = $this->service->updateAvailableTime($id, $data);

        return response()->json($result);
    }

    // حذف
    public function deleteAvailableTime($id)
    {
        $result = $this->service->deleteAvailableTime($id);

        return response()->json($result);
    }
///////////////////////////////////////////////////

public function myPatients()
{
    return response()->json($this->service->getDoctorPatients());
}

public function todayAppointments()
{
    return response()->json($this->service->getTodayAppointments());
}

public function upcomingAppointments()
{
    return response()->json($this->service->getUpcomingAppointmentsGrouped());
}
}
