<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Exchange_Rate extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'exchange_rates';

    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'fetched_at' => 'datetime',
    ];


    public function doctorEarnings()
    {
        return $this->hasMany(Doctor_Earning::class, 'exchange_rate_id');
    }
    public function doctorPayments()
    {
        return $this->hasMany(Doctor_Payment::class, 'exchange_rate_id');
    }

    public function treatmentPlans()
    {
        return $this->hasMany(Treatment_Plan::class, 'exchange_rate_id');
    }

}
