<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\AppointmentCreated;
use App\Listeners\SendAppointmentNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // AppointmentCreated::class => [
        //     SendAppointmentNotification::class,
        // ],
        \App\Events\InvoiceCreated::class => [
        \App\Listeners\SendInvoiceNotification::class,
],
\App\Events\LowStockDetected::class => [
        \App\Listeners\LowStockNotification::class,
    ],
      \App\Events\MaterialRequestCreated::class => [
        \App\Listeners\SendRequestNotification::class,
    ],
        \App\Events\ExpiredItemsDetected::class => [
            \App\Listeners\ExpiredItemsNotification::class,
        ],
         \App\Events\InvoiceApproved::class => [                    // ← جديد
        \App\Listeners\SendInvoiceApprovedNotification::class, // ← جديد
    ],
    ];

    public function boot(): void
    {
        \App\Models\Item::observe(\App\Observers\ItemObserver::class);
    }
}