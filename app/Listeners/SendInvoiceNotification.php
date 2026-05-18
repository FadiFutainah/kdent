<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\InvoiceCreated;
use App\Models\User;
use App\Jobs\SendNotificationJob;

class SendInvoiceNotification
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
    // public function handle(InvoiceCreated $event): void
    // {
    //       dispatch(new SendInvoiceNotificationJob($event->invoice));
    // }|
    public function handle($event)
{
    dispatch(new SendNotificationJob(
        // User::where('role', 'accountant')->pluck('id'),
          User::role('accountant')->pluck('id'),
        'فاتورة جديدة',
        "تم إنشاء فاتورة جديدة",
        'invoice',
        ['invoice_id' => $event->invoice->id]
    ));
}

}
