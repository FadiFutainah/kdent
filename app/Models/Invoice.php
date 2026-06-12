<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use FixJsonDateFormat;
    //protected $appends = []; // لكي تظهر النسبة تلقائياً في الريسبونس
    protected $hidden = ['patient'];
    protected $appends = ['patient_name','paid_percent','final_price'];
    protected $casts = [
    'last_reminder_sent_at' => 'datetime',
    'issued_at'             => 'datetime',
];
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
        'exchange_rate',
        'created_by',
        'status',
        'notes',
        'issued_at',
        'last_reminder_sent_at',
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

// تعريف الـ Attribute الذي سيجلب الاسم
    public function getPatientNameAttribute()
    {
        // نصل للاسم من المريض ثم المستخدم، وإذا لم يوجد نرجع '-'
        return $this->patient?->user?->name ?? 'غير معروف';
    }
// التابع المركزي للسعر (يغنيكِ عن تكرار شرط الخصم)
    public function getFinalPriceAttribute()
    {
        return ($this->total_amount_USD_after_discount > 0) 
               ? $this->total_amount_USD_after_discount 
               : $this->total_amount_USD;
    }

    // التابع المركزي لنسبة السداد
    public function getPaidPercentAttribute()
    {
        $finalAmount = $this->final_price;
        if ($finalAmount <= 0) return 100;
        
        return (int) round(($this->paid_amount / $finalAmount) * 100);
    }

}
