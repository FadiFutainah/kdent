<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Notification extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'notifications';
      protected $fillable = [
         'user_id',
        'title',
        'body',
        'type',
        'data',
        'read_at',
    ];
      protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
