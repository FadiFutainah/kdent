<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function setSchedule(Request $request)
{
    $data = $request->validate([
        'schedules' => 'required|array',
        'schedules.*.day' => 'required|string',
        'schedules.*.shifts' => 'required|array',
        'schedules.*.shifts.*.shift' => 'required|in:morning,evening',
        'schedules.*.shifts.*.start_time' => 'required',
        'schedules.*.shifts.*.end_time' => 'required',
    ]);

    return response()->json(
        $this->service->setDoctorSchedule(auth()->id(), $data)
    );
}
}
