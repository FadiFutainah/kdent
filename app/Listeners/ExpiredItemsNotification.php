<?php

namespace App\Listeners;

use App\Events\ExpiredItemsDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
class ExpiredItemsNotification
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
    public function handle(ExpiredItemsDetected $event): void
    {
        dispatch(new \App\Jobs\SendNotificationJob(
        User::role('storekeeper')->pluck('id'), // المستهدفين
        '⚠️ طلب إتلاف تلقائي جديد',
        "تم رصد ({$event->disposal->items()->count()}) مادة منتهية.",
        'auto_expired_disposal',
        ['disposal_id' => $event->disposal->id]
    ));
    }
}
