<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class MedicalRecordService
{
    public function getMedicalRecord(int $patientId)
    {
        $scope = $this->resolveAccessScope();

        if ($scope['role'] === 'patient' && $scope['id'] !== $patientId) {
            throw new \Exception('لا تملك صلاحية الوصول لهذا السجل');
        }

        $patient = Patient::with('user')->findOrFail($patientId);

        return $this->formatRecord($patient);
    }

    public function updateMedicalRecord(int $patientId, array $data)
    {
        $scope = $this->resolveAccessScope();

        if ($scope['role'] !== 'doctor') {
            throw new \Exception('لا تملك صلاحية تعديل السجل');
        }

        $patient = Patient::with('user')->findOrFail($patientId);

        $updates = [];
        $booleanFields = [
            'medical_history_heart_disease',
            'medical_history_diabetes',
            'medical_history_blood_pressure',
            'medical_history_asthma',
            'medical_history_allergies_meds',
            'medical_history_liver_disease',
            'medical_history_kidney_disease',
            'medical_history_blood_disorders',
            'medical_history_pregnancy',
        ];

        foreach ($this->recordFields() as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (in_array($field, $booleanFields, true) && is_null($data[$field])) {
                continue;
            }

            $updates[$field] = $data[$field];
        }

        if ($updates) {
            $patient->update($updates);
        }

        return $this->formatRecord($patient->fresh('user'));
    }

    private function formatRecord(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'user_id' => $patient->user_id,
            'name' => $patient->user?->name,
            'phone_number' => $patient->user?->phone_number,
            'gender' => $patient->gender,
            'address' => $patient->address,
            'occupation' => $patient->occupation,
            'file_open_date' => optional($patient->file_open_date)->toDateString(),
            'medical_history_heart_disease' => $patient->medical_history_heart_disease,
            'medical_history_diabetes' => $patient->medical_history_diabetes,
            'medical_history_blood_pressure' => $patient->medical_history_blood_pressure,
            'medical_history_asthma' => $patient->medical_history_asthma,
            'medical_history_allergies_meds' => $patient->medical_history_allergies_meds,
            'medical_history_liver_disease' => $patient->medical_history_liver_disease,
            'medical_history_kidney_disease' => $patient->medical_history_kidney_disease,
            'medical_history_blood_disorders' => $patient->medical_history_blood_disorders,
            'medical_history_pregnancy' => $patient->medical_history_pregnancy,
            'current_medications' => $patient->current_medications,
            'known_allergies' => $patient->known_allergies,
            'created_at' => optional($patient->created_at)->toDateTimeString(),
            'updated_at' => optional($patient->updated_at)->toDateTimeString(),
        ];
    }

    private function recordFields(): array
    {
        return [
            'gender',
            'address',
            'occupation',
            'file_open_date',
            'medical_history_heart_disease',
            'medical_history_diabetes',
            'medical_history_blood_pressure',
            'medical_history_asthma',
            'medical_history_allergies_meds',
            'medical_history_liver_disease',
            'medical_history_kidney_disease',
            'medical_history_blood_disorders',
            'medical_history_pregnancy',
            'current_medications',
            'known_allergies',
        ];
    }

    private function resolveAccessScope(): array
    {
        $user = Auth::user();
        $doctor = $user?->doctor;
        $patient = $user?->patient;

        if ($doctor) {
            return ['role' => 'doctor', 'id' => $doctor->id];
        }

        if ($patient) {
            return ['role' => 'patient', 'id' => $patient->id];
        }

        throw new \Exception('لا تملك صلاحية الوصول للسجل');
    }
}
