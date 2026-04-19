<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor_Earning extends Model
{
    protected $table = 'doctor_earnings';

    protected $fillable = [
        'doctor_id',
        'treatment_session_id',
        'percentage',
        'amount_usd',
        'amount_syp',
        'earning_date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function treatmentSession()
    {
        return $this->belongsTo(Treatment_Session::class, 'treatment_session_id');
    }

}
