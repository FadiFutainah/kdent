<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    
    protected $fillable = [
        'doctor_id',
        'status',
        'notes'
    ];

    // 👨‍⚕️ الدكتور صاحب الطلب
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    // 📦 عناصر الطلب
    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
}
