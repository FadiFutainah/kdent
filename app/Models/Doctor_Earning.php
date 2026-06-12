<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor_Earning extends Model
{
    use FixJsonDateFormat;
    protected $table = 'doctor_earnings';

    protected $fillable = [
        'doctor_id',
        'treatment_plans_id',
        'exchange_rate_id',
        'percentage',
        'amount_usd',
        'amount_syp',
        'earning_date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(Treatment_Plan::class, 'treatment_plans_id');
    }

    public function exchangeRate()
    {
        return $this->belongsTo(Exchange_Rate::class, 'exchange_rate_id');
    }

}
