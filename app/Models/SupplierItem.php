<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class SupplierItem extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'supplier_items';
    protected $fillable = [
        'supplier_id',
        'item_id',
        'name',
        'unit',
    ];
    public function item()
{
    return $this->belongsTo(Item::class);
}

public function supplier()
{
    return $this->belongsTo(Supplier::class);
}
}
