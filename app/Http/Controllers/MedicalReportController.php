<?php

namespace App\Http\Controllers;

use App\Services\MedicalReportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ArPHP\I18N\Arabic; 

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

    public function index(int $patientId)
    {
        return response()->json(
            $this->service->getReportsForCurrentUser($patientId)
        );
    }

    public function show(int $reportId)
    {
        return response()->json(
            $this->service->getReportDetails($reportId)
        );
    }

    
public function downloadPdf(int $reportId)
{
    $report = $this->service->getReportForPdf($reportId);

    $mpdf = new \Mpdf\Mpdf([
        'mode'        => 'utf-8',
        'format'      => 'A4',
        'orientation' => 'P',
        'directionality' => 'rtl',
    ]);

    $html = view('pdf.medical-report', compact('report'))->render();

    $mpdf->WriteHTML($html);

    return response($mpdf->Output('', 'S'), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "attachment; filename=medical-report-{$report->id}.pdf",
    ]);
}
    public function update(Request $request, int $reportId)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $files = $request->file('attachments', []);

        return response()->json(
            $this->service->updateReport(
                $reportId,
                $data,
                $files
            )
        );
    }
    public function destroy(int $reportId)
    {
        $this->service->deleteReport($reportId);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقرير بنجاح'
        ]);
    }




}