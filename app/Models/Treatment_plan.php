<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Plan_Item;

class Treatment_Plan extends Model
{
    protected $table = 'treatment_plans';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'name',
        'start_date',
        'notes',
        'status',
    ];

    protected $appends = [
        'progress_percent',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(Plan_Item::class, 'plan_id');
    }

    public function getProgressPercentAttribute()
    {
        if ($this->relationLoaded('items')) {
            $total = $this->items->count();
            if ($total === 0) {
                return 0;
            }

            $completed = $this->items->where('status', 'completed')->count();
            return (int) round(($completed / $total) * 100);
        }

        $total = Plan_Item::where('plan_id', $this->id)->count();
        if ($total === 0) {
            return 0;
        }

        $completed = Plan_Item::where('plan_id', $this->id)
            ->where('status', 'completed')
            ->count();

        return (int) round(($completed / $total) * 100);
    }
}