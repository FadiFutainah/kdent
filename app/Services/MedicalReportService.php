<?php

namespace App\Services;

use App\Models\Medical_Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicalReportService
{
    public function createReport(array $data, array $files = [])
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            throw new \Exception('هذا المستخدم ليس دكتور');
        }

        $attachments = $this->storeAttachments($files, $doctor->id, (int) $data['patient_id']);

        return Medical_Report::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctor->id,
            'content' => $data['content'],
            'attachments' => $attachments ?: null,
        ]);
    }

    public function getReportsForCurrentUser()
    {
        $scope = $this->resolveAccessScope();

        return Medical_Report::with(['patient.user', 'doctor.user'])
            ->where($scope['column'], $scope['id'])
            ->orderByDesc('report_date')
            ->get()
            ->map(function (Medical_Report $report) {
                return [
                    'id' => $report->id,
                    'patient_name' => $report->patient?->user?->name,
                    'doctor_name' => $report->doctor?->user?->name,
                    'report_date' => optional($report->report_date)->toDateTimeString(),
                ];
            })
            ->values();
    }

    public function getReportDetails(int $reportId)
    {
        $scope = $this->resolveAccessScope();

        $report = Medical_Report::with(['patient.user', 'doctor.user'])
            ->where($scope['column'], $scope['id'])
            ->where('id', $reportId)
            ->firstOrFail();

        return [
            'id' => $report->id,
            'patient_id' => $report->patient_id,
            'patient_name' => $report->patient?->user?->name,
            'doctor_id' => $report->doctor_id,
            'doctor_name' => $report->doctor?->user?->name,
            'report_date' => optional($report->report_date)->toDateTimeString(),
            'content' => $report->content,
            'attachments' => $report->attachments,
        ];
    }

    private function storeAttachments(array $files, int $doctorId, int $patientId): array
    {
        $attachments = [];
        $dir = "medical_reports/{$doctorId}/{$patientId}";

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $mime = (string) ($file->getClientMimeType() ?: $file->getMimeType());
            $extension = $file->getClientOriginalExtension();
            $name = (string) Str::uuid();
            $filename = $extension ? "{$name}.{$extension}" : $name;

            $path = $file->storeAs($dir, $filename, 'public');

            $attachments[] = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $mime ?: null,
                'file_size' => $file->getSize(),
                'file_type' => str_starts_with($mime, 'image/') ? 'image' : 'file',
            ];
        }

        return $attachments;
    }

    private function resolveAccessScope(): array
    {
        $user = Auth::user();
        $doctor = $user?->doctor;
        $patient = $user?->patient;

        if ($doctor) {
            return ['column' => 'doctor_id', 'id' => $doctor->id];
        }

        if ($patient) {
            return ['column' => 'patient_id', 'id' => $patient->id];
        }

        throw new \Exception('لا تملك صلاحية الوصول للتقارير');
    }
}
