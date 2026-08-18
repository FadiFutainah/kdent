<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class SalaryPayment extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'salary_payments';                                                                                   

     protected $fillable = [
        'user_id',
        'paid_by',
        'exchange_rate_id',
        'base_amount_usd',
        'bonus_total_usd',
        'deduction_total_usd',
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
