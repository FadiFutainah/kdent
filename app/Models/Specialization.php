<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class Specialization extends Model implements Auditable
{
    use FixJsonDateFormat;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'specializations';
    protected $fillable = ['name','description'];

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
    
public function activeDoctor()
{
    return $this->hasOne(Doctor::class)
        ->where('is_active', true);
}
}
