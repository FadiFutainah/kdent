<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class MedicalRecordService
{
    public function getMedicalRecord(int $patientId)
    {
        $scope = $this->resolveAccessScope();

        if ($scope['role'] === 'patient' && $scope['patient_id'] !== $patientId) {
            throw new \Exception('غير مصرح');
        }

        if ($scope['role'] === 'doctor') {
            $exists = \App\Models\Treatment_Plan::where('doctor_id', $scope['doctor_id'])
                ->where('patient_id', $patientId)
                ->exists();

            if (!$exists) {
                throw new \Exception('هذا المريض ليس من مرضاك');
            }
        }

        $patient = Patient::with('user')->findOrFail($patientId);

        return $this->formatRecord($patient);
    }

    /*public function updateMedicalRecord(int $patientId, array $data)
    {
        $scope = $this->resolveAccessScope();

        if ($scope['role'] !== 'doctor') {
            throw new \Exception('لا تملك صلاحية تعديل السجل');
        }

        $patient = Patient::with('user')->findOrFail($patientId);

        $patientUpdates = [];
        $userUpdates = [];

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

        $userUpdates = array_filter([
            'name' => $data['name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'file_open_date' => $data['file_open_date'] ?? null,
            'current_medications' => $data['current_medications'] ?? null,
            'known_allergies' => $data['known_allergies'] ?? null,
        ], fn ($value) => !is_null($value) && $value !== '');

        foreach ($this->recordFields() as $field) {

        if (!array_key_exists($field, $data)) {
            continue;
        }

        if (
            in_array($field, $booleanFields, true)
            && is_null($data[$field])
        ) {
            continue;
        }

        $patientUpdates[$field] = $data[$field];
    } 

        if (!empty($userUpdates)) {
            $patient->user()->update($userUpdates);
        }

        if (!empty($patientUpdates)) {
            $patient->update($patientUpdates);
        }

        return $this->formatRecord($patient->fresh('user'));
    }*/

    public function updateMedicalRecord(int $patientId, array $data)
    {
        $scope = $this->resolveAccessScope();

        if (
            $scope['role'] !== 'doctor' &&
            $scope['role'] !== 'secretary'
        ) {
            throw new \Exception('لا تملك صلاحية تعديل السجل');
        }

        $patient = Patient::with('user')->findOrFail($patientId);

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

        // حقول جدول users
        $userUpdates = array_filter([
            'name' => $data['name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'email' => $data['email'] ?? null,
        ], fn ($value) => !is_null($value) && $value !== '');

        // حقول جدول patients
        $patientUpdates = [];

        foreach ($this->recordFields() as $field) {

            if (!array_key_exists($field, $data)) {
                continue;
            }

            // لا تحدث القيم المنطقية إذا كانت null
            if (
                in_array($field, $booleanFields, true)
                && is_null($data[$field])
            ) {
                continue;
            }

            $patientUpdates[$field] = $data[$field];
        }

        if (!empty($userUpdates)) {
            $patient->user()->update($userUpdates);
        }

        if (!empty($patientUpdates)) {
            $patient->update($patientUpdates);
        }

        return $this->formatRecord(
            $patient->fresh('user')
        );
    }    

    private function formatRecord(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'user_id' => $patient->user_id,
            'name' => $patient->user?->name,
            'phone_number' => $patient->user?->phone_number,
            'date_of_birth' => $patient->user?->date_of_birth,
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

        if ($user->hasRole('secretary')) {
            return ['role' => 'secretary'];
        }

        if ($user->hasRole('doctor')) {
            return ['role' => 'doctor', 'doctor_id' => $user->doctor->id];
        }

        if ($user->hasRole('patient')) {
            return ['role' => 'patient', 'patient_id' => $user->patient->id];
        }

        throw new \Exception('غير مصرح');
    }
}
