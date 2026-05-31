<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposal extends Model
{
    protected $fillable = [
        'disposal_number', 
        'disposal_date', 
        'reason', 
        'status', 
        'total_quantity', 
        'notes', 
        'created_by', 
        'approved_by', 
        'executed_by'];
    
    public function items()
     { return $this->hasMany(DisposalItem::class); }
    public function movements()
    { return $this->hasMany(InventoryTransaction::class, 'reference_id')->where('reference_type', 'Disposal'); }
}
