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

    public function getReportsForCurrentUser(int $patientId = null)
    {
        $scope = $this->resolveAccessScope();

        $query = Medical_Report::with(['patient.user', 'doctor.user']);

        if ($scope['role'] === 'doctor') {
            $query->where('doctor_id', $scope['doctor_id']);
            // إذا حدد مريض معين
            if ($patientId) {
                $query->where('patient_id', $patientId);
            }
        }

        if ($scope['role'] === 'patient') {
            // المريض يشوف تقاريره فقط بغض النظر عن $patientId
            $query->where('patient_id', $scope['patient_id']);
        }

        if ($scope['role'] === 'secretary' && $patientId) {
            $query->where('patient_id', $patientId);
        }

        return $query->orderByDesc('report_date')
            ->get()
            ->map(fn($r) => [
                'id'           => $r->id,
                'patient_name' => $r->patient?->user?->name,
                'doctor_name'  => $r->doctor?->user?->name,
                'report_date'  => $r->report_date,
            ]);
    }

    public function getReportDetails(int $reportId)
    {
        $scope = $this->resolveAccessScope();

        $query = Medical_Report::with(['patient.user', 'doctor.user'])
            ->where('id', $reportId);

        if ($scope['role'] === 'doctor') {
            $query->where('doctor_id', $scope['doctor_id']);
        }

        if ($scope['role'] === 'patient') {
            $query->where('patient_id', $scope['patient_id']);
        }

        $report = $query->firstOrFail();

        return [
            'id'            => $report->id,
            'patient_name'  => $report->patient?->user?->name,
            'doctor_name'   => $report->doctor?->user?->name,
            'content'       => $report->content,
            'attachments'   => $report->attachments,
            'report_date'   => $report->report_date,
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

        if ($user->hasRole('secretary')) {
            return ['role' => 'secretary'];
        }

        if ($user->hasRole('doctor')) {
            return [
                'role' => 'doctor',
                'doctor_id' => $user->doctor->id
            ];
        }

        if ($user->hasRole('patient')) {
            return [
                'role' => 'patient',
                'patient_id' => $user->patient->id
            ];
        }

        throw new \Exception('غير مصرح');
    }

    public function getReportForPdf(int $reportId): Medical_Report
    {
        $scope = $this->resolveAccessScope();

        $query = Medical_Report::with([
            'patient.user',
            'doctor.user'
        ])->where('id', $reportId);

        if ($scope['role'] === 'doctor') {
            $query->where('doctor_id', $scope['doctor_id']);
        }

        if ($scope['role'] === 'patient') {
            $query->where('patient_id', $scope['patient_id']);
        }

        return $query->firstOrFail();
    }
}
