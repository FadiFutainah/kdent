<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medical_Report extends Model
{
    protected $table = 'medical_reports';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'report_date',
        'content',
        'attachments',
    ];

    protected $casts = [
        'report_date' => 'datetime',
        'attachments' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

}
