<?php
namespace App\Services;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Invoice_Item;
use Illuminate\Support\Facades\DB;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Http;
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

            // 💱 سعر الصرف
            $rateModel = $this->exchangeService->getCurrentUsdToSypRate();
            $exchangeRate = $rateModel->rate;

            // 🧾 إنشاء الفاتورة
            $invoice = Invoice::create([
                'type' => 'supplier',
                'supplier_id' => $supplier->id,
                'discount' => 0,
                'exchange_rate' => $exchangeRate,
                'status' => 'draft',
                'issued_at' => $data['issued_at'] ?? now(),
            ]);

            $total = 0;

            foreach ($data['items'] as $row) {

                // 🔍 جلب مادة المورد
                $supplierItem = SupplierItem::where('supplier_id', $supplier->id)
                    ->where('id', $row['supplier_item_id'])
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | 🧠 إذا المادة غير موجودة بالمخزن → أنشئها
                |--------------------------------------------------------------------------
                */
                if (!$supplierItem->item_id) {

                    // 🔍 منع التكرار
                    $item = Item::where('name', $supplierItem->name)->first();

                    if (!$item) {
                        $item = Item::create([
                            'name' => $supplierItem->name,
                            'code' => strtoupper(uniqid('ITEM_')),
                            'unit' => $supplierItem->unit ?? 'unit',
                            'minimum_stock' => 0,
                            'current_stock' => 0,
                        ]);
                    }

                    // 🔗 ربطها بالمورد
                    $supplierItem->update([
                        'item_id' => $item->id
                    ]);

                } else {
                    $item = $supplierItem->item;
                }

                /*
                |--------------------------------------------------------------------------
                | 📦 تحديث المخزون
                |--------------------------------------------------------------------------
                */
                $item->increment('current_stock', $row['quantity']);

                $subtotal = $row['quantity'] * $row['purchase_price'];
                $total += $subtotal;

                /*
                |--------------------------------------------------------------------------
                | 🧾 عناصر الفاتورة
                |--------------------------------------------------------------------------
                */
                Invoice_Item::create([
                    'invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'description' => $item->name,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['purchase_price'],
                    'subtotal' => $subtotal,
                ]);

                /*
                |--------------------------------------------------------------------------
                | 📊 تسجيل الحركة
                |--------------------------------------------------------------------------
                */
                InventoryTransaction::create([
                    'item_id' => $item->id,
                    'supplier_id' => $supplier->id,
                    'quantity' => $row['quantity'],
                    'purchase_price' => $row['purchase_price'],
                    'type' => 'in',
                    'issued_at' => now(),
                ]);
              
            }

            /*
            |--------------------------------------------------------------------------
            | 💰 تحديث الفاتورة
            |--------------------------------------------------------------------------
            */
            $invoice->update([
                'total_amount_USD' => $total,
                'total_amount_SYP' => $total * $exchangeRate
            ]);

            return $invoice->load('items');
        });
          event(new InvoiceCreated($invoice));
    }
// شراء مواد مع إنشاء فاتورة
//  public function purchaseBulk(array $data)
//     {
//         return DB::transaction(function () use ($data) {

//             $supplier = Supplier::findOrFail($data['supplier_id']);

//             // 💱 هون السحر 👇
//             $rateModel = $this->exchangeService->getCurrentUsdToSypRate();

//             $exchangeRate = $rateModel->rate;

//             // 🧾 إنشاء الفاتورة
//             $invoice = Invoice::create([
//                 'type' => 'supplier',
//                 'supplier_id' => $supplier->id,
//                 'discount' => 0,
//                 //'currency' => 'USD',
//                 'exchange_rate' => $exchangeRate,
//                 //'created_by' => auth()->id(),
//                 'status' => 'draft',
//                 'issued_at' => $data['issued_at'] ?? now(),
//             ]);

//             $total = 0;

//             foreach ($data['items'] as $row) {

//                 $allowed = $supplier->items()
//                     ->where('item_id', $row['item_id'])
//                     ->exists();

//                 if (!$allowed) {
//                     throw new \Exception("Supplier does not provide item ID: {$row['item_id']}");
//                 }

//                 $item = Item::findOrFail($row['item_id']);

//                 $item->increment('current_stock', $row['quantity']);

//                 $subtotal = $row['quantity'] * $row['purchase_price'];
//                 $total += $subtotal;

//                 Invoice_Item::create([
//                     'invoice_id' => $invoice->id,
//                     'item_id' => $item->id,
//                     'description' => $item->name,
//                     'quantity' => $row['quantity'],
//                     'unit_price' => $row['purchase_price'],
//                     'subtotal' => $subtotal,
//                 ]);

//                 InventoryTransaction::create([
//                     'item_id' => $item->id,
//                     'supplier_id' => $supplier->id,
//                     'quantity' => $row['quantity'],
//                     'purchase_price' => $row['purchase_price'],
//                     'type' => 'in',
//                 ]);
//             }

//             $invoice->update([
//                 'total_amount_USD' => $total,
//                 'total_amount_SYP' => $total * $exchangeRate
//             ]);

//             return $invoice->load('items');
//         });
//     }

// صرف مواد لجلسة محددة
// public function consumeMultiple(array $data)
// {
//     return DB::transaction(function () use ($data) {

//         $transactions = [];

//         foreach ($data['items'] as $entry) {

//             $item = Item::findOrFail($entry['item_id']);

//             // ❌ تحقق من الكمية
//             if ($item->current_stock < $entry['quantity']) {
//                 throw new \Exception("Not enough stock for item: " . $item->name);
//             }

//             // 📉 نقص المخزون
//             $item->decrement('current_stock', $entry['quantity']);

//             // 🧾 سجل الحركة
//             $transactions[] = InventoryTransaction::create([
//                 'item_id' => $item->id,
//                 'session_id' => $data['session_id'],
//                 'quantity' => $entry['quantity'],
//                 'type' => 'out',
//                 'notes' => $data['notes'] ?? null,
//             ]);
//         }

//         return $transactions;
//     });
// }
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
}