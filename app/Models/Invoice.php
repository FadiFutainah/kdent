<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class invoice extends Model
{
     protected $fillable = [
        'type',
        'supplier_id',
        'patient_id',
        'plan_id',
        'total_amount_USD',
        'total_amount_SYP',
        'invoice_number',
        'paid_amount',
        'discount',
        'total_amount_USD_after_discount',
        'total_amount_SYP_after_discount',
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
      public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function plan()
    {
        return $this->belongsTo(Plan_Item::class);
    }
    public function getTotalSypAttribute()
{
    return $this->total_amount * $this->exchange_rate;
}
public function payments()
{
    return $this->hasMany(Payment::class);
}
public function plans()
{
    return $this->belongsTo(Treatment_Plan::class, 'plan_id');
}

}
