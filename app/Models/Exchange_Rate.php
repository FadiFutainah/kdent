<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange_Rate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'fetched_at' => 'datetime',
    ];

    public function treatmentSessions()
    {
        return $this->hasMany(Treatment_Session::class, 'exchange_rate_id');
    }

    public function doctorEarnings()
    {
        return $this->hasMany(Doctor_Earning::class, 'exchange_rate_id');
    }
    public function doctorPayments()
    {
        return $this->hasMany(Doctor_Payment::class, 'exchange_rate_id');
    }
}
