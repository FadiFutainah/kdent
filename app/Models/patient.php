<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class patient extends Model
{
      protected $fillable = [
        'user_id',
        'gender',
        'address',
        'occupation',
        'file_open_date',

        'medical_history_heart_disease',
        'medical_history_diabetes',
        'medical_history_blood_pressure',
        'medical_history_asthma',
        'medical_history_allergies_meds',
        'medical_history_liver_disease',
        'medical_history_kidney_disease',
        'medical_history_blood_disorders',
        'medical_history_pregnancy',

        'current_medications',
        'known_allergies'
    ];

    protected $casts = [
        'file_open_date' => 'date',

        'medical_history_heart_disease' => 'boolean',
        'medical_history_diabetes' => 'boolean',
        'medical_history_blood_pressure' => 'boolean',
        'medical_history_asthma' => 'boolean',
        'medical_history_allergies_meds' => 'boolean',
        'medical_history_liver_disease' => 'boolean',
        'medical_history_kidney_disease' => 'boolean',
        'medical_history_blood_disorders' => 'boolean',
        'medical_history_pregnancy' => 'boolean',
    ];

  public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatmentPlans()
    {
        return $this->hasMany(Treatment_Plan::class, 'patient_id');
    }
    public function medicalReports()
    {
        return $this->hasMany(Medical_Report::class, 'patient_id');
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'patient_id');
    }
}