<?php

namespace App\Listeners;

use App\Events\MaterialRequestCreated;
use App\Jobs\SendNotificationJob;
use App\Models\User;

class SendrequestNotification
{
    public function handle(MaterialRequestCreated $event): void
    {
       $warehouseManagers = User::role('storekeeper')
            ->pluck('id')
            ->toArray();

        if (empty($warehouseManagers)) {
            return;
        }

        $request = $event->request;

        SendNotificationJob::dispatch(
            $warehouseManagers,
            'طلب مواد جديد',
            "لديك طلب مواد جديد من الطبيب. رقم الطلب: {$request->requisition_number}",
            'material_request',
            [
                'request_id' => $request->id,
            ]
        );
    }
}