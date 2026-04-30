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
    ];

    public function boot(): void
    {
        //
    }
}