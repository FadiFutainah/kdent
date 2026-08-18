<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Inventory extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'inventories';
     protected $fillable = 
     [
        'item_id',
        'batch_number', 
        'quantity', 
        'quantity_reserved',
        'expiry_date', 
        'storage_location', 
        'unit_cost', 
        'supplier_id', 
        'received_date', 
        'is_active'
        ];
    
    public function item() 
    { return $this->belongsTo(Item::class); }
    public function supplier() 
    { return $this->belongsTo(Supplier::class); }
    public function movements()
     { return $this->hasMany(InventoryTransaction::class); }
    
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now()->toDateString();
    }
    public function disposalItems()
{
    return $this->hasMany(DisposalItem::class, 'inventory_id', 'id');
}
    
    public function isExpiringsoon(): bool
    {
       // return $this->expiry_date && $this->expiry_date <= now()->addDays(30)->toDateString();
        return $this->expiry_date &&
           $this->expiry_date >= now()->toDateString() &&
           $this->expiry_date <= now()->addDays(30)->toDateString();
    }
}
