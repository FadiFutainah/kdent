<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor_Schedule extends Model
{
    use FixJsonDateFormat;
    protected $table = 'doctor_schedules';
    protected $fillable = [
        'doctor_id',
        'day',
        'start_time',
        'end_time',
        
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
