<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class invoice extends Model
{
     protected $fillable = [
        'type',
        'supplier_id',
        'total_amount_USD',
        'total_amount_SYP',
        'discount',
        'currency',
        'exchange_rate',
        'created_by',
        'status',
        'notes',
        'pdf_url',
        'issued_at'
    ];

    public function items()
    {
        return $this->hasMany(Invoice_Item::class);
    }
    public function getTotalSypAttribute()
{
    return $this->total_amount * $this->exchange_rate;
}
}
