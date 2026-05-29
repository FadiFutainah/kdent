<?php

namespace App\Http\Controllers;

use App\Services\MedicalReportService;
use Illuminate\Http\Request;

class MedicalReportController extends Controller
{
    public function __construct(private MedicalReportService $service)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'content' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $files = $request->file('attachments', []);

        return response()->json(
            $this->service->createReport($data, $files)
        );
    }

    public function index()
    {
        return response()->json(
            $this->service->getReportsForCurrentUser()
        );
    }

    public function show(int $reportId)
    {
        return response()->json(
            $this->service->getReportDetails($reportId)
        );
    }
}
