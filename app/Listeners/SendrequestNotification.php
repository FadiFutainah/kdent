<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\MaterialRequestCreated;
use App\Models\User;
use App\Jobs\SendNotificationJob;
class SendrequestNotification
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
    public function handle($event): void
    {
          // 🔔 إشعار للمستودع
        dispatch(new SendNotificationJob(
            User::role('storekeeper')->pluck('id'),
            'طلب مواد جديد',
            'في طلب جديد من دكتور',
            'material_request',
            ['request_id' => $event->request->id]
        ));
    }
}
