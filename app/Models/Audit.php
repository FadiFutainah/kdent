<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use FixJsonDateFormat;
protected $table = 'audits';
     protected $fillable = [
        'audit_number',
         'audit_date', 
         'status', 
         'total_items',
          'total_variance', 
          'notes',
           'conducted_by', 
           'approved_by'];
    
    public function items()
     { return $this->hasMany(AuditItem::class); }
     
    public function movements()
    { return $this->hasMany(InventoryTransaction::class, 'reference_id')->where('reference_type', 'Audit'); }
}
