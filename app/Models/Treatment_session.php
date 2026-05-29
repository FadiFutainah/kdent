<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment_Session extends Model
{
    protected $table = 'treatment_sessions';

    protected $fillable = [
        'plan_item_id',
        'appointment_id',
        'name',
        'session_date',
        'status',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function planItem()
    {
        return $this->belongsTo(Plan_Item::class, 'plan_item_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
 

    public function exchangeRate()
    {
        return $this->belongsTo(Exchange_Rate::class, 'exchange_rate_id');
    }

    public function earning()
    {
        return $this->hasOne(Doctor_Earning::class, 'treatment_session_id');
    }  
    public function invoiceItems()
    {
        return $this->hasMany(Invoice_Item::class, 'treatment_session_id');
    } 

}
