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
    //اضافة وقت متاح
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
            'day' => 'required|in:sun,mon,tues,wed,thy,fri,sat',
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
    //عرض الاوقات المتاحة
    public function getMyAvailableTimes()
{
    $schedules = $this->service->getMyAvailableTimes();
    return response()->json($schedules);
}
///////////////////////////////////////////////////
//عرض قائمة المرضى
public function myPatients()
{
    return response()->json($this->service->getDoctorPatients());
}
// عرض مواعيد اليوم
public function todayAppointments()
{
    return response()->json($this->service->getTodayAppointments());
}
// عرض المواعيد لعشرة ايام
public function upcomingAppointments()
{
    return response()->json($this->service->getUpcomingAppointmentsGrouped());
}

public function searchPatients(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|min:2',
    ]);

    return response()->json(
        $this->service->searchPatientsByName($data['name'])
    );
}
//طلب مواد من المخزن
public function store(Request $request)
{
    $data = $request->validate([
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string'
    ]);

    $req = $this->service->createRequest($data);

    return response()->json([
        'message' => 'Request created successfully',
        'data' => $req
    ]);
}



}
