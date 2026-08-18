<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Doctor_Schedule extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
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
