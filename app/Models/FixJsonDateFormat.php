<?php

namespace App\Models;

use DateTimeInterface;

trait FixJsonDateFormat
{
    /**
     * تخصيص صيغة الوقت عند تحويل أي موديل إلى JSON
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}