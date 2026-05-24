<?php
namespace App\Services;

use App\Models\Supplier;
use App\Models\Item;
use App\Models\SupplierItem;
use Illuminate\Support\Facades\DB;

class SupplierService
{

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
                'max_stock' => $item['max_stock'],
                //'reorder_point' => $item['reorder_point'] ?? 0,
                //'current_stock' => 0,
                'is_active' => true,
            ]);
        }

        return $created;
    });
}

//  public function createSupplierWithItems(array $data)
//     {
//         $supplier = Supplier::create([
//             'name' => $data['name'],
//             'phone' => $data['phone'] ?? null,
//             'notes' => $data['notes'] ?? null,
//         ]);

//         foreach ($data['items'] as $row) {

//             // ✔ مادة موجودة
//             if (!empty($row['item_id'])) {

//                 $item = Item::findOrFail($row['item_id']);

//                 SupplierItem::create([
//                     'supplier_id' => $supplier->id,
//                     'item_id' => $item->id,
//                     'name' => $item->name,
//                     'unit' => $item->unit,
//                     'code' => $item->code,
//                 ]);

//             } else {
//                 // 🆕 مادة جديدة

//                 SupplierItem::create([
//                     'supplier_id' => $supplier->id,
//                     'item_id' => null,
//                     'name' => $row['name'],
//                     'unit' => $row['unit'] ?? 'unit',
//                     'code' => $row['code'] ?? null,
//                 ]);
//             }
//         }

//         return $supplier->load('supplierItems');
//     }
    
// في ملف SupplierService.php
public function createSupplierWithItems(array $data)
{
    return DB::transaction(function () use ($data) {
        $supplier = Supplier::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $row) {

            if (!empty($row['item_id'])) {
                // ✔ مادة موجودة مسبقاً
                $item = Item::findOrFail($row['item_id']);
            } else {
                // 🆕 مادة جديدة تماماً - يتم إنشاؤها فوراً بحدودها المخزنية
                $item = Item::create([
                    'name' => $row['name'],
                    'code' => $row['code'] ?? strtoupper(uniqid('ITEM_')),
                    'unit' => $row['unit'] ?? 'unit',
                    'minimum_stock' => $row['minimum_stock'] ?? 0, // 💡 هنا تم الاستقبال
                    'max_stock' => $row['max_stock'] ?? 0,         // 💡 هنا تم الاستقبال
                    'is_active' => true,
                ]);
            }

            // ربط المادة بالمورد (الآن item_id لن يكون null أبداً)
            SupplierItem::create([
                'supplier_id' => $supplier->id,
                'item_id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'code' => $item->code,
            ]);
        }

        return $supplier->load('supplierItems');
    });
}
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