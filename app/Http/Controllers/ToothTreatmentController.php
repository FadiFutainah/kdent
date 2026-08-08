<?php

namespace App\Http\Controllers;

use App\Models\ToothTreatment;
use App\Services\ToothTreatmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToothTreatmentController extends Controller
{
    public function __construct(private ToothTreatmentService $service)
    {
    }

    // GET /patients/{patientId}/dental-chart
    public function index(int $patientId)
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->service->getChartForPatient($patientId),
        ]);
    }

    // POST /patients/{patientId}/dental-chart
    public function store(Request $request, int $patientId)
    {
        $data = $this->validateData($request, forUpdate: false);

        $tooth = $this->service->addTooth($patientId, $data);

        return response()->json(['status' => 'success', 'data' => $tooth]);
    }

    // PUT /patients/{patientId}/dental-chart/{recordId}
    public function update(Request $request, int $patientId, int $recordId)
    {
        $data = $this->validateData($request, forUpdate: true);

        $tooth = $this->service->updateTooth($patientId, $recordId, $data);

        return response()->json(['status' => 'success', 'data' => $tooth]);
    }

    // DELETE /patients/{patientId}/dental-chart/{recordId}
    public function destroy(int $patientId, int $recordId)
    {
        $this->service->deleteTooth($patientId, $recordId);

        return response()->json(['status' => 'success', 'message' => 'تم حذف الإجراء بنجاح']);
    }

    /**
     * قواعد التحقق الشكلي (أنواع البيانات، القيم المسموحة) بس
     * التحقق "الطبي" (تطابق نوع المعالجة مع نوع السن) صار مسؤولية الـ Service
     */
    private function validateData(Request $request, bool $forUpdate): array
    {
        $rules = [
            'status' => 'required|in:Initial,Pending,Done',
            'treatment_type' => 'required|string',
            'selected_surfaces' => 'nullable|array',
            'selected_surfaces.*' => Rule::in(ToothTreatment::SURFACES),
            'notes' => 'nullable|string',
        ];

        // رقم السن مطلوب بس بالإضافة (create) - بالتعديل السن ثابت وما بينبعت
        if (!$forUpdate) {
            $rules['tooth_number'] = [
                'required',
                'integer',
                Rule::in(ToothTreatment::validToothNumbers()),
            ];
        }

        return $request->validate($rules);
    }
}