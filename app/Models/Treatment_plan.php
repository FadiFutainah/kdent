<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment_Plan extends Model
{
    protected $table = 'treatment_plans';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'name',
        'start_date',
        'notes',
    ];

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
}