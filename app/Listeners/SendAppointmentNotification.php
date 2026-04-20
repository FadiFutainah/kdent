<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\NotificationService;
use App\Models\User;
class SendAppointmentNotification implements ShouldQueue
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
     public function handle($event)
    {
        $receptions = User::role('reception')->get();

        foreach ($receptions as $reception) {

            app(NotificationService::class)->send(
                $reception,
                'موعد جديد',
                'تم حجز موعد جديد بانتظار التأكيد',
                'appointment',
                [
                    'appointment_id' => $event->appointment->id
                ]
            );
        }
    }
}
