<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PatientServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    protected $service;

    public function __construct(PatientServices $service)
    {
        $this->service = $service;
    }
//عرض المواعيد المتاحة لليوم 
   public function getAvailableSlotsForDays(Request $request)
{
    $data = $request->validate([
        'specialization_id' => 'required|exists:specializations,id',
        
    ]);

    $result = $this->service->getAvailableSlotsForDays(
        $data['specialization_id'],
    
    );

    return response()->json($result);
}
// حجز موعد
 public function bookAppointment(Request $request)
{
    
    $data = $request->validate([
        'specialization_id' => 'required|exists:specializations,id',
        'date' => 'required|date',
        'time' => 'required'
    ]);
     $patientId = Auth::user()->patient->id;

    $appointment = $this->service->bookAppointment(
       // auth()->id(),
       $patientId ,
        $data
    );

    return response()->json($appointment);
}
}
