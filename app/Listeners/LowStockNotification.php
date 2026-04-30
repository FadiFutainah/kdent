<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Jobs\SendNotificationJob;

class LowStockNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
         dispatch(new SendNotificationJob(
        // User::where('role', 'warehouse')->pluck('id'),
          User::role('storekeeper')->pluck('id'),
        'تنبيه مخزون',
        "⚠️ {$event->item->name} قربت تخلص",
        'low_stock',
        ['item_id' => $event->item->id]
    ));
    }
}
