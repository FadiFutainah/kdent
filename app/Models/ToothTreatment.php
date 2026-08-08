<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToothTreatment extends Model
{
    protected $table = 'tooth_treatments';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'tooth_number',
        'status',
        'treatment_type',
        'selected_surfaces',
        'notes',
    ];

    protected $casts = [
        'tooth_number' => 'integer',
        'selected_surfaces' => 'array',
    ];

    // علاجات الأسنان الدائمة (الكبار)
    public const ADULT_TREATMENT_TYPES = [
        'حشوة',
        'سحب عصب',
        'تاج',
        'قلع ',
        'زراعة',
    ];

    // علاجات الأسنان اللبنية (الأطفال)
    public const CHILD_TREATMENT_TYPES = [
        'حشوة',
        'بترعصب',
        'استئصال عصب',
        'تاج أطفال',
        'قلع',
        'حافظ مسافة',
    ];
    public const SURFACES = [
    'أنسي',
    'وحشي',
    'إطباقي',
    'دهليزي',
    'لساني',
];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * بيرجع true إذا كان السن لبني (طفل) حسب الترقيم الدولي FDI
     */
    public static function isChildToothNumber(int $toothNumber): bool
    {
        return $toothNumber >= 51 && $toothNumber <= 85;
    }

    /**
     * قائمة أنواع المعالجة المسموحة حسب نوع السن (لبني/دائم)
     */
    public static function allowedTreatmentTypesFor(int $toothNumber): array
    {
        return self::isChildToothNumber($toothNumber)
            ? self::CHILD_TREATMENT_TYPES
            : self::ADULT_TREATMENT_TYPES;
    }

    /**
     * كل أرقام الأسنان الصحيحة حسب معيار FDI (32 دائم + 20 لبني)
     */
    public static function validToothNumbers(): array
    {
        $numbers = [];

        // الكبار: 4 أرباع × 8 أسنان (11-18, 21-28, 31-38, 41-48)
        foreach ([1, 2, 3, 4] as $quadrant) {
            for ($i = 1; $i <= 8; $i++) {
                $numbers[] = ($quadrant * 10) + $i;
            }
        }

        // الأطفال: 4 أرباع × 5 أسنان (51-55, 61-65, 71-75, 81-85)
        foreach ([5, 6, 7, 8] as $quadrant) {
            for ($i = 1; $i <= 5; $i++) {
                $numbers[] = ($quadrant * 10) + $i;
            }
        }

        return $numbers;
    }

}
