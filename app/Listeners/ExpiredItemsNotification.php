<?php

namespace App\Listeners;

use App\Events\ExpiredItemsDetected;
use App\Jobs\SendNotificationJob;
use App\Models\User;

class ExpiredItemsNotification
{
    public function handle(ExpiredItemsDetected $event): void
    {
        $warehouseManagers = User::role('storekeeper')
            ->pluck('id')
            ->toArray();

        if (empty($warehouseManagers)) {
            return;
        }

        SendNotificationJob::dispatch(
            $warehouseManagers,
            'مواد منتهية الصلاحية',
            'يوجد مواد منتهية الصلاحية في المخزن وتحتاج إلى المعالجة.',
            'expired_material',
            [
                'disposal_id' => $event->disposal->id,
            ]
        );
    }
}