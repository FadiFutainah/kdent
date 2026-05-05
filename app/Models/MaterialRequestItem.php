<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
      protected $fillable = [
        'material_request_id',
        'item_id',
        'quantity',
        'approved_quantity',
        'status'
    ];

    // 🔗 الطلب
    public function request()
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    // 📦 المادة
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
