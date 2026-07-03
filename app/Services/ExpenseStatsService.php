<?php

namespace App\Services;

use App\Models\Doctor_Payment;
use App\Models\Payment;
use App\Models\SalaryPayment;
use Carbon\Carbon;

class ExpenseStatsService
{
    // إحصائيات شهر شهر لسنة كاملة — الثلاثة مع بعض
    public function getYearlyExpenseStats(?int $year = null): array
    {
        $year = $year ?? date('Y');

        // 1. رواتب الأطباء
        $doctorStats = Doctor_Payment::whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as month, SUM(amount_usd) as total_usd, SUM(amount_syp) as total_syp')
            ->groupBy('month')
            ->get()->keyBy('month');

        // 2. فواتير الموردين — عبر جدول payments المرتبط بـ invoices نوعه supplier
        $supplierStats = Payment::whereHas('invoice', fn ($q) => $q->where('type', 'supplier'))
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total_usd')
            ->groupBy('month')
            ->get()->keyBy('month');

        // 3. رواتب الموظفين الإداريين
        $salaryStats = SalaryPayment::whereYear('salary_month', $year)
            ->selectRaw('MONTH(salary_month) as month, SUM(amount_usd) as total_usd, SUM(amount_syp) as total_syp')
            ->groupBy('month')
            ->get()->keyBy('month');

        $months = collect(range(1, 12))->map(function ($m) use ($doctorStats, $supplierStats, $salaryStats) {
            $doctorUsd   = (float) ($doctorStats[$m]['total_usd']   ?? 0);
            $supplierUsd = (float) ($supplierStats[$m]['total_usd'] ?? 0);
            $salaryUsd   = (float) ($salaryStats[$m]['total_usd']   ?? 0);

            return [
                'month'               => $m,
                'doctor_payments_usd' => $doctorUsd,
                'supplier_payments_usd' => $supplierUsd,
                'salary_payments_usd' => $salaryUsd,
                'total_expenses_usd'  => $doctorUsd + $supplierUsd + $salaryUsd,
                'total_syp'           => (float) ($doctorStats[$m]['total_syp'] ?? 0)
                                       + (float) ($salaryStats[$m]['total_syp'] ?? 0),
            ];
        });

        return [
            'year'         => $year,
            'months'       => $months,
            'yearly_total' => [
                'doctor_payments_usd'   => $months->sum('doctor_payments_usd'),
                'supplier_payments_usd' => $months->sum('supplier_payments_usd'),
                'salary_payments_usd'   => $months->sum('salary_payments_usd'),
                'total_expenses_usd'    => $months->sum('total_expenses_usd'),
            ],
        ];
    }

    // إحصائيات شهر محدد بالتفصيل
    public function getMonthlyExpenseStats(int $month, ?int $year = null): array
    {
        $year = $year ?? date('Y');

        $doctorTotal   = (float) Doctor_Payment::whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)->sum('amount_usd');

        $supplierTotal = (float) Payment::whereHas('invoice', fn ($q) => $q->where('type', 'supplier'))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)->sum('amount');

        $salaryTotal   = (float) SalaryPayment::whereYear('salary_month', $year)
            ->whereMonth('salary_month', $month)->sum('amount_usd');

        return [
            'month'  => $month,
            'year'   => $year,
            'breakdown' => [
                'doctor_payments_usd'   => $doctorTotal,
                'supplier_payments_usd' => $supplierTotal,
                'salary_payments_usd'   => $salaryTotal,
            ],
            'total_expenses_usd' => $doctorTotal + $supplierTotal + $salaryTotal,
        ];
    }
}