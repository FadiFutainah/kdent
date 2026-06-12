<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use FixJsonDateFormat;
     protected $fillable = [
        'name',
        'code',
        'unit',
        'minimum_stock',
        'current_stock',
        //'reorder_point',
        'max_stock',
        'is_active'
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    public function supplierItems()
    {
        return $this->hasMany(SupplierItem::class);
    }
//     public function suppliers()
// {
//     return $this->belongsToMany(Supplier::class, 'supplier_items');
// }
  // 📦 علاقته مع طلبات المواد
    public function materialRequestItems()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
public function invoice()
    {
        return $this->hasMany(Invoice_Item::class);
    }
    ////////////////////////////
     public function inventory()
     {
 return $this->hasMany(Inventory::class);
     }

      public function getTotalQuantity(): int
    {
        return $this->inventory->where('is_active', true)->sum('quantity');
    }
    
    public function getAvailableQuantity(): int
    {
        return $this->inventory->where('is_active', true)->sum('quantity_available');
    }
    
    public function getReservedQuantity(): int
    {
        return $this->inventory->where('is_active', true)->sum('quantity_reserved');
    }

}
