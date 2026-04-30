<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Notification;
// class SendInvoiceNotificationJob implements ShouldQueue
// {
//     use Queueable;

//      protected Invoice $invoice;

//     public function __construct(Invoice $invoice)
//     {
//         $this->invoice = $invoice;
//     }

//     public function handle()
//     {
//         $accountants = User::where('role', 'accountant')->pluck('id');

//         foreach ($accountants as $userId) {
//             Notification::create([
//                 'user_id' => $userId,
//                 'title' => 'فاتورة جديدة',
//                 'body' => "فاتورة جديدة من المورد رقم {$this->invoice->supplier_id}",
//                 'type' => 'invoice',
//                 'data' => json_encode([
//                     'invoice_id' => $this->invoice->id
//                 ])
//             ]);
//         }
//     }
// }

class SendNotificationJob implements ShouldQueue
{
    public $users;
    public $title;
    public $body;
    public $type;
    public $data;

    public function __construct($users, $title, $body, $type, $data = [])
    {
        $this->users = $users;
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->data = $data;
    }

    public function handle()
    {
        // 🧾 DB
        foreach ($this->users as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $this->title,
                'body' => $this->body,
                'type' => $this->type,
                'data' => json_encode($this->data)
            ]);
        }

        // 🔔 Firebase
        Http::withHeaders([
            'Authorization' => 'key=' . config('services.firebase.server_key'),
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => '/topics/general',
            'notification' => [
                'title' => $this->title,
                'body' => $this->body,
            ],
            'data' => $this->data
        ]);
    }
}
