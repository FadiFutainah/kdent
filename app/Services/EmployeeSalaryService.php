<?php

namespace App\Services;

use App\Models\User;
use App\Models\SalaryPayment;
use App\Models\SalaryAdjustment;
use App\Models\Exchange_Rate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSalaryService
{
    protected array $salariedRoles = ['admin', 'secretary', 'accountant', 'storekeeper'];

    public function setBaseSalary(int $userId, float $amountUsd): array
    {
        $user = User::whereHas('roles', fn ($q) => $q->whereIn('name', $this->salariedRoles))
            ->findOrFail($userId);

        $user->update(['base_salary_usd' => $amountUsd]);

        return [
            'message'         => 'تم تحديث الراتب الأساسي بنجاح',
            'user_id'         => $user->id,
            'base_salary_usd' => $user->base_salary_usd,
        ];
    }

    // دالة مساعدة خاصة — ضيفيها فوق setBaseSalary
private function parseMonth(mixed $input): array
{
    // بتاخد أي input وبترجع ['str' => '2026-06-01', 'year' => 2026, 'month' => 6]
    // بدون أي تحويل timezone لأننا عم نشتغل على string بس
    $str = Carbon::parse($input)->format('Y-m') . '-01';
    return [
        'str'   => $str,
        'year'  => (int) substr($str, 0, 4),
        'month' => (int) substr($str, 5, 2),
    ];
}

   public function addAdjustment(int $userId, array $data): array
{
    $employee = User::whereHas('roles', fn ($q) => $q->whereIn('name', $this->salariedRoles))
        ->findOrFail($userId);

    $month = $this->parseMonth($data['salary_month'] ?? now()); // ← هون
   // 🔒 تحقق: هل راتب هاد الشهر مدفوع أصلاً؟
    $alreadyPaid = SalaryPayment::where('user_id', $employee->id)
        ->whereYear('salary_month', $month['year'])
        ->whereMonth('salary_month', $month['month'])
        ->exists();

    if ($alreadyPaid) {
        return [
            'success' => false,
            'message' => 'تم دفع راتب شهر ' . $month['str'] . ' لهذا الموظف مسبقاً، لا يمكن إضافة مكافأة أو خصم عليه الآن.',
        ];
    }

    $adjustment = SalaryAdjustment::create([
        'user_id'      => $employee->id,
        'created_by'   => Auth::id(),
        'type'         => $data['type'],
        'amount_usd'   => $data['amount_usd'],
        'reason'       => $data['reason'] ?? null,
        'salary_month' => $month['str'], // ← دايماً "2026-06-01"
    ]);

    return [
        'message' => $data['type'] === 'bonus' ? 'تمت إضافة المكافأة بنجاح' : 'تمت إضافة الخصم بنجاح',
        'data'    => $adjustment,
    ];
}

public function getPendingAdjustments(int $userId, ?string $input = null)
{
    $month = $this->parseMonth($input ?? now()); // ← هون

    return SalaryAdjustment::where('user_id', $userId)
        ->whereNull('salary_payment_id')
        ->whereYear('salary_month', $month['year'])
        ->whereMonth('salary_month', $month['month'])
        ->get();
}

public function paySalary(int $userId, array $data): array
{
    return DB::transaction(function () use ($userId, $data) {

        $employee = User::whereHas('roles', fn ($q) => $q->whereIn('name', $this->salariedRoles))
            ->findOrFail($userId);

        $month = $this->parseMonth($data['salary_month'] ?? now()); // ← هون — نفس المنطق تماماً

        $alreadyPaid = SalaryPayment::where('user_id', $userId)
            ->whereYear('salary_month', $month['year'])
            ->whereMonth('salary_month', $month['month'])
            ->exists();

        if ($alreadyPaid) {
            throw new \DomainException('تم دفع راتب ' . $month['str'] . ' لهذا الموظف مسبقاً');
        }

        $baseAmount = isset($data['amount_usd']) && $data['amount_usd'] > 0
            ? (float) $data['amount_usd']
            : (float) $employee->base_salary_usd;

        if (!$baseAmount) {
            throw new \DomainException('لا يوجد راتب أساسي محدد لهذا الموظف');
        }

        $pendingAdjustments = SalaryAdjustment::where('user_id', $userId)
            ->whereNull('salary_payment_id')
            ->whereYear('salary_month', $month['year'])  // ← نفس year
            ->whereMonth('salary_month', $month['month']) // ← نفس month
            ->get();

        $bonusTotal     = (float) $pendingAdjustments->where('type', 'bonus')->sum('amount_usd');
        $deductionTotal = (float) $pendingAdjustments->where('type', 'deduction')->sum('amount_usd');
        $netAmountUsd   = $baseAmount + $bonusTotal - $deductionTotal;

        if ($netAmountUsd < 0) {
            throw new \DomainException('قيمة الخصومات أكبر من الراتب، تحقق من البيانات');
        }

        $rate         = Exchange_Rate::latest('id')->first();
        $netAmountSyp = $rate ? round($netAmountUsd * $rate->rate, 2) : null;

        $payment = SalaryPayment::create([
            'user_id'             => $employee->id,
            'paid_by'             => Auth::id(),
            'exchange_rate_id'    => $rate?->id,
            'base_amount_usd'     => $baseAmount,
            'bonus_total_usd'     => $bonusTotal,
            'deduction_total_usd' => $deductionTotal,
            'amount_usd'          => $netAmountUsd,
            'amount_syp'          => $netAmountSyp,
            'salary_month'        => $month['str'], // ← دايماً "2026-06-01"
            'payment_date'        => $data['payment_date'] ?? now(),
            'notes'               => $data['notes'] ?? null,
            'status'              => 'paid',
        ]);

        if ($pendingAdjustments->isNotEmpty()) {
            SalaryAdjustment::whereIn('id', $pendingAdjustments->pluck('id')->toArray())
                ->update(['salary_payment_id' => $payment->id]);
        }

        $linkedCount = SalaryAdjustment::where('salary_payment_id', $payment->id)->count();

        return [
            'message'             => 'تم دفع الراتب بنجاح',
            'data'                => $payment->load('exchangeRate'),
            'summary'             => [
                'base_amount_usd'     => $baseAmount,
                'bonus_total_usd'     => $bonusTotal,
                'deduction_total_usd' => $deductionTotal,
                'net_amount_usd'      => $netAmountUsd,
                'net_amount_syp'      => $netAmountSyp,
            ],
            'adjustments_applied' => $pendingAdjustments,
            'adjustments_linked'  => $linkedCount,
        ];
    });
}

     public function deleteAdjustment(int $adjustmentId): array
    {
        $adjustment = SalaryAdjustment::findOrFail($adjustmentId);

        // ✅ يمنع الحذف لو ارتبط بدفعة
        if (!is_null($adjustment->salary_payment_id)) {
            throw new \DomainException('لا يمكن حذف تعديل مرتبط بدفعة راتب تم تسديدها');
        }

        $adjustment->delete();

        return ['message' => 'تم حذف التعديل بنجاح'];
    }

    public function getEmployeeSalaryHistory(int $userId)
    {
        $employee = User::findOrFail($userId);

        return [
            'employee' => [
                'id'              => $employee->id,
                'name'            => $employee->name,
                'role'            => $employee->roles->first()?->name,
                'base_salary_usd' => $employee->base_salary_usd,
            ],
            'payments' => SalaryPayment::with('exchangeRate', 'paidBy:id,name')
                ->where('user_id', $userId)
                ->latest('payment_date')
                ->get(),
        ];
    }

    public function getAllSalaryPayments(?string $month = null)
    {
        $query = SalaryPayment::with(
            'employee:id,name',
            'employee.roles',
            'paidBy:id,name'
        );

        if ($month) {
            $targetMonth = Carbon::parse($month)->startOfMonth();
            $query->whereYear('salary_month', $targetMonth->year)
                  ->whereMonth('salary_month', $targetMonth->month);
        }

        return $query->latest('payment_date')->get();
    }
}