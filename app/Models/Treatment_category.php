<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment_Category extends Model
{
    protected $table = 'treatment_categories';

    protected $fillable = [
        'name',
    ];

    public function planItems()
    {
        return $this->hasMany(Plan_Item::class, 'category_id');
    }
    
}