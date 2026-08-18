<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Treatment_Category extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'treatment_categories';

    protected $fillable = [
        'name',
        'price_usd',
    ];

    public function planItems()
    {
        return $this->hasMany(Plan_Item::class, 'category_id');
    }
    
}