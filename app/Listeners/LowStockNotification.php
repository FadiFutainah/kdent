<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Jobs\SendNotificationJob;
use App\Models\User;

class LowStockNotification
{
    public function handle(LowStockDetected $event): void
    {
        $warehouseManagers = User::role('storekeeper')
            ->pluck('id')
            ->toArray();

        if (empty($warehouseManagers)) {
            return;
        }

        $item = $event->item;

        SendNotificationJob::dispatch(
            $warehouseManagers,
            'مادة قاربت على النفاد',
            "المادة {$item->name} وصلت إلى الحد الأدنى للمخزون. الكمية الحالية: {$item->current_stock}.",
            'low_stock',
            [
                'item_id' => $item->id,
            ]
        );
    }
}