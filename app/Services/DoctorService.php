<?php
namespace App\Services;
use App\Models\Doctor_Schedules;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment_session;

class DoctorService
{
   public function addAvailableTime($data)
{
    $doctor = Auth::user()->doctor;

    if (!$doctor) {
        throw new \Exception("هذا المستخدم ليس دكتور");
    }

    // 🔥 منع التداخل
    $overlap = Doctor_Schedules::where('doctor_id', $doctor->id)
        ->where('day', $data['day'])
        ->where(function ($q) use ($data) {
            $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
              ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
        })
        ->exists();

    if ($overlap) {
        throw new \Exception("الفترة تتداخل مع فترة موجودة");
    }
    if ($data['start_time'] >= $data['end_time']) {
    throw new \Exception("وقت البداية لازم يكون قبل النهاية");
}

    return Doctor_Schedules::create([
        'doctor_id' => $doctor->id,
        'day' => $data['day'],
        'start_time' => $data['start_time'],
        'end_time' => $data['end_time'],
    ]);
}
 // تعديل فترة
    public function updateAvailableTime($id, $data)
    {
        $doctor = Auth::user()->doctor;

        $schedule = Doctor_Schedules::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        // ✅ تحقق الوقت
        if ($data['start_time'] >= $data['end_time']) {
            throw new \Exception("وقت البداية لازم يكون قبل النهاية");
        }

        $schedule->update([
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return $schedule;
    }

    // حذف فترة
    public function deleteAvailableTime($id)
    {
        $doctor = Auth::user()->doctor;

        $schedule = Doctor_Schedules::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $schedule->delete();

        return ['message' => 'تم حذف الفترة'];
    }
    ////////////////////////////////////////////////////
// public function getDoctorPatients()
// {
//     $doctor = Auth::user()->doctor;

//     return Patient::whereHas('treatmentPlans.planItems.sessions', function ($q) use ($doctor) {
//         $q->where('doctor_id', $doctor->id)
//           ->where('status', 'completed'); // 🔥 فقط جلسات فعلية
//     })
//     ->distinct()
//     ->get();
// }

public function getDoctorPatients()
{
    $doctorId = Auth::user()->doctor->id;

    $patientIds = Treatment_session::where('doctor_id', $doctorId)
        ->where('status', 'completed')
        ->pluck('patient_id')
        ->unique();

    return Patient::whereIn('id', $patientIds)->get();
}

public function getTodayAppointments()
{
    $doctor = Auth::user()->doctor;

    return Appointment::where('doctor_id', $doctor->id)
        ->whereDate('appointment_date', now()->toDateString())
        ->with('patient')
        ->orderBy('appointment_date')
        ->get();
}

public function getUpcomingAppointmentsGrouped()
{
    $doctor = Auth::user()->doctor;

    return Appointment::where('doctor_id', $doctor->id)
        ->whereBetween('appointment_date', [
            now(),
            now()->addDays(10)
        ])
        ->with('patient')
        ->get()
        ->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->appointment_date)->toDateString();
        });
}
}