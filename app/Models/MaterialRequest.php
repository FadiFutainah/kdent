<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use FixJsonDateFormat;
    protected $table = 'material_requests';
    protected $fillable = [
'requisition_number',
        'doctor_id',
        'status',
        'notes',
        'requested_date', 
        'withdrawn_date', 
       // 'fulfilled_date', 
       // 'total_items', 
        'requested_by',
       // 'approved_by',
        //'fulfilled_by'
    ];

    // 👨‍⚕️ الدكتور صاحب الطلب
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    // 📦 عناصر الطلب
    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
    ///////////////////////////////////////
     public function movements()
      { return $this->hasMany(InventoryTransaction::class, 'reference_id')->where('reference_type', 'Requisition'); }
}
