<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmployeeSalaryService;
class EmployeeSalaryController extends Controller
{
     protected $service;

    public function __construct(EmployeeSalaryService $service)
    {
        $this->service = $service;
    }

    public function setBaseSalary(Request $request, int $userId)
    {
        $data = $request->validate([
            'base_salary_usd' => 'required|numeric|min:0',
        ]);

        return response()->json(
            $this->service->setBaseSalary($userId, $data['base_salary_usd'])
        );
    }

    public function pay(Request $request, int $userId)
    {
        $data = $request->validate([
            'amount_usd'   => 'nullable|numeric|gt:0',
            'amount_syp'   => 'nullable|numeric|gt:0',
            'salary_month' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        try {
            return response()->json(
                $this->service->paySalary($userId, $data)
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function history(int $userId)
    {
        return response()->json(
            $this->service->getEmployeeSalaryHistory($userId)
        );
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->service->getAllSalaryPayments($request->query('month'))
        );
    }

    public function addAdjustment(Request $request, int $userId)
{
    $data = $request->validate([
        'type'         => 'required|in:bonus,deduction',
        'amount_usd'   => 'required|numeric|gt:0',
        'reason'       => 'nullable|string',
        'salary_month' => 'nullable|date',
    ]);

    return response()->json(
        $this->service->addAdjustment($userId, $data)
    );
}

public function pendingAdjustments(Request $request, int $userId)
{
    return response()->json(
        $this->service->getPendingAdjustments($userId, $request->query('month'))
    );
}

public function deleteAdjustment(int $adjustmentId)
{
    try {
        return response()->json(
            $this->service->deleteAdjustment($adjustmentId)
        );
    } catch (\DomainException $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}
// عرض الراتب الأساسي لموظف
public function getBaseSalary(int $userId)
{
    return response()->json(
        $this->service->getBaseSalary($userId)
    );
}

// عرض الأشهر غير المدفوعة لموظف
public function unpaidMonths(int $userId)
{
    return response()->json(
        $this->service->getUnpaidMonths($userId)
    );
}
}
