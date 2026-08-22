<?php

namespace App\Listeners;

use App\Events\MaterialRequestCreated;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendrequestNotification
{
    public function handle(MaterialRequestCreated $event): void
    {
       $warehouseManagers = User::role('storekeeper')
            ->pluck('id')
            ->toArray();
        Log::info($warehouseManagers);
        Log::info('here1');

        if (empty($warehouseManagers)) {
            Log::info('here');
            return;
        }

        Log::info($warehouseManagers);
        $request = $event->request;

        Log::info($warehouseManagers);
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