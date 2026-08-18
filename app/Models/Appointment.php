<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Appointment extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
protected $table = 'appointments';
       protected $fillable = [
        'patient_id',
        'doctor_id',
        'day',
        'appointment_date',
        'status',
        'reminder_sent',
    ];
  protected $casts = [
        'appointment_date' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    // Relations
    public function patient()
    {
        return $this->belongsTo(Patient::class)
        ->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class)
        ->withTrashed();
    }


    public function treatmentSession()
    {
        return $this->hasOne(Treatment_Session::class, 'appointment_id');
    }
}
