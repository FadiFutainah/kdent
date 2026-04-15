<?php
namespace App\Services;
use App\Models\Doctor_Schedules;
use Illuminate\Support\Facades\Auth;
class DoctorService
{
    public function setDoctorSchedule($userId, $data)
    {
        $doctor = Auth::user()->doctor;

    if (!$doctor) {
        throw new \Exception("هذا المستخدم ليس دكتور");
    }

    // 🧹 حذف القديم (اختياري)
    // Doctor_Schedules::where('doctor_id', $doctor->id)->delete();

    foreach ($data['schedules'] as $dayData) {

        foreach ($dayData['shifts'] as $shift) {

            Doctor_Schedules::create([
                'doctor_id' => $doctor->id,
                'day' => $dayData['day'],
                'shift' => $shift['shift'],
                'start_time' => $shift['start_time'],
                'end_time' => $shift['end_time'],
            ]);
        }
    }

    return ['message' => 'تم حفظ الدوام بنجاح'];
}
}