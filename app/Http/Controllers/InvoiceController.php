<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;
use App\Models\Invoice;
use App\Models\Payment;

class InvoiceController extends Controller
{
    private InvoiceService $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }
//عرض فواتير المورد
   public function index()
{
    $invoices = $this->service->getAll();

    return response()->json($invoices);
} 
//اعتماد الفاتورة
public function approve($id)
{
    $invoice = $this->service->approve($id);

    return response()->json([
        'message' => 'Invoice approved',
        'data' => $invoice
    ]);
}
// دفع الفاتورة
public function pay(Request $request, $id)
{
    $request->validate([
        'amount' => 'required|numeric|min:0.01'
    ]);

    $invoice = $this->service->payInvoice($id, $request->amount);

    return response()->json([
        'message' => 'Payment recorded successfully',
        'data' => $invoice
    ]);
}
//'طباعة الفاتورة
public function print($id)
{
    $invoice = $this->service->getById($id);

    $supplierName = $invoice->supplier?->name ?? '-';

    // $remaining = $invoice->total_amount_USD - $invoice->paid_amount;
    $total = $invoice->total_amount_USD_after_discount > 0
    ? $invoice->total_amount_USD_after_discount
    : $invoice->total_amount_USD;

$remaining = $total - $invoice->paid_amount;

    $html = '
    <html dir="rtl">
    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: dejavusans;
                direction: rtl;
                text-align: right;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th, td {
                border: 1px solid #000;
                padding: 6px;
                text-align: center;
            }

            .section {
                margin-top: 10px;
            }
        </style>
    </head>
    <body>

        <h2 style="text-align:center;">
            فاتورة رقم ' . ($invoice->invoice_number ?? 'INV-' . $invoice->id) . '
        </h2>

        <p>النوع: ' . $invoice->type . '</p>
        <p>المورد: ' . $supplierName . '</p>
        <p>الحالة: ' . $invoice->status . '</p>
        <p>التاريخ: ' . $invoice->issued_at . '</p>
        <p>سعر الصرف: ' . $invoice->exchange_rate . '</p>

        <table>
            <tr>
                <th>المادة</th>
                <th>الكمية</th>
                <th>سعر القطعة</th>
                <th>الإجمالي</th>
            </tr>
    ';

    foreach ($invoice->items as $item) {
        $html .= '
            <tr>
                <td>' . $item->description . '</td>
                <td>' . $item->quantity . '</td>
                <td>' . number_format($item->unit_price, 2) . '</td>
                <td>' . number_format($item->subtotal, 2) . '</td>
            </tr>
        ';
    }

    $html .= '</table><br>';

    /*
    |--------------------------------------------------------------------------
    | 💰 عرض الإجمالي (مع أو بدون خصم)
    |--------------------------------------------------------------------------
    */

    // if (!empty($invoice->discount) && $invoice->discount > 0) {

    //     $before = $invoice->total_before_discount ?? $invoice->total_amount_USD;
    //     $after  = $invoice->total_after_discount ?? $invoice->total_amount_USD;

    //     $html .= '
    //         <div class="section">
    //             <h3>الإجمالي قبل الخصم: ' . number_format($before, 2) . ' USD</h3>
    //             <h3>الإجمالي قبل الخصم: ' . number_format($before * $invoice->exchange_rate, 2) . ' SYP</h3>

    //             <h3>نسبة الخصم: ' . $invoice->discount . ' %</h3>

    //             <h3>الإجمالي بعد الخصم: ' . number_format($after, 2) . ' USD</h3>
    //             <h3>الإجمالي بعد الخصم: ' . number_format($after * $invoice->exchange_rate, 2) . ' SYP</h3>
    //         </div>
    //     ';
    if (!empty($invoice->discount) && $invoice->discount > 0) {

    $before = $invoice->total_amount_USD;
    $after  = $invoice->total_amount_USD_after_discount;

    $html .= '
        <div class="section">
            <h3>الإجمالي قبل الخصم: ' . number_format($before, 2) . ' USD</h3>
            <h3>الإجمالي قبل الخصم: ' . number_format($invoice->total_amount_SYP, 2) . ' SYP</h3>

            <h3>نسبة الخصم: ' . $invoice->discount . ' %</h3>

            <h3>الإجمالي بعد الخصم: ' . number_format($after, 2) . ' USD</h3>
            <h3>الإجمالي بعد الخصم: ' . number_format($invoice->total_amount_SYP_after_discount, 2) . ' SYP</h3>
        </div>
    ';
}

     else {

        $html .= '
            <div class="section">
                <h3>الإجمالي: ' . number_format($invoice->total_amount_USD, 2) . ' USD</h3>
                <h3>الإجمالي: ' . number_format($invoice->total_amount_SYP, 2) . ' SYP</h3>
            </div>
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | 💵 الدفع
    |--------------------------------------------------------------------------
    */

    $html .= '
        <div class="section">
            <h3>المدفوع: ' . number_format($invoice->paid_amount, 2) . ' USD</h3>
            <h3>المتبقي: ' . number_format($remaining, 2) . ' USD</h3>
        </div>
    ';

    $html .= '
    </body>
    </html>
    ';

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);

    return response($mpdf->Output('', 'S'))
        ->header('Content-Type', 'application/pdf');
}
public function applyDiscount(Request $request, $id)
{
    $request->validate([
        'discount' => 'required|numeric|min:0|max:100'
    ]);

    $invoice = $this->service->applyDiscount($id, $request->discount);

    return response()->json([
        'message' => 'Discount applied successfully',
        'data' => $invoice
    ]);
}


// public function print($id)
// {
//     $invoice = $this->service->getById($id);

//     $supplierName = $invoice->supplier?->name ?? '-';

//     $remaining = $invoice->total_amount_USD - $invoice->paid_amount;

//     $html = '
//     <html dir="rtl">
//     <head>
//         <meta charset="utf-8">
//         <style>
//             body {
//                 font-family: dejavusans;
//                 direction: rtl;
//                 text-align: right;
//             }

//             table {
//                 border-collapse: collapse;
//                 width: 100%;
//             }

//             th, td {
//                 border: 1px solid #000;
//                 padding: 6px;
//                 text-align: center;
//             }
//         </style>
//     </head>
//     <body>

//         <h2 style="text-align:center;">فاتورة رقم ' . $invoice->invoice_number . '</h2>
// <p>النوع: ' . $invoice->type . '</p>
//         <p>المورد: ' . $supplierName . '</p>
//         <p>الحالة: ' . $invoice->status . '</p>
//         <p>التاريخ: ' . $invoice->issued_at . '</p>
//         <p>سعر الصرف وقت الفاتورة: ' . $invoice->exchange_rate . '</p>

//         <table>
//             <tr>
//                 <th>المادة</th>
//                 <th>الكمية</th>
//                 <th>سعر القطعة</th>
//                 <th>الإجمالي</th>
//             </tr>
//     ';

//     foreach ($invoice->items as $item) {
//         $html .= '
//             <tr>
//                 <td>' . $item->description . '</td>
//                 <td>' . $item->quantity . '</td>
//                 <td>' . $item->unit_price . '</td>
//                 <td>' . $item->subtotal . '</td>
//             </tr>
//         ';
//     }

//     $html .= '
//         </table>

//         <br>

//         <h3>الإجمالي بالدولار: ' . $invoice->total_amount_USD . 'USD</h3>
//         <h3>الإجمالي بالليرة: ' . $invoice->total_amount_SYP . ' SYP</h3>
//        <h3>المدفوع: ' . $invoice->paid_amount . ' USD</h3>
// <h3>المتبقي: ' . $remaining . ' USD</h3>
//         <h3>الحالة: ' . $invoice->status . '</h3>

//     </body>
//     </html>
//     ';

//     $mpdf = new Mpdf();
//     $mpdf->WriteHTML($html);

//     return response($mpdf->Output('', 'S'))
//         ->header('Content-Type', 'application/pdf');
// }

// public function print($id)
// {
//     $invoice = $this->service->getById($id);

//     $supplierName = $invoice->supplier?->name ?? '-';

//     $html = '
//     <html dir="rtl">
//     <head>
//         <meta charset="utf-8">
//         <style>
//             body {
//                 font-family: dejavusans;
//                 direction: rtl;
//                 text-align: right;
//             }

//             table {
//                 border-collapse: collapse;
//                 width: 100%;
//             }

//             th, td {
//                 border: 1px solid #000;
//                 padding: 6px;
//                 text-align: center;
//             }
//         </style>
//     </head>
//     <body>

//         <h2 style="text-align:center;">فاتورة رقم #' . $invoice->id . '</h2>

//         <p>المورد: ' . $supplierName . '</p>
//         <p>الحالة: ' . $invoice->status . '</p>
//         <p>التاريخ: ' . $invoice->issued_at . '</p>

//         <table>
//             <tr>
//                 <th>المادة</th>
//                 <th>الكمية</th>
//                 <th>سعر القطعة</th>
//                 <th>الإجمالي</th>
//             </tr>
//     ';

//     foreach ($invoice->items as $item) {
//         $html .= '
//             <tr>
//                 <td>' . $item->description . '</td>
//                 <td>' . $item->quantity . '</td>
//                 <td>' . $item->unit_price . '</td>
//                 <td>' . $item->subtotal . '</td>
//             </tr>
//         ';
//     }

//     $html .= '
//         </table>

//         <h3>الإجمالي بالدولار: ' . $invoice->total_amount_USD . '</h3>
//         <h3>الإجمالي بالليرة: ' . $invoice->total_amount_SYP . '</h3>

//     </body>
//     </html>
//     ';

//     $mpdf = new Mpdf();

//     $mpdf->WriteHTML($html);

//     return response($mpdf->Output('', 'S'))
//         ->header('Content-Type', 'application/pdf');
// }




// public function print($id)
// {
//     $invoice = $this->service->getById($id);

//     $supplierName = $invoice->supplier?->name ?? '-';

//     $html = '
//     <html>
//     <head>
//         <meta charset="utf-8">
//         <style>
//             body {
//                 font-family: DejaVu Sans, sans-serif;
//                 direction: rtl;
//                 text-align: right;
//             }

//             table {
//                 border-collapse: collapse;
//                 width: 100%;
//                 direction: rtl;
//             }

//             th, td {
//                 border: 1px solid #000;
//                 padding: 6px;
//                 text-align: center;
//             }

//             h2 {
//                 text-align: center;
//             }
//         </style>
//     </head>
//     <body>

//         <h2>فاتورة رقم #' . $invoice->id . '</h2>

//         <p><strong>المورد:</strong> ' . $supplierName . '</p>
//         <p><strong>الحالة:</strong> ' . $invoice->status . '</p>
//         <p><strong>التاريخ:</strong> ' . $invoice->issued_at . '</p>

//         <table>
//             <thead>
//                 <tr>
//                     <th>المادة</th>
//                     <th>الكمية</th>
//                     <th>سعر القطعة</th>
//                     <th>الإجمالي</th>
//                 </tr>
//             </thead>
//             <tbody>
//     ';

//     foreach ($invoice->items as $item) {
//         $html .= '
//             <tr>
//                 <td>' . $item->description . '</td>
//                 <td>' . $item->quantity . '</td>
//                 <td>' . $item->unit_price . '</td>
//                 <td>' . $item->subtotal . '</td>
//             </tr>
//         ';
//     }

//     $html .= '
//             </tbody>
//         </table>

//         <br>

//         <h3>الإجمالي بالدولار: ' . $invoice->total_amount_USD . '</h3>
//         <h3>الإجمالي بالليرة: ' . $invoice->total_amount_SYP . '</h3>

//     </body>
//     </html>
//     ';

//     // 🔥 أهم سطر (أحياناً بيحل مشكلة PDF الفاضي)
//     $pdf = Pdf::loadHTML($html)->setPaper('A4');

//     return $pdf->stream("invoice_{$invoice->id}.pdf");
// }


// public function print($id)
// {
//     $invoice = $this->service->getById($id);

//     // 🧾 نبني HTML يدوي
//     $html = "
//         <h2>Invoice #{$invoice->id}</h2>
//         <p>Supplier: " . ($invoice->supplier->name ?? '-') . "</p>
//         <p>Status: {$invoice->status}</p>

//         <table border='1' width='100%' cellpadding='5'>
//             <tr>
//                 <th>Item</th>
//                 <th>Qty</th>
//                 <th>Price</th>
//                 <th>Subtotal</th>
//             </tr>
//     ";

//     foreach ($invoice->items as $item) {
//         $html .= "
//             <tr>
//                 <td>{$item->description}</td>
//                 <td>{$item->quantity}</td>
//                 <td>{$item->unit_price}</td>
//                 <td>{$item->subtotal}</td>
//             </tr>
//         ";
//     }

//     $html .= "
//         </table>

//         <h3>Total USD: {$invoice->total_amount_USD}</h3>
//         <h3>Total SYP: {$invoice->total_amount_SYP}</h3>
//     ";

//     $pdf = Pdf::loadHTML($html);

//     // 👇 فيك تختار وحدة منهم

//     //return $pdf->stream("invoice_{$invoice->id}.pdf"); // عرض
//      return $pdf->download("invoice_{$invoice->id}.pdf"); // تحميل
// }

}
