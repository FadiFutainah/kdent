<?php

namespace App\Listeners;

use App\Events\InvoiceApproved;
use App\Jobs\SendNotificationJob;
use App\Models\User;

class SendInvoiceApprovedNotification
{
    public function handle(InvoiceApproved $event): void
    {
        $accountants = User::role('accountant')
            ->pluck('id')
            ->toArray();

        if (empty($accountants)) {
            return;
        }

        $invoice = $event->invoice;

        SendNotificationJob::dispatch(
            $accountants,
            'فاتورة مورد موافق عليها',
            "تمت الموافقة على فاتورة المورد رقم {$invoice->invoice_number} ويمكنك الآن متابعة محاسبة المورد.",
            'supplier_invoice_approved',
            [
                'invoice_id' => $invoice->id,
            ]
        );
    }
}