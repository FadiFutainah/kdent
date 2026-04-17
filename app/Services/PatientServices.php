<?php
namespace App\Services;
use App\Models\Doctor_Schedules;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PatientServices
{
 
public function getAvailableSlotsForDays($specializationId, $shift = null)
{
    $doctor = Doctor::where('specialization_id', $specializationId)
        ->where('is_active', true)
        ->first();

    if (!$doctor) {
        return [];
    }
    $daysToCheck = 10;
    $result = [];

    for ($i = 0; $i < $daysToCheck; $i++) {

        $date = Carbon::today()->addDays($i);
        $day = strtolower($date->format('D'));

        $query = Doctor_Schedules::where('doctor_id', $doctor->id)
            ->where('day', $day);

        if ($shift) {
            $query->where('shift', $shift);
        }

        $schedules = $query->get();

        $slots = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);

            while ($start < $end) {
                $slots[] = $start->format('H:i');
                $start->addMinutes(30);
            }
        }

        // 🔥 حذف الأوقات الماضية لليوم الحالي
       if ($i == 0) {
    $now = Carbon::now();

    $slots = array_filter($slots, function ($slot) use ($now, $date) {
        $slotDateTime = Carbon::parse($date->toDateString() . ' ' . $slot);
        return $slotDateTime->gt($now);
    });
}

        // جلب المحجوز
        $booked = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->pluck('appointment_date')
            ->map(fn($t) => Carbon::parse($t)->format('H:i'))
            ->toArray();

        // حذف المحجوز
        $available = array_values(array_diff($slots, $booked));

        // 👉 حتى لو فاضي خليه موجود (ليعرض "لا يوجد")
        $result[] = [
            'date' => $date->toDateString(),
            'day' => $day,
            'slots' => $available
        ];
    }

    return $result;
}


  public function bookAppointment($userId, $data)
{
    // 1️⃣ نجيب المريض
   
   $patient = Auth::user()->patient;
    // 2️⃣ نجيب الدكتور
    $doctor = Doctor::where('specialization_id', $data['specialization_id'])
        ->where('is_active', true)
        ->firstOrFail();

    // 3️⃣ نركب datetime
    $appointmentDateTime = Carbon::parse($data['date'] . ' ' . $data['time']);

    // ❌ منع الحجز بالماضي
    if ($appointmentDateTime->lt(Carbon::now())) {
        throw new \Exception("لا يمكن حجز موعد في الماضي");
    }

    // 4️⃣ تأكد الوقت ضمن الدوام
    $day = strtolower($appointmentDateTime->format('D'));

    $hasSchedule = Doctor_Schedules::where('doctor_id', $doctor->id)
        ->where('day', $day)
        ->exists();

    if (!$hasSchedule) {
        throw new \Exception("الدكتور لا يعمل بهذا اليوم");
    }

    // 5️⃣ تأكد الوقت مو محجوز
    $exists = Appointment::where('doctor_id', $doctor->id)
        ->where('appointment_date', $appointmentDateTime)
        ->exists();

    if ($exists) {
        throw new \Exception("هذا الموعد محجوز مسبقاً");
    }

    // 6️⃣ إنشاء الموعد
    return Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => $appointmentDateTime,
        'status' => 'scheduled'
    ]);
}

}