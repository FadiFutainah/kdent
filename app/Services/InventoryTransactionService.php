<?php
namespace App\Services;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Invoice_Item;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\DB;
use App\Services\ExchangeRateService;
use App\Models\Notification;
use App\Events\InvoiceCreated;
use App\Events\LowStockDetected;

class InventoryTransactionService
{
      public function __construct(
        private ExchangeRateService $exchangeService
    ) {}

   public function purchaseBulk(array $data)
{
    return DB::transaction(function () use ($data) {

        $supplier = Supplier::findOrFail($data['supplier_id']);

        $rateModel = $this->exchangeService->getCurrentUsdToSypRate();
        $exchangeRate = $rateModel->rate;

        // 🧾 رقم فاتورة unique
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'type' => 'supplier',
            'supplier_id' => $supplier->id,
            'discount' => 0,
            'exchange_rate' => $exchangeRate,
            'status' => 'draft',
            'paid_amount' => 0,
            'issued_at' => $data['issued_at'] ?? now(),
        ]);
         // ✅ تحقق من النوع
        if ($invoice->type !== 'supplier') {
            throw new \Exception('Invoice type must be supplier');
        }

        $total = 0;

        foreach ($data['items'] as $row) {

            $supplierItem = SupplierItem::where('supplier_id', $supplier->id)
                ->where('id', $row['supplier_item_id'])
                ->firstOrFail();

            if (!$supplierItem->item_id) {

                $item = Item::firstOrCreate(
                    ['name' => $supplierItem->name],
                    [
                        'code' => strtoupper(uniqid('ITEM_')),
                        'unit' => $supplierItem->unit ?? 'unit',
                        'minimum_stock' => 0,
                        'current_stock' => 0,
                    ]
                );

                $supplierItem->update(['item_id' => $item->id]);

            } else {
                $item = $supplierItem->item;
            }
                // 🔥 هذا منطق المورد فقط
            if ($invoice->type === 'supplier') {
                $item->increment('current_stock', $row['quantity']);
            }

           // $item->increment('current_stock', $row['quantity']);

            $subtotal = $row['quantity'] * $row['purchase_price'];
            $total += $subtotal;

            Invoice_Item::create([
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'description' => $item->name,
                'quantity' => $row['quantity'],
                'unit_price' => $row['purchase_price'],
                'subtotal' => $subtotal,
            ]);
  if ($invoice->type === 'supplier') {
            InventoryTransaction::create([
                'item_id' => $item->id,
                'supplier_id' => $supplier->id,
                'quantity' => $row['quantity'],
                'purchase_price' => $row['purchase_price'],
                'type' => 'in',
                'issued_at' => now(),
            ]);
        }}
  // 🔥 تحديث المجموع فقط للمورد
        if ($invoice->type === 'supplier') {
        $invoice->update([
            'total_amount_USD' => $total,
            'total_amount_SYP' => $total * $exchangeRate
        ]);
        }
        event(new InvoiceCreated($invoice));

        return $invoice->load('items.item', 'supplier');
    });
}

//صرف مواد 
public function consumeMultiple(array $data)
{
    return DB::transaction(function () use ($data) {

        $transactions = [];

        foreach ($data['items'] as $entry) {

            $item = Item::findOrFail($entry['item_id']);

            if ($item->current_stock < $entry['quantity']) {
                throw new \Exception("Not enough stock for item: " . $item->name);
            }

            // 📉 نقص المخزون
            $item->decrement('current_stock', $entry['quantity']);

            // 🔄 نعمل refresh لنجيب القيمة الجديدة
            $item->refresh();

            // 🧠 تحقق من الحد الأدنى
            if ($item->current_stock <= $item->minimum_stock) {
                 event(new LowStockDetected($item));

                // // ✅ 1. خزّن إشعار بالداتابيس
                // Notification::create([
                //     'title' => 'Low Stock Alert',
                //     'body' => "المادة {$item->name} وصلت للحد الأدنى",
                //     'type' => 'low_stock',
                //     'data' => json_encode([
                //         'item_id' => $item->id,
                //         'current_stock' => $item->current_stock
                //     ])
                // ]);

                // // ✅ 2. ابعت Firebase (جاهز للفرونت)
                // Http::withHeaders([
                //     'Authorization' => 'key=FIREBASE_SERVER_KEY',
                //     'Content-Type' => 'application/json',
                // ])->post('https://fcm.googleapis.com/fcm/send', [
                //     'to' => '/topics/warehouse',
                //     'notification' => [
                //         'title' => 'تنبيه مخزون',
                //         'body' => "⚠️ {$item->name} قربت تخلص (باقي {$item->current_stock})",
                //     ],
                //     'data' => [
                //         'type' => 'low_stock',
                //         'item_id' => $item->id
                //     ]
                // ]);
            }

            // 🧾 تسجيل الحركة
            $transactions[] = InventoryTransaction::create([
                'item_id' => $item->id,
                //'session_id' => $data['treatment_session_id'],
                'doctor_id' => $data['doctor_id'],
                'quantity' => $entry['quantity'],
                'type' => 'out',
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return $transactions;
    });

}
//ارجاع مواد 
public function returnItems(array $data)
{
    return DB::transaction(function () use ($data) {

        $transactions = [];

        foreach ($data['items'] as $entry) {

            $item = Item::findOrFail($entry['item_id']);

            // 🧠 1. احسب الكمية المصروفة
            $consumed = InventoryTransaction::where('doctor_id', $data['doctor_id'])
                ->where('item_id', $item->id)
                ->where('type', 'out')
                ->sum('quantity');

            // 🧠 2. احسب المرتجع
            $returned = InventoryTransaction::where('doctor_id', $data['doctor_id'])
                ->where('item_id', $item->id)
                ->where('type', 'return')
                ->sum('quantity');

            $availableToReturn = $consumed - $returned;

            // ❌ تحقق
            if ($entry['quantity'] > $availableToReturn) {
                throw new \Exception("Return exceeds consumed quantity for item: " . $item->name);
            }

            // 📈 3. زيادة المخزون
            $item->increment('current_stock', $entry['quantity']);
            $item->refresh();

            // 🔔 4. إذا رجعت الكمية فوق الحد الأدنى → فك التنبيه
            if ($item->current_stock > $item->minimum_stock) {
                $item->update([
                    'low_stock_notified' => false
                ]);
            }

            // 🧾 5. تسجيل الحركة
            $transactions[] = InventoryTransaction::create([
                'item_id' => $item->id,
                'doctor_id' => $data['doctor_id'],
                'quantity' => $entry['quantity'],
                'type' => 'return',
                'issued_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // 🔔 6. إشعار (اختياري)
            Notification::create([
                'title' => 'Items Returned',
                'body' => "تم إرجاع {$entry['quantity']} من {$item->name}",
                'type' => 'return',
                'data' => json_encode([
                    'item_id' => $item->id
                ])
            ]);
        }

        return $transactions;
    });
}

public function approveRequest($requestId, $itemsInput = null)
{
    return DB::transaction(function () use ($requestId, $itemsInput) {

        $request = MaterialRequest::with('items.item')->findOrFail($requestId);

        if ($request->status !== 'pending') {
            throw new \Exception('Request already processed');
        }

        $consumeData = [
            'doctor_id' => $request->doctor_id,
            'notes' => 'صرف من طلب #' . $request->id,
            'items' => []
        ];

        foreach ($request->items as $row) {

            // 🔥 إذا ما في itemsInput → موافقة كاملة
            if (!$itemsInput) {

                $approvedQty = $row->quantity;

            } else {

                // 🔍 دور على هالعنصر بالـ input
                $input = collect($itemsInput)
                    ->firstWhere('item_id', $row->item_id);

                // ❌ إذا مو موجود → مرفوض
                if (!$input) {
                    $row->update([
                        'status' => 'rejected',
                        'approved_quantity' => 0
                    ]);
                    continue;
                }

                $approvedQty = $input['approved_quantity'] ?? $row->quantity;

                if ($approvedQty <= 0) {
                    $row->update([
                        'status' => 'rejected',
                        'approved_quantity' => 0
                    ]);
                    continue;
                }
            }

            // 🔥 تحقق من المخزون
            if ($row->item->current_stock < $approvedQty) {
                throw new \Exception("Not enough stock for {$row->item->name}");
            }

            // ✅ تحديث
            $row->update([
                'status' => 'approved',
                'approved_quantity' => $approvedQty
            ]);

            // 🔥 حضر للصرف
            $consumeData['items'][] = [
                'item_id' => $row->item_id,
                'quantity' => $approvedQty
            ];
        }

        // 🔥 صرف فعلي
        if (!empty($consumeData['items'])) {
            $this->consumeMultiple($consumeData);
        }

        // 🧠 تحديد حالة الطلب
        $approvedCount = $request->items()->where('status', 'approved')->count();

        if ($approvedCount == 0) {
            $request->status = 'rejected';
        } elseif ($approvedCount == $request->items->count()) {
            $request->status = 'approved';
        } else {
            $request->status = 'approved'; // 🔥 مهم جداً
        }
        $request->save();

        return $request->load('items.item');
    });
}
}