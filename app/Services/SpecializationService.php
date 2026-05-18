<?php
namespace App\Services;

use App\Models\Specialization;
use App\Models\Doctor;

class SpecializationService
{
    /**
     * جلب كل الاختصاصات (للواجهة الرئيسية)
     */
    public function getAllSpecializations()
    {
        return Specialization::select('id', 'name')->get();
    }

    /**
     * جلب تفاصيل اختصاص + الدكتور المتاح
     */
    public function getSpecializationDetails($id)
    {
        $specialization = Specialization::findOrFail($id);

        // جلب الدكتور مع اليوزر
        $doctors = Doctor::with('user')
            ->where('specialization_id', $id)
            ->where('is_active', true)
               ->get();

        return [
            'id' => $specialization->id,
            'name' => $specialization->name,
            'description' => $specialization->description,

            // 'doctor' => $doctor ? [
            //     'id' => $doctor->id,
            //     'name' => $doctor->user->name,
            // ] : null
            'doctors' => $doctors->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                ];
            })
        ];
    }
    //عرض اطباء اختصاص محدد
    public function getDoctorsBySpecialization($id)
    {
         $specialization = Specialization::findOrFail($id);
        $doctors = Doctor::with('user')
            ->where('specialization_id', $id)
            ->where('is_active', true)
            ->get();
        return $doctors->map(function ($doctor) 
        {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                ];
            });
            
    }

    /**
     * جلب الدكتور المتاح لاختصاص (إذا بدك تستخدمها لحالها)
     */
    public function getActiveDoctor($specializationId)
    {
        $doctor = Doctor::with('user')
            ->where('specialization_id', $specializationId)
            ->where('is_active', true)
            ->first();

        return $doctor ? [
            'id' => $doctor->id,
            'name' => $doctor->user->name,
        ] : null;
    }

    /**
     * (اختياري 🔥) جلب كل الاختصاصات مع الدكتور
     */
    // public function getSpecializationsWithDoctors()
    // {
    //     $specializations = Specialization::all();

    //     return $specializations->map(function ($spec) {

    //         $doctor = Doctor::with('user')
    //             ->where('specialization_id', $spec->id)
    //             ->where('is_active', true)
    //             ->first();

    //         return [
    //             'id' => $spec->id,
    //             'name' => $spec->name,
    //             'doctor' => $doctor ? [
    //                 'id' => $doctor->id,
    //                 'name' => $doctor->user->name,
    //             ] : null
    //         ];
    //     });
    // }

    public function getSpecializationsWithDoctors()
    {
        return Specialization::with(['doctors.user'])->get()->map(function ($spec) {

            return [
                'id' => $spec->id,
                'name' => $spec->name,

                'doctors' => $spec->doctors
                    ->where('is_active', true)
                    ->map(function ($doctor) {
                        return [
                            'id' => $doctor->id,
                            'name' => $doctor->user->name,
                        ];
                    })->values()
            ];
        });
    }
    
    //  public function getSchedules($doctorId, $shift = null)
    // {
    //     $query = DoctorSchedule::where('doctor_id', $doctorId);

    //     if ($shift) {
    //         $query->where('shift', $shift);
    //     }

    //     return $query->get();
    // }
    
}
