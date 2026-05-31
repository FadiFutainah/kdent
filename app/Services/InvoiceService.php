<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\patient;
use App\Models\Payment;
use App\Models\Treatment_plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
//عرض فواتير المورد
    public function getAll()
{
  return Invoice::where('type', 'supplier')
    ->with(['items', 'supplier'])
    ->orderByDesc('created_at')
    ->get();
}
//عرض فواتير المرضى
public function getAllPatientInvoices()
{
    return Invoice::where('type', 'patient')
        ->with([
            'items',        // الجلسات (invoice_items)
            'payments',     // الدفعات
            'patient',      // المريض
            'plan'          // الخطة (اختياري)
        ])
        ->orderByDesc('created_at')
        ->get();       
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

    return $invoice;
}
public function payInvoice($invoiceId, $amount)
{
    $invoice = Invoice::with('payments')->findOrFail($invoiceId);

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
}
// //دفع للفاتورة 
// public function payInvoice($invoiceId, $amount)
// {
//     $invoice = Invoice::with('payments')->findOrFail($invoiceId);

//     // 🎯 تحديد الإجمالي (مع الخصم إن وجد)
//     $total = $invoice->total_amount_USD_after_discount > 0
//         ? $invoice->total_amount_USD_after_discount
//         : $invoice->total_amount_USD;

//     // 🚨 تحقق
//     if ($invoice->paid_amount >= $total) {
//         throw new \Exception("Invoice already fully paid");
//     }

//     if (($invoice->paid_amount + $amount) > $total) {
//         throw new \Exception("Payment exceeds remaining amount");
//     }

//     // 💰 تسجيل الدفع
//     Payment::create([
//         'invoice_id' => $invoice->id,
//         'amount' => $amount,
//         'method' => 'cash',
//         'created_by' => Auth::id(),
//     ]);

//     // 🔥 إعادة حساب المدفوع (الأصح دائمًا)
//     $invoice->paid_amount = $invoice->payments()->sum('amount');

//     // 🧠 تحديث الحالة
//     $this->updateStatus($invoice, $total);

//     $invoice->save();

//     return $invoice;
// }
// // دفع الفاتورة
// public function payInvoice($invoiceId, $amount)
// {
//     $invoice = Invoice::findOrFail($invoiceId);

//     // 🎯 حدد الإجمالي الصحيح
    
//       $total = $invoice->total_amount_USD_after_discount > 0
//     ? $invoice->total_amount_USD_after_discount
//     : $invoice->total_amount_USD;
//     // 🚨 تحقق قبل أي شي
//     if ($invoice->paid_amount >= $total) {
//         throw new \Exception("Invoice already fully paid");
//     }

//     if (($invoice->paid_amount + $amount) > $total) {
//         throw new \Exception("Payment exceeds remaining amount");
//     }

//     // 💰 سجل الدفع
//     Payment::create([
//         'invoice_id' => $invoice->id,
//         'amount' => $amount,
//         'method' => 'cash',
//         'created_by' => Auth::id(),
//     ]);

//     // 💰 حدث المدفوع
//     $invoice->paid_amount += $amount;

//     // 🧠 الحالة
//     if ($invoice->paid_amount == 0) {
//         $invoice->status = 'issued';
//     } elseif ($invoice->paid_amount < $total) {
//         $invoice->status = 'partial';
//     } else {
//         $invoice->status = 'paid';
//     }

//     $invoice->save();

//     return $invoice;
// }

// public function getById($id)
// {
//     return Invoice::with(['items.item', 'supplier'])
//         ->findOrFail($id);
 
// }
public function getById($id)
{
    return Invoice::with([
        'items',
        'supplier',
        'patient',
        'plans'
    ])->findOrFail($id);
}

// //تطبيق الخصم
// public function applyDiscount($invoiceId, $discount)
// {
//     $invoice = Invoice::findOrFail($invoiceId);

//     $invoice->discount = $discount;

//     // 💰 لازم نجيب القيمة الأصلية (قبل أي تعديل)
//     $totalBefore = $invoice->getOriginal('total_amount_USD');

//     if (!$totalBefore) {
//         $totalBefore = $invoice->total_amount_USD;
//     }

//     $discountValue = ($totalBefore * $discount) / 100;

//     $totalAfter = $totalBefore - $discountValue;

//     // 💾 خزّن بعد الخصم
//     $invoice->total_amount_USD_after_discount = $totalAfter;
//     $invoice->total_amount_SYP_after_discount = $totalAfter * $invoice->exchange_rate;

//     // ⚠️ مهم جداً: لا تلمس الأصل
//     // ❌ لا تعدل total_amount_USD

//     $invoice->save();

//     return $invoice;
// }
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

    return $invoice;
}
// public function createForPatient($patientId, $planId)
// {    return DB::transaction(function () use ($data) {
//       $patient = patient::findOrFail($data['patient_id']);
//       $plan = TreatmentPlan::findOrFail($data['plan_id']);

//     $rate = app(ExchangeRateService::class)->getCurrentUsdToSypRate();
//       $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
//     return Invoice::create([
//         'number' => $invoiceNumber,
//         'type' => 'patient',
//         'patient_id' => $patient->id,
//         'plan_id' => $plan->id,
//         'status' => 'draft',
//         'paid_amount' => 0,
//         'total_amount_USD' => 0,
//         'exchange_rate' => $rate->rate,
//        'issued_at' => $data['issued_at'] ?? now(),
//         'created_by' => Auth::id(),
//     ]);
// });
// }
// public function createForPatient(array $data)
// {   
//      return DB::transaction(function () use ($data) {
//       $patient = patient::findOrFail($data['patient_id']);
//       $plan = Treatment_plan::findOrFail($data['plan_id']);

//     $rate = app(ExchangeRateService::class)->getCurrentUsdToSypRate();
//        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
//     return Invoice::create([
//         'invoice_number' => $invoiceNumber,
//         'type' => 'patient',
//         'patient_id' => $patient->id,
//         'plan_id' => $plan->id,
//         'status' => 'draft',
//         'paid_amount' => 0,
//         'total_amount_USD' => 0,
//         'total_amount_SYP' => 0,
//         'exchange_rate' => $rate->rate,
//        'issued_at' => $data['issued_at'] ?? now(),
//         'created_by' => Auth::id(),
//     ]);
  
// });
// }
public function createForPatient(array $data)
{
    return DB::transaction(function () use ($data) {

        $patient = Patient::findOrFail($data['patient_id']);
        $plan = Treatment_Plan::findOrFail($data['plan_id']);

        $rate = app(ExchangeRateService::class)->getCurrentUsdToSypRate();

        // 1️⃣ إنشاء الفاتورة بدون رقم نهائي
        $invoice = Invoice::create([
            'type' => 'patient',
            'patient_id' => $patient->id,
            'plan_id' => $plan->id,
            'status' => 'draft',
            'paid_amount' => 0,
            'total_amount_USD' => 0,
            'total_amount_SYP' => 0,
            'exchange_rate' => $rate->rate,
            'issued_at' => $data['issued_at'] ?? now(),
            'created_by' => Auth::id(),
        ]);

        // 2️⃣ توليد الرقم بعد ما صار عندنا ID
        $invoice->invoice_number =  'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
        $invoice->save();

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


}