<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment_Plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\InvoiceApproved; 
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
class InvoiceService
{
 // protected $messaging;
//عرض فواتير المورد
    public function getAll()
{
  return Invoice::where('type', 'supplier')
    ->with(['items', 'supplier'])
    ->orderByDesc('created_at')
    ->paginate(20);  
}

//عرض فواتير المرضى
public function getAllPatientInvoices()
{
    return Invoice::where('type', 'patient')
        ->with([
            //'items',        // الجلسات (invoice_items)
            'payments',     // الدفعات
          // 'patient.user',      // المريض
            'plans'          // الخطة (اختياري)
        ])
        ->orderByDesc('created_at')
         ->paginate(20);        
}

//اعتماد الفاتورة بس للموردين
public function approve($id)
{
    $invoice = Invoice::findOrFail($id);

    if ($invoice->type !== 'supplier') {
        return [
            'success' => false,
            'message' => "Only supplier invoices can be approved"
        ];
   // throw new \Exception("Only supplier invoices can be approved");
}
    if ($invoice->status !== 'draft') {
        return [
            'success' => false,
            'message' => "Only draft invoices can be approved"
        ];
      //  throw new \Exception("Only draft invoices can be approved");
    }

    $invoice->update([
        'status' => 'issued'
    ]);
     event(new InvoiceApproved($invoice)); 

    return $invoice;
}

public function payInvoice($invoiceId, $amount , string $idempotencyKey)
{
    // 🔑 أول شي: هل هاد المفتاح انعالج قبل؟ إذا إيه رجّعي نفس النتيجة القديمة
    $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
    if ($existing) {
        return [
            'message'   => 'Payment already recorded (duplicate request ignored)',
            'data'      => $existing->invoice->load('payments'),
            //'receipt'   => $existing->receipt_number,
            'duplicate' => true, // فيدك تعرفي بالفرونت إنه هاد رد مكرر مش دفعة جديدة
        ];
    }

    return DB::transaction(function () use ($invoiceId, $amount, $idempotencyKey) {
        $invoice = Invoice::with('payments')->findOrFail($invoiceId);
    
        // ✅ هون بس — فاتورة المورد لازم تكون issued قبل ما تنقبل دفعات
        if ($invoice->type === 'supplier' && $invoice->status === 'draft') {
            return [
                'success' => false,
                'message' => 'لا يمكن إضافة دفعة لفاتورة مورد بحالة مسودة، يجب أن يوافق عليها الأدمن أولاً',
            ];
        }
    
        $total = $invoice->total_amount_USD_after_discount > 0
            ? $invoice->total_amount_USD_after_discount
            : $invoice->total_amount_USD;
    
        if ($invoice->paid_amount >= $total) {
            return [
                'success' => false,
                'message' => "Invoice already fully paid"
            ];
        }
    
        if (($invoice->paid_amount + $amount) > $total) {
            return [
                'success' => false,
                'message' => "Payment exceeds remaining amount"
            ];
        }
    $invoice = Invoice::with('payments')->findOrFail($invoiceId);
    
// ✅ هون بس — فاتورة المورد لازم تكون issued قبل ما تنقبل دفعات
    if ($invoice->type === 'supplier' && $invoice->status === 'draft') {
        return [
            'success' => false,
            'message' => 'لا يمكن إضافة دفعة لفاتورة مورد بحالة مسودة، يجب أن يوافق عليها الأدمن أولاً',
        ];
    }

    $total = $invoice->total_amount_USD_after_discount > 0
        ? $invoice->total_amount_USD_after_discount
        : $invoice->total_amount_USD;

    if ($invoice->paid_amount >= $total) {
        return [
            'success' => false,
            'message' => "Invoice already fully paid"
        ];
      //  throw new \Exception("Invoice already fully paid");
    }

    if (($invoice->paid_amount + $amount) > $total) {
        return [
            'success' => false,
            'message' => "Payment exceeds remaining amount"
        ];
        //throw new \Exception("Payment exceeds remaining amount");
    }
    $rate = app(ExchangeRateService::class)->getCurrentUsdToSypRate();

    Payment::create([
        'invoice_id' => $invoice->id,
        'idempotency_key' => $idempotencyKey, // حفظ المفتاح
        'amount' => $amount,
        'method' => 'cash',
        'exchange_rate' => $rate->rate,
        'created_by' => Auth::id(),
    ]);

    // 🔥 تحديث البيانات
    $invoice->refresh();

    $invoice->paid_amount = $invoice->payments()->sum('amount');

    $this->updateStatus($invoice, $total);

    // 💰 حساب الليرة
    $invoice->total_amount_SYP = $invoice->total_amount_USD * $invoice->exchange_rate;

    $invoice->save();

    $remaining = $total - $invoice->paid_amount;

    return [
        'message' => 'Payment recorded successfully',
        'data' => $invoice->load('payments'),
        'remaining' => $remaining
    ];
     });
}


public function getById($id)
{
    return Invoice::with([
        'items',
        'supplier',
        'patient.user',
        'plans',
         'payments' 
    ])->findOrFail($id);
}


private function updateStatus($invoice, $total)
{
    if ($invoice->paid_amount == 0) {
        $invoice->status = 'issued';
    } elseif ($invoice->paid_amount < $total) {
        $invoice->status = 'partial';
    } else {
        $invoice->status = 'paid';
    }
}

public function applyDiscount($invoiceId, $discount)
{
    $invoice = Invoice::findOrFail($invoiceId);

    // ❗ فقط للمورد والمريض
    if (!in_array($invoice->type, ['supplier', 'patient'])) {
            return [
                'success' => false,
                'message' => "Discount not allowed for this invoice type"
            ];
        //throw new \Exception("Discount not allowed for this invoice type");
    }

    // 🧾 خزّن نسبة الخصم
    $invoice->discount = $discount;

    // 💰 القيمة الأصلية
    $totalBefore = $invoice->total_amount_USD;

    // 🧮 حساب الخصم
    $discountValue = ($totalBefore * $discount) / 100;
    $totalAfter = $totalBefore - $discountValue;
 // ✅ 👇 هون بالضبط تحطها
    if ($invoice->paid_amount > $totalAfter) {
            return [
                'success' => false,
                'message' => "Discount invalid: paid amount exceeds total after discount"
            ];
       // throw new \Exception("Discount invalid: paid amount exceeds total after discount");
    }
    // 💾 التخزين
    $invoice->total_amount_USD_after_discount = $totalAfter;
    $invoice->total_amount_SYP_after_discount = $totalAfter * $invoice->exchange_rate;

    // 🔥 مهم: تحديث الحالة بعد الخصم
    $this->updateStatus($invoice, $totalAfter);

    $invoice->save();
// إضافة حساب المتبقي للريسبونس
    $remaining = $totalAfter - $invoice->paid_amount;

    return [
        'invoice' => $invoice,
        'remaining' => $remaining
    ];
}

// public function createForPatient(array $data)
// {
//     return DB::transaction(function () use ($data) {

//         $patient = Patient::findOrFail($data['patient_id']);
//         $plan = Treatment_Plan::findOrFail($data['plan_id']);

//         $rate = app(ExchangeRateService::class)->getCurrentUsdToSypRate();

//         // 1️⃣ إنشاء الفاتورة بدون رقم نهائي
//         $invoice = Invoice::create([
//             'type' => 'patient',
//             'patient_id' => $patient->id,
//             'plan_id' => $plan->id,
//             'status' => 'draft',
//             'paid_amount' => 0,
//             'total_amount_USD' => 0,
//             'total_amount_SYP' => 0,
//             'exchange_rate' => $rate->rate,
//             'issued_at' => $data['issued_at'] ?? now(),
//             'created_by' => Auth::id(),
//         ]);

//         // 2️⃣ توليد الرقم بعد ما صار عندنا ID
//         $invoice->invoice_number =  'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
//         $invoice->save();

//         return $invoice;
//     });
// }
//انشاء فاتورة المريض 
public function createPatientInvoice(array $data)
{
    return DB::transaction(function () use ($data) {
        $patient = Patient::findOrFail($data['patient_id']);
        $plan = Treatment_Plan::findOrFail($data['plan_id']);

        $rate = app(ExchangeRateService::class)->getCurrentUsdToSypRate();
        
        // لنفترض أنكِ حددتِ سعر الخطة الإجمالي في جدول الخطط أو يأتي من الـ $data
        $totalUsd = $data['total_amount'] ?? $plan->price_usd; 

        // 1. إنشاء الفاتورة
        $invoice = Invoice::create([
            'type' => 'patient',
            'patient_id' => $patient->id,
            'plan_id' => $plan->id,
            'status' => 'draft',
            'paid_amount' => 0,
            'total_amount_USD' => $totalUsd,
            'total_amount_SYP' => $totalUsd * $rate->rate,
            'exchange_rate' => $rate->rate,
            'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(uniqid()),
            'issued_at' => now(),
            'created_by' => Auth::id(),
        ]);

        return $invoice;
    });
}

public function addSession($invoice, $session)
{
    $amount = $session->rprice_usd;

    // add item
    $invoice->items()->create([
        'treatment_session_id' => $session->id,
        'description' => $session->name,
        'quantity' => 1,
        'unit_price' => $amount,
        'subtotal' => $amount,
    ]);

    // recalc total
    $this->recalculate($invoice);

    return $invoice;
}

public function recalculate($invoice)
{
    $invoice->total_amount_USD = $invoice->items()->sum('subtotal');
    $invoice->total_amount_SYP = $invoice->total_amount_USD * $invoice->exchange_rate;
    $invoice->status = $this->status($invoice);

    $invoice->save();

    return $invoice;
}

private function status($invoice)
{
    if ($invoice->paid_amount == 0) return 'issued';
    if ($invoice->paid_amount < $invoice->total_amount_USD) return 'partial';
    return 'paid';
}

//  للمرضى: إحصائيات شهرية للفواتير حسب الحالة
public function getMonthlyStatusStats($year = null)
{
    $year = $year ?? date('Y');

    $results = \App\Models\Invoice::where('type', 'patient')
        ->whereYear('created_at', $year)
        ->whereIn('status', ['draft', 'issued', 'partial', 'paid'])
        ->selectRaw('
            MONTH(created_at) as month,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status IN ("draft", "issued", "partial") THEN 1 ELSE 0 END) as pending_count
        ')
        ->groupBy('month')
        ->get()
        ->keyBy('month'); // ← فهرسة النتائج برقم الشهر عشان نلاقيها بسرعة

    // نبني مصفوفة من 1 لـ 12 ونعبي الفاضي بصفر
    $stats = [];
    for ($month = 1; $month <= 12; $month++) {
        if ($results->has($month)) {
            $stats[] = [
                'month'           => $month,
                'completed_count' => (int) $results[$month]->completed_count,
                'pending_count'   => (int) $results[$month]->pending_count,
            ];
        } else {
            $stats[] = [
                'month'           => $month,
                'completed_count' => 0,
                'pending_count'   => 0,
            ];
        }
    }

    return $stats;
}
// إحصائيات شهرية للفواتير حسب الحالة للموردين
public function getMonthlySupplierStatusStats($year = null)
{
    $year = $year ?? date('Y');

    $results = \App\Models\Invoice::where('type', 'supplier')
        ->whereYear('created_at', $year)
        ->whereIn('status', ['draft', 'issued', 'partial', 'paid'])
        ->selectRaw('
            MONTH(created_at) as month,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status IN ("draft", "issued", "partial") THEN 1 ELSE 0 END) as pending_count
        ')
        ->groupBy('month')
        ->get()
        ->keyBy('month');

    $stats = [];
    for ($month = 1; $month <= 12; $month++) {
        if ($results->has($month)) {
            $stats[] = [
                'month'           => $month,
                'completed_count' => (int) $results[$month]->completed_count,
                'pending_count'   => (int) $results[$month]->pending_count,
            ];
        } else {
            $stats[] = [
                'month'           => $month,
                'completed_count' => 0,
                'pending_count'   => 0,
            ];
        }
    }

    return $stats;
}

// إحصائيات الإيرادات لسنة
public function getMonthlyRevenueStats($year = null)
{
    $year = $year ?? date('Y');

    $stats = \App\Models\Payment::whereHas('invoice', function ($query) {
            $query->where('type', 'patient');
        })
        ->whereYear('created_at', $year)
        ->selectRaw('
            MONTH(created_at) as month,
            SUM(amount) as total_collected_usd
        ')
        ->groupBy('month')
        ->get()
        ->keyBy('month');

    $months = collect(range(1, 12))->map(function ($month) use ($stats) {
        return [
            'month'             => $month,
            'total_collected_usd' => (float) ($stats[$month]['total_collected_usd'] ?? 0),
        ];
    });

    return [
        'months'    => $months,                                          // شهر شهر
        'yearly_total' => $months->sum('total_collected_usd'),           // مجموع السنة
    ];
}
//الايرادات لشهر محدد 
public function getMonthlyRevenueBySpecificMonth($month, $year = null)
{
    $year = $year ?? date('Y');

    return \App\Models\Payment::whereHas('invoice', function ($query) {
            $query->where('type', 'patient');
        })
        ->whereYear('created_at', $year)
        ->whereMonth('created_at', $month) // الفلترة حسب الشهر هنا
        ->sum('amount'); // نستخدم sum مباشرة للحصول على الرقم النهائي
}

//اشعارات للمرضى بالفواتير المتأخرة بالدفع
// public function sendPaymentReminders()
// {
//     $overdueInvoices = $this->getOverdueInvoices(); // الفواتير المتأخرة

//     foreach ($overdueInvoices as $invoice) {
//         // نتحقق: هل أرسلنا له خلال آخر 7 أيام؟
//         if ($invoice->last_reminder_sent_at && $invoice->last_reminder_sent_at->greaterThan(now()->subDays(7))) {
//             continue;
//         }
// $title ='تذكير بدفعة مستحقة';
// $body  ='عزيزي المريض، يرجى مراجعة المركز لإتمام الدفعة المستحقة على خطتك العلاجية ';
//      $message = CloudMessage::new()
//  ->withToken($fcmToken)
//  ->withNotification(
//  Notification::create(
//  $title,
//  $body
// )
// ) ->withData([
//  'type' => 'test',
// 'timestamp' => now()->toDateTimeString(),
//  ]);
//  $response = $this->messaging->send($message);


//         // // إرسال الإشعار باستخدام الـ Job الموجود عندك
//         // dispatch(new \App\Jobs\SendNotificationJob(
//         //     [$invoice->patient->user->id],
//         //     'تذكير بدفعة مستحقة',
//         //     "عزيزي المريض، يرجى مراجعة المركز لإتمام الدفعة المستحقة على خطتك العلاجية.",
//         //     'payment_reminder',
//         //     ['invoice_id' => $invoice->id]
//         // ));

//         // تسجيل تاريخ الإرسال
//         $invoice->update(['last_reminder_sent_at' => now()]);
//     }
// }
public function sendPaymentReminders()
{
    $overdueInvoices = $this->getOverdueInvoices();

    foreach ($overdueInvoices as $invoice) {

        // لا ترسل إذا تم إرسال تذكير خلال آخر 7 أيام
        if (
            $invoice->last_reminder_sent_at &&
            $invoice->last_reminder_sent_at
                ->greaterThanOrEqual(now()->subDays(7))
        ) {
            continue;
        }

        $user = $invoice->patient?->user;

        if (!$user) {
            continue;
        }

        dispatch(new \App\Jobs\SendNotificationJob(
            [$user->id],
            'تذكير بدفعة مستحقة',
            'عزيزي المريض، يرجى مراجعة المركز لإتمام الدفعة المستحقة على خطتك العلاجية.',
            'payment_reminder',
            [
                'invoice_id' => $invoice->id,
            ]
        ));

        // تسجيل وقت إرسال التذكير
        $invoice->update([
            'last_reminder_sent_at' => now(),
        ]);
    }
}

//عرض لفواتير المتأخرة بالدفع
public function getOverdueInvoices($fromDate = null, $toDate = null)
{
    $startDate = $fromDate ? Carbon::parse($fromDate) : now()->subMonths(12);
    $endDate   = $toDate   ? Carbon::parse($toDate)->endOfDay() : now();

    return Invoice::where('type', 'patient')
        ->where('status', '!=', 'paid')
        ->where('status', '!=', 'cancelled')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->with(['patient.user', 'plans'])
        ->get()
        ->filter(function ($invoice) {
            if (!$invoice->plans) return false;

            $progress = $invoice->plans->progress_percent;
            $paid     = $invoice->paid_percent;

            // الحالة 1: الخطة مكتملة 100% والمريض لم يكمل الدفع
            $case1 = ($progress == 100 && $paid < 100);

            // الحالة 2: نسبة الإنجاز أكبر من نسبة الدفع بـ 20% أو أكثر
            $case2 = ($progress > $paid + 20);

            return $case1 || $case2;
        });
}

}