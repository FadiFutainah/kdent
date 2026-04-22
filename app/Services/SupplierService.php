<?php
namespace App\Services;

use App\Models\Supplier;
use App\Models\Item;
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
//اضافة موردين الى النظام
// public function createSupplierWithItems(array $data)
// {
//     return DB::transaction(function () use ($data) {

//         $supplier = Supplier::create([
//             'name' => $data['name'],
//             'phone' => $data['phone'] ?? null,
//         ]);

//         if (!empty($data['items'])) {
//             foreach ($data['items'] as $item) {
//                 $supplier->supplierItems()->create([
//                     'name' => $item['name'],
//                     'unit' => $item['unit'] ?? null,
//                 ]);
//             }
//         }

//         return $supplier->load('supplierItems');
//     });
// }
 public function createSupplierWithItems(array $data)
    {
        // 🏗 إنشاء المورد
        $supplier = Supplier::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // 🔗 ربط المواد
        $supplier->items()->sync($data['items']);

        return $supplier->load('items');
    }
}