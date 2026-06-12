<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment_Plan extends Model
{
    use FixJsonDateFormat;

    protected $table = 'treatment_plans';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'name',
        'start_date',
        'exchange_rate_id',
        'price_usd',
        'price_syp',
        'target_teeth',
        'status',
        'is_locked',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'price_usd' => 'decimal:2',
        'price_syp' => 'decimal:2',
    ];

    protected $appends = [
        'progress_percent',
    ];
    public function invoice()
{
    return $this->hasOne(Invoice::class, 'plan_id');
}

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(Plan_Item::class, 'plan_id');
    }

    public function getProgressPercentAttribute()
    {
        $total = Treatment_Session::whereHas('planItem', function ($q) {
            $q->where('plan_id', $this->id);
        })->count();

        if ($total === 0) {
            return 0;
        }

        $completed = Treatment_Session::whereHas('planItem', function ($q) {
            $q->where('plan_id', $this->id);
        })
            ->where('status', 'completed')
            ->count();

        return (int) round(($completed / $total) * 100);
    }

        public function exchangeRate()
    {
        return $this->belongsTo(Exchange_Rate::class, 'exchange_rate_id');
    }

    public function earning()
    {
        return $this->hasOne(Doctor_Earning::class, 'treatment_plans_id');
    }  

}