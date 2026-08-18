<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class DisposalItem extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'disposal_items';
    protected $fillable = [
        'disposal_id', 
        'item_id',
         'batch_number',
          'quantity', 
          'expiry_date', 
          'inventory_id', 
          'reason_details'];
    
    public function disposal()
    { return $this->belongsTo(Disposal::class); }
    public function item()
     { return $this->belongsTo(Item::class); }
    public function inventory()
    { return $this->belongsTo(Inventory::class); }
}
