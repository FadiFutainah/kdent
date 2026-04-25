<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
     protected $fillable = [
        'item_id',
       // 'treatment_session_id',
        'doctor_id',
        'supplier_id',
        'type',
        'quantity',
        'transaction_date',
        'purchase_price',
        'notes'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
