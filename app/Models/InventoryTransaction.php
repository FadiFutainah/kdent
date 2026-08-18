<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class InventoryTransaction extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'inventory_transactions';
     protected $fillable = [
        'item_id',
       // 'treatment_session_id',
        'doctor_id',
        'inventory_id',
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
       // 🔗 الطلب (اختياري لكن مهم)
    public function request()
    {
        return $this->belongsTo(MaterialRequest::class, 'reference_id');
    }
    
}
