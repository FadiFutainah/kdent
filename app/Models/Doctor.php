<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
     protected $fillable = [
        'user_id',
        'specialization_id',
        'percentage',
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

    public function earnings()
    {
        return $this->hasMany(Doctor_Earning::class, 'doctor_id');
    }   
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'doctor_id');
    }
     // 📦 طلبات المواد
    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'doctor_id');
    }

    public function payments()
    {
        return $this->hasMany(Doctor_Payment::class, 'doctor_id');
    }
    public function medicalReports()
    {
        return $this->hasMany(Medical_Report::class, 'doctor_id');
    }


}
