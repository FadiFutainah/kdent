<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class RunManualBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 15 دقيقة كحد أقصى للنسخة.
    public $timeout = 900;

    // لا نعيد المحاولة تلقائياً حتى لا تتولد نسخ مكررة.
    public $tries = 1;

    public function handle(): void
    {
        $exitCode = Artisan::call('backup:run');
        $output = Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "فشلت النسخة الاحتياطية اليدوية:\n" . $output
            );
        }
    }
}
