<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryAdjustment extends Model
{
     protected $fillable = [
        'user_id',
        'created_by',
        'type',
        'amount_usd',
        'reason',
        'salary_month',
        'salary_payment_id',
    ];

    protected $casts = [
        'salary_month' => 'date:Y-m-d',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salaryPayment()
    {
        return $this->belongsTo(SalaryPayment::class);
    }
}
