<?php

namespace App\Http\Controllers;

use App\Services\MedicalRecordService;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function __construct(private MedicalRecordService $service)
    {
    }

    public function show(int $patientId)
    {
        return response()->json(
            $this->service->getMedicalRecord($patientId)
        );
    }

    public function update(Request $request, int $patientId)
    {
        $data = $request->validate([
            'name' => 'sometimes|nullable|string',
            'phone_number' => 'sometimes|nullable|string',
            'gender' => 'sometimes|nullable|in:male,female',
            'date_of_birth' => 'sometimes|nullable|date',
            'address' => 'sometimes|nullable|string',
            'occupation' => 'nullable|string',
            'file_open_date' => 'sometimes|nullable|date',
            'medical_history_heart_disease' => 'nullable|boolean',
            'medical_history_diabetes' => 'nullable|boolean',
            'medical_history_blood_pressure' => 'nullable|boolean',
            'medical_history_asthma' => 'nullable|boolean',
            'medical_history_allergies_meds' => 'nullable|boolean',
            'medical_history_liver_disease' => 'nullable|boolean',
            'medical_history_kidney_disease' => 'nullable|boolean',
            'medical_history_blood_disorders' => 'nullable|boolean',
            'medical_history_pregnancy' => 'nullable|boolean',
            'current_medications' => 'nullable|string',
            'known_allergies' => 'nullable|string',
        ]);

        return response()->json(
            $this->service->updateMedicalRecord($patientId, $data)
        );
    }
}
