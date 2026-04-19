<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment_Session extends Model
{
    protected $table = 'treatment_sessions';

    protected $fillable = [
        'plan_item_id',
        'doctor_id',
        'appointment_id',
        'patient_id',
        'rprice_usd',
        'rprice_syp',
        'session_date',
        'status',
        'clinical_notes',
        'is_last_session',
    ];

    protected $casts = [
        'session_date' => 'datetime',
        'is_last_session' => 'boolean',
        'session_type' => 'string',
    ];

    public function planItem()
    {
        return $this->belongsTo(Plan_Item::class, 'plan_item_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

}
