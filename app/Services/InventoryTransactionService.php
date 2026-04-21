<?php
namespace App\Services;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryTransactionService
{
    // public function purchase(array $data)
    // {
    //     return DB::transaction(function () use ($data) {

    //         // 🔍 جلب المادة
    //         $item = Item::findOrFail($data['item_id']);

    //         // 📦 تحديث المخزون
    //         $item->increment('current_stock', $data['quantity']);

    //         // 🧾 تسجيل العملية مع السعر اللحظي
    //         $transaction = InventoryTransaction::create([
    //             'item_id' => $item->id,
    //             'supplier_id' => $data['supplier_id'],
    //             'quantity' => $data['quantity'],

    //             // 💥 السعر وقت الشراء (snapshot)
    //             'purchase_price' => $data['purchase_price'],

    //             'type' => 'in',
    //             'notes' => $data['notes'] ?? null,
    //         ]);

    //         return $transaction;
    //     });
    // }
  

// public function purchase(array $data)
// {
//     return DB::transaction(function () use ($data) {

//         $supplier = Supplier::findOrFail($data['supplier_id']);

//         // 🔍 تحقق هل المورد يملك هذه المادة
//         $allowed = $supplier->items()
//             ->where('item_id', $data['item_id'])
//             ->exists();

//         if (!$allowed) {
//             throw new \Exception("Supplier does not provide this item.");
//         }

//         // ✔ متابعة الشراء
//         $item = Item::findOrFail($data['item_id']);

//         $item->increment('current_stock', $data['quantity']);

//         return InventoryTransaction::create([
//             'item_id' => $item->id,
//             'supplier_id' => $supplier->id,
//             'quantity' => $data['quantity'],
//             'purchase_price' => $data['purchase_price'],
//             'type' => 'in',
//         ]);
//     });
// }
public function purchaseBulk(array $data)
{
    return DB::transaction(function () use ($data) {

        $supplier = Supplier::findOrFail($data['supplier_id']);

        $results = [];
        $total = 0;

        foreach ($data['items'] as $row) {

            // 🔍 تحقق أن المورد يبيع المادة
            $allowed = $supplier->items()
                ->where('item_id', $row['item_id'])
                ->exists();

            if (!$allowed) {
                throw new \Exception("Supplier does not provide item ID: {$row['item_id']}");
            }

            $item = Item::findOrFail($row['item_id']);

            // ✔ تحديث المخزون
            $item->increment('current_stock', $row['quantity']);

            $subtotal = $row['quantity'] * $row['purchase_price'];
            $total += $subtotal;

            // 🧾 تسجيل حركة
            $results[] = InventoryTransaction::create([
                'item_id' => $item->id,
                'supplier_id' => $supplier->id,
                'quantity' => $row['quantity'],
                'purchase_price' => $row['purchase_price'],
                'type' => 'in',
                'notes' => $data['notes'] ?? null,
            ]);
        }

        // 💰 (اختياري) هون لاحقاً تعمل Purchase Invoice Table
        return [
            // 'transactions' => $results,
            'total_cost' => $total
        ];
    });
}
// صرف مواد لجلسة محددة
public function consumeMultiple(array $data)
{
    return DB::transaction(function () use ($data) {

        $transactions = [];

        foreach ($data['items'] as $entry) {

            $item = Item::findOrFail($entry['item_id']);

            // ❌ تحقق من الكمية
            if ($item->current_stock < $entry['quantity']) {
                throw new \Exception("Not enough stock for item: " . $item->name);
            }

            // 📉 نقص المخزون
            $item->decrement('current_stock', $entry['quantity']);

            // 🧾 سجل الحركة
            $transactions[] = InventoryTransaction::create([
                'item_id' => $item->id,
                'session_id' => $data['session_id'],
                'quantity' => $entry['quantity'],
                'type' => 'out',
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return $transactions;
    });
}
}