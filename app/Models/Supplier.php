<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
     protected $fillable = ['name', 'phone', 'notes'];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    public function supplierItems()
{
    return $this->hasMany(SupplierItem::class);
}
public function invoices()
{
    return $this->hasMany(Invoice::class);
}
// public function items()
// {
//     return $this->belongsToMany(Item::class, 'supplier_items');
// }
}
