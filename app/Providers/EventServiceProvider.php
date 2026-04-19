<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\AppointmentCreated;
use App\Listeners\SendAppointmentNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AppointmentCreated::class => [
            SendAppointmentNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}