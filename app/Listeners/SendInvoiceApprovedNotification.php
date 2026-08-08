<?php

namespace App\Listeners;

use App\Events\InvoiceApproved;
use App\Models\User;
use App\Jobs\SendNotificationJob;

class SendInvoiceApprovedNotification
{
    public function handle(InvoiceApproved $event): void
    {
        dispatch(new SendNotificationJob(
            User::role('accountant')->pluck('id'),
            'فاتورة معتمدة',
            'تم اعتماد فاتورة مورد جديدة، جاهزة للدفع',
            'invoice_approved',
            ['invoice_id' => $event->invoice->id]
        ));
    }
}