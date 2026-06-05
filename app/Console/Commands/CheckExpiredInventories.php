<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory;
use App\Models\Disposal;
use App\Models\DisposalItem;
use App\Events\ExpiredItemsDetected;
use Illuminate\Support\Facades\DB;

class CheckExpiredInventories extends Command
{
    protected $signature = 'inventory:check-expired';
    protected $description = 'فحص المواد منتهية الصلاحية ليلاً، تجميدها، وإنشاء مسودة طلب إتلاف فوري';

    public function handle()
    {
        DB::transaction(function () {
            $today = now()->startOfDay();

            // 1. جلب كل الدفعات النشطة التي انتهت صلاحيتها اليوم أو سابقاً ولديها كمية
            $expiredBatches = Inventory::where('is_active', true)
                ->where('quantity', '>', 0)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', $today)
                ->get();

            if ($expiredBatches->isEmpty()) {
                $this->info('المستودع سليم، لا توجد مواد منتهية الصلاحية اليوم.');
                return;
            }

            // 2. إنشاء رأس مستند الإتلاف (المسودة) ليرتبط بالنظام
            $disposal = Disposal::create([
                'disposal_number' => 'AUTO-DISP-' . date('YmdHis'),
                'reason' => 'expired',
                'reason_notes' => 'طلب مؤتمت أنشأه النظام للدفعات التي انتهت صلاحيتها دفترياً.',
                'status' => 'pending',
                'created_by' => null, // النظام هو المنشئ
                'disposal_date' => now(),
                'total_quantity' => $expiredBatches->sum('quantity'),
                    'notes' => 'طلب مؤتمت... (الملاحظات)',
            ]);

            // 3. حظر الدفعة عن الأطباء وربطها بطلب الإتلاف
            foreach ($expiredBatches as $batch) {
                
                // تجميد الدفعة فوراً لكي لا تظهر بنظام الـ FIFO الخاص بصرف العيادات
                $batch->update(['is_active' => false]);

                // تسجيل المادة داخل تفاصيل مستند الإتلاف
                DisposalItem::create([

                    'disposal_id' => $disposal->id,
                    'item_id' => $batch->item_id,
                    'inventory_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->expiry_date,
                    'reason_details' => 'مادة منتهية الصلاحية (نظام)',
                ]);
            }

            // 4. 🚀 إطلاق الـ Event النظيف لإرسال إشعار الفايربيز للمدراء
            $this->info('جاري إطلاق الحدث...');
            event(new ExpiredItemsDetected($disposal));
$this->info('تم إطلاق الحدث بنجاح.');
            $this->info("تم بنجاح حظر المواد وإنشاء مستند الإتلاف التلقائي رقم: {$disposal->disposal_number}");
        });

        return 0;
    }

}