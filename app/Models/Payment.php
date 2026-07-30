<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Payment extends Model implements Auditable
{
    use FixJsonDateFormat;
    // use \OwenIt\Auditing\Auditable;   // ← هاد بيفعّل التسجيل التلقائي
 use AuditableTrait;
    protected $table = 'payments';
     protected $appends = ['paid_at'];
    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'exchange_rate',
        'created_by'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function user()
{
    return $this->belongsTo(User::class, 'created_by');
}

    public function getPaidAtAttribute()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

}
