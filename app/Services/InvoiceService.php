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
//وضع علامة مدفوعة على الفاتورة
// public function markAsPaid($id)
// {
//     $invoice = Invoice::findOrFail($id);

//     if ($invoice->status !== 'issued') {
//         throw new \Exception("Invoice must be approved first");
//     }

//     $invoice->update([
//         'status' => 'paid'
//     ]);

//     return $invoice;
// }
public function payInvoice($invoiceId, $amount)
{
    $invoice = Invoice::findOrFail($invoiceId);

    // 💰 سجل الدفع
    Payment::create([
        'invoice_id' => $invoice->id,
        'amount' => $amount,
        'method' => 'cash',
        'created_by' => Auth::id(),
    ]);

    // 💰 تحديث الفاتورة
    $invoice->paid_amount += $amount;

    $total = $invoice->total_amount_USD;

    if ($invoice->paid_amount <= 0) {
        $invoice->status = 'approved';
    } elseif ($invoice->paid_amount < $total) {
        $invoice->status = 'partial';
    } else {
        $invoice->status = 'paid';
        $invoice->paid_amount = $total;
    }

    $invoice->save();

    return $invoice;
}

public function getById($id)
{
    return Invoice::with(['items.item', 'supplier'])
        ->findOrFail($id);
}
}