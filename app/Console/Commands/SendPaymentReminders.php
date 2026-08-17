<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'app:send-payment-reminders';
    protected $signature = 'reminders:send-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
    $service = new \App\Services\InvoiceService();
    $service->sendPaymentReminders();
    $this->info('تم إرسال التنبيهات بنجاح.');
    }
    
}
