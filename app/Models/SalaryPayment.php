<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
     protected $fillable = [
        'user_id',
        'paid_by',
        'exchange_rate_id',
        'amount_usd',
        'amount_syp',
        'salary_month',
        'payment_date',
        'status',
        'notes',
    ];
     protected $casts = [
       'salary_month' => 'date:Y-m-d',
        'payment_date'  => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function exchangeRate()
    {
        return $this->belongsTo(Exchange_Rate::class, 'exchange_rate_id');
    }

}
