<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
     protected $fillable = [
        'name',
        'code',
        'unit',
        'minimum_stock',
        'current_stock',
        'is_active'
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    // public function supplierItems()
    // {
    //     return $this->hasMany(supplier_items::class);
    // }
    public function suppliers()
{
    return $this->belongsToMany(Supplier::class, 'supplier_items');
}

}
