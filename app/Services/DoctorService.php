<?php
namespace App\Services;
use App\Models\Doctor_Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;

use App\Events\MaterialRequestCreated;
use App\Models\Doctor;

class DoctorService
{
   public function addAvailableTime($data)
{
    $doctor = Auth::user()->doctor;


    if (!$doctor) {
        return [
            'success' => false,
            'message' => "هذا المستخدم ليس دكتور"
        ];
       // throw new \Exception("هذا المستخدم ليس دكتور");
    }

    // 🔥 منع التداخل
    $overlap = Doctor_Schedule::where('doctor_id', $doctor->id)
        ->where('day', $data['day'])
        ->where(function ($q) use ($data) {
            $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
              ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
        })
        ->exists();

    if ($overlap) {
        return [
            'success' => false,
            'message' => "الفترة تتداخل مع فترة موجودة"
        ];
       // throw new \Exception("الفترة تتداخل مع فترة موجودة");

    }
    if ($data['start_time'] >= $data['end_time']) {
            return [
                'success' => false,
                'message' => "وقت البداية لازم يكون قبل النهاية"
            ];
    //throw new \Exception("وقت البداية لازم يكون قبل النهاية");
}

    return Doctor_Schedule::create([
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

        $schedule = Doctor_Schedule::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        // ✅ تحقق الوقت
        if ($data['start_time'] >= $data['end_time']) {
            return [
                'success' => false,
                'message' => "وقت البداية لازم يكون قبل النهاية"
            ];
           // throw new \Exception("وقت البداية لازم يكون قبل النهاية");
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

        $schedule = Doctor_Schedule::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $schedule->delete();

        return ['message' => 'تم حذف الفترة'];
    }
    //عرض الاوقات المتاحة
    public function getMyAvailableTimes()
{
    $doctor = Auth::user()->doctor;

    if (!$doctor) {
        return [
            'success' => false,
            'message' => "هذا المستخدم ليس دكتور"
        ];
    }
    return Doctor_Schedule::where('doctor_id', $doctor->id)
    ->orderByRaw("FIELD(day, 'sun', 'mon', 'tues', 'wed', 'thy', 'fri', 'sat')")
    ->orderBy('start_time', 'asc')
    ->get();
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
//عرض مرضى الدكتور
public function getDoctorPatients()
{
    $doctorId = Auth::user()->doctor->id;

    return Patient::where(function ($query) use ($doctorId) {
        $query->whereHas('treatmentPlans', function ($planQuery) use ($doctorId) {
            $planQuery->where('doctor_id', $doctorId)
                ->whereHas('items.sessions', function ($sessionQuery) {
                    $sessionQuery->where('status', 'completed');
                });
        })
        ->orWhereHas('appointments', function ($appointmentQuery) use ($doctorId) {
            $appointmentQuery->where('doctor_id', $doctorId)
                             ->where('status', 'confirmed');
        });
    })
    ->with(['user:id,name,phone_number']) // جلب الاسم ورقم الهاتف فقط من جدول الـ Users
    ->get(['id', 'user_id'])        // جلب الـ ID والـ user_id فقط من جدول المرضى
    ->map(function ($patient) {
        return [
            'id'    => $patient->id,
            'name'  => $patient->user->name,
            'phone' => $patient->user->phone_number, // تأكدي أن اسم الحقل في جدول users هو phone
        ];
    });
}

// public function getDoctorPatients()
// {
//     $doctorId = Auth::user()->doctor->id;

//     return Patient::whereHas('treatmentPlans', function ($planQuery) use ($doctorId) {
//         $planQuery->where('doctor_id', $doctorId)
//             ->whereHas('items.sessions', function ($sessionQuery) {
//                 $sessionQuery->where('status', 'completed');
//             });
//     })
//         ->distinct()
//         ->get();
// }

public function getTodayAppointments()
{
    $doctor = Auth::user()->doctor;

    return Appointment::where('doctor_id', $doctor->id)
        ->whereDate('appointment_date', now()->toDateString())
        ->with('patient.user:id,name') // جلب بيانات المريض
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

public function searchPatientsByName(string $name)
{
    $user = Auth::user();
   // $doctor = $user?->doctor;

    $query = Patient::with('user')
        ->whereHas('user', function ($q) use ($name) {
            $q->where('name', 'like', "%{$name}%");
        });
 /*   if ($doctor) {
        $query->whereHas('treatmentPlans', function ($planQuery) use ($doctor) {
            $planQuery->where('doctor_id', $doctor->id);
        });
    }*/
    return $query
        ->distinct()
        ->get()
        ->map(function (Patient $patient) {
            return [
                'name' => $patient->user?->name,
                'phone_number' => $patient->user?->phone_number,
            ];
        })
        ->values();
}
// طلب مواد
public function createRequest(array $data)
{
    return DB::transaction(function () use ($data) {

        // $request = MaterialRequest::create([
        //     'doctor_id' => Auth::id(),
        //     'notes' => $data['notes'] ?? null,
        // ]);
         $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();

        $request = MaterialRequest::create([
            'doctor_id' => $doctor->id, // ✅ هون الصح
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {

            MaterialRequestItem::create([
                'material_request_id' => $request->id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
            ]);
        }

       
 event(new MaterialRequestCreated($request));


        return $request->load('items');
    });
}

}