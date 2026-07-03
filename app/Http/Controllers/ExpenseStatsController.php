<?php

namespace App\Http\Controllers;

use App\Services\ExpenseStatsService;
use Illuminate\Http\Request;

class ExpenseStatsController extends Controller
{
    public function __construct(protected ExpenseStatsService $service) {}

    public function yearly(Request $request)
    {
        $data = $request->validate(['year' => 'nullable|integer|digits:4']);
        return response()->json($this->service->getYearlyExpenseStats($data['year'] ?? null));
    }

    public function monthly(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'nullable|integer|digits:4',
        ]);
        return response()->json($this->service->getMonthlyExpenseStats($data['month'], $data['year'] ?? null));
    }
}