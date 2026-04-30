<?php
namespace App\Services;

use App\Models\Supplier;
use App\Models\Item;
use App\Models\SupplierItem;
use Illuminate\Support\Facades\DB;

class SupplierService
{
//تثبيت المواد في النظام
//  public function createItem(array $data)
//     {
//         return Item::create([
//             'name' => $data['name'],

//             // 💥 الكود الأساسي للمادة
//             'code' => strtoupper($data['code']),

//             'unit' => $data['unit'],

//             // الحد الأدنى للمخزون
//             'minimum_stock' => $data['minimum_stock'],

//             // يبدأ صفر دائمًا
//             'current_stock' => 0,

//             'is_active' => true,
//         ]);
//     }
//تثبيت المواد كدفعة وحدة
public function createBulkItems(array $items)
{
    return DB::transaction(function () use ($items) {

        $created = [];

        foreach ($items as $item) {
            $created[] = Item::create([
                'name' => $item['name'],
                'code' => $item['code'],
                'unit' => $item['unit'],
                'minimum_stock' => $item['minimum_stock'],
                'current_stock' => 0,
                'is_active' => true,
            ]);
        }

        return $created;
    });
}
 public function createSupplierWithItems(array $data)
    {
        $supplier = Supplier::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $row) {

            // ✔ مادة موجودة
            if (!empty($row['item_id'])) {

                $item = Item::findOrFail($row['item_id']);

                SupplierItem::create([
                    'supplier_id' => $supplier->id,
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'unit' => $item->unit,
                ]);

            } else {
                // 🆕 مادة جديدة

                SupplierItem::create([
                    'supplier_id' => $supplier->id,
                    'item_id' => null,
                    'name' => $row['name'],
                    'unit' => $row['unit'] ?? 'unit',
                ]);
            }
        }

        return $supplier->load('supplierItems');
    }
    

// public function createSupplierWithItems(array $data)
// {
//     $supplier = Supplier::create([
//         'name' => $data['name'],
//         'phone' => $data['phone'] ?? null,
//         'notes' => $data['notes'] ?? null,
//     ]);

//     foreach ($data['items'] as $row) {

//         // إذا المادة موجودة
//         if (isset($row['item_id'])) {

//             $item = Item::findOrFail($row['item_id']);

//             SupplierItem::create([
//                 'supplier_id' => $supplier->id,
//                 'item_id' => $item->id,
//                 'name' => $item->name,
//                 'unit' => $item->unit,
//             ]);

//         } else {
//             // مادة جديدة من المورد

//             SupplierItem::create([
//                 'supplier_id' => $supplier->id,
//                 'item_id' => null,
//                 'name' => $row['name'],
//                 'unit' => $row['unit'] ?? 'unit',
//             ]);
//         }
//     }

//     return $supplier->load('supplierItems');
// }
    //عرض المواد الموجودة 
    public function getAvailableItems()
{
    return Item::select([
            'id',
            'name',
            'code',
            'unit',
            'current_stock'
        ])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
}


}