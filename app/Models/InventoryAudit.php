<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class InventoryAudit extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
protected $table = 'inventory_audits';
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
