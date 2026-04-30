<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
//عرض فواتير المورد
    public function getAll()
{
    return Invoice::with(['items', 'supplier'])
        ->orderByDesc('created_at')
        ->get();
}
//اعتماد الفاتورة
public function approve($id)
{
    $invoice = Invoice::findOrFail($id);

    if ($invoice->status !== 'draft') {
        throw new \Exception("Only draft invoices can be approved");
    }

    $invoice->update([
        'status' => 'issued'
    ]);

    return $invoice;
}
// دفع الفاتورة
public function payInvoice($invoiceId, $amount)
{
    $invoice = Invoice::findOrFail($invoiceId);

    // 🎯 حدد الإجمالي الصحيح
    
      $total = $invoice->total_amount_USD_after_discount > 0
    ? $invoice->total_amount_USD_after_discount
    : $invoice->total_amount_USD;
    // 🚨 تحقق قبل أي شي
    if ($invoice->paid_amount >= $total) {
        throw new \Exception("Invoice already fully paid");
    }

    if (($invoice->paid_amount + $amount) > $total) {
        throw new \Exception("Payment exceeds remaining amount");
    }

    // 💰 سجل الدفع
    Payment::create([
        'invoice_id' => $invoice->id,
        'amount' => $amount,
        'method' => 'cash',
        'created_by' => Auth::id(),
    ]);

    // 💰 حدث المدفوع
    $invoice->paid_amount += $amount;

    // 🧠 الحالة
    if ($invoice->paid_amount == 0) {
        $invoice->status = 'issued';
    } elseif ($invoice->paid_amount < $total) {
        $invoice->status = 'partial';
    } else {
        $invoice->status = 'paid';
    }

    $invoice->save();

    return $invoice;
}

public function getById($id)
{
    return Invoice::with(['items.item', 'supplier'])
        ->findOrFail($id);
}
//تطبيق الخصم
public function applyDiscount($invoiceId, $discount)
{
    $invoice = Invoice::findOrFail($invoiceId);

    $invoice->discount = $discount;

    // 💰 لازم نجيب القيمة الأصلية (قبل أي تعديل)
    $totalBefore = $invoice->getOriginal('total_amount_USD');

    if (!$totalBefore) {
        $totalBefore = $invoice->total_amount_USD;
    }

    $discountValue = ($totalBefore * $discount) / 100;

    $totalAfter = $totalBefore - $discountValue;

    // 💾 خزّن بعد الخصم
    $invoice->total_amount_USD_after_discount = $totalAfter;
    $invoice->total_amount_SYP_after_discount = $totalAfter * $invoice->exchange_rate;

    // ⚠️ مهم جداً: لا تلمس الأصل
    // ❌ لا تعدل total_amount_USD

    $invoice->save();

    return $invoice;
}

}