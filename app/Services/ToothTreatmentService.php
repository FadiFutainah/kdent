<?php

namespace App\Services;

use App\Models\ToothTreatment;
use App\Models\Treatment_Plan;
use Illuminate\Support\Facades\Auth;

class ToothTreatmentService
{
    /**
     * جلب خارطة الأسنان كاملة لمريض معين
     * كل سن ممكن يكون إلو أكتر من سجل (تاريخ معالجات) → بنرجع "current" (آخر واحد) + "history" (الباقي)
     */
    public function getChartForPatient(int $patientId)
    {
        $this->authorizeAccess($patientId);

        $records = ToothTreatment::where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get();

        return $records
            ->groupBy('tooth_number')
            ->map(function ($group, $toothNumber) {
                $sorted = $group->values(); // أحدث سجل أولاً

                return [
                    'tooth_number' => (int) $toothNumber,
                    'current' => $this->formatTooth($sorted->first()),
                    'history' => $sorted->skip(1)->map(fn ($t) => $this->formatTooth($t))->values(),
                ];
            })
            ->values();
    }

    /**
     * إضافة إجراء جديد لسن (دايماً سجل جديد - ما في تحديث هون)
     */
    public function addTooth(int $patientId, array $data)
    {
        $scope = $this->authorizeAccess($patientId, requireDoctor: true);

        $this->validateTreatmentTypeForTooth((int) $data['tooth_number'], $data['treatment_type']);

        $tooth = ToothTreatment::create([
            'patient_id' => $patientId,
            'doctor_id' => $scope['doctor_id'],
            'tooth_number' => $data['tooth_number'],
            'status' => $data['status'],
            'treatment_type' => $data['treatment_type'],
            'selected_surfaces' => $data['selected_surfaces'] ?? [],
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->formatTooth($tooth);
    }

    /**
     * تعديل سجل إجراء موجود مسبقاً (بالـ id تبعو) - أي دكتور من مرضى العيادة يقدر يعدل
     */
    public function updateTooth(int $patientId, int $recordId, array $data)
    {
        $this->authorizeAccess($patientId, requireDoctor: true);

        $tooth = ToothTreatment::where('patient_id', $patientId)
            ->where('id', $recordId)
            ->firstOrFail();

        // السن (tooth_number) ثابت وما بيتغير بالتعديل - منجيبه من السجل نفسه
        $this->validateTreatmentTypeForTooth($tooth->tooth_number, $data['treatment_type']);

        $tooth->update([
            'status' => $data['status'],
            'treatment_type' => $data['treatment_type'],
            'selected_surfaces' => $data['selected_surfaces'] ?? [],
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->formatTooth($tooth->fresh());
    }

    /**
     * حذف سجل إجراء محدد (بالـ id) - مسموح فقط للدكتور اللي سجّله أصلاً
     */
    public function deleteTooth(int $patientId, int $recordId): void
    {
        $scope = $this->authorizeAccess($patientId, requireDoctor: true);

        $tooth = ToothTreatment::where('patient_id', $patientId)
            ->where('id', $recordId)
            ->firstOrFail();

        if ($tooth->doctor_id !== $scope['doctor_id']) {
            throw new \Exception('لا يمكنك حذف إجراء سجّله دكتور آخر');
        }

        $tooth->delete();
    }

    /**
     * التحقق إنو نوع المعالجة يطابق نوع السن (لبني/دائم)
     */
    private function validateTreatmentTypeForTooth(int $toothNumber, string $treatmentType): void
    {
        $allowedTypes = ToothTreatment::allowedTreatmentTypesFor($toothNumber);

        if (!in_array($treatmentType, $allowedTypes, true)) {
            throw new \Exception('نوع المعالجة غير مناسب لهذا السن');
        }
    }

    private function formatTooth(ToothTreatment $t): array
    {
        return [
            'id' => $t->id,
            'tooth_number' => $t->tooth_number,
            'status' => $t->status,
            'treatment_type' => $t->treatment_type,
            'selected_surfaces' => $t->selected_surfaces,
            'notes' => $t->notes,
            'doctor_id' => $t->doctor_id,
            'created_at' => optional($t->created_at)->toDateTimeString(),
            'updated_at' => optional($t->updated_at)->toDateTimeString(),
        ];
    }

    /**
     * التحقق من صلاحية الوصول لخارطة هاد المريض
     */
    private function authorizeAccess(int $patientId, bool $requireDoctor = false): array
    {
        $scope = $this->resolveAccessScope();

        if ($requireDoctor && $scope['role'] !== 'doctor') {
            throw new \Exception('غير مصرح، هاد الإجراء يحتاج صلاحية دكتور');
        }

        if ($scope['role'] === 'patient' && $scope['patient_id'] !== $patientId) {
            throw new \Exception('غير مصرح');
        }

        if ($scope['role'] === 'doctor') {
            $exists = Treatment_Plan::where('doctor_id', $scope['doctor_id'])
                ->where('patient_id', $patientId)
                ->exists();

            if (!$exists) {
                throw new \Exception('هذا المريض ليس من مرضاك');
            }
        }

        return $scope;
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