<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class doctor extends Model
{
     protected $fillable = [
        'user_id',
        'specialization_id',
        'is_active'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function schedules()
    {
        return $this->hasMany(Doctor_Schedules::class);
    }

    public function treatmentPlans()
    {
        return $this->hasMany(Treatment_Plan::class, 'doctor_id');
    }

    public function treatmentSessions()
    {
        return $this->hasMany(Treatment_Session::class, 'doctor_id');
    }

}
