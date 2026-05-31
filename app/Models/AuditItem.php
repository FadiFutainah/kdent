<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditItem extends Model
{
     protected $fillable = [
        'audit_id',
         'item_id', 
         'batch_number', 
         'quantity_expected',
          'quantity_actual', 
          'variance_reason', 
          'notes'];
    
    public function audit()
     { return $this->belongsTo(Audit::class); }
    public function item()
    { return $this->belongsTo(Item::class); }
}
