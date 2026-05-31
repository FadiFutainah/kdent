<?php

namespace App\Observers;

use App\Models\Item;

class ItemObserver
{
    /**
     * Handle the Item "created" event.
     */
    public function created(Item $item): void
    {
        //
    }

    /**
     * Handle the Item "updated" event.
     */
    public function updated(Item $item)
{
    // تحقق إذا كانت الكمية الحالية أصبحت أقل من أو تساوي الحد الأدنى
    if ($item->quantity <= $item->min_stock_level && $item->getOriginal('quantity') > $item->min_stock_level) {
        
        // إطلاق الحدث (Event) الذي سيقوم بتشغيل الـ Listener (الذي أنشأناه سابقاً)
        event(new \App\Events\LowStockDetected($item));
    }
}

    /**
     * Handle the Item "deleted" event.
     */
    public function deleted(Item $item): void
    {
        //
    }

    /**
     * Handle the Item "restored" event.
     */
    public function restored(Item $item): void
    {
        //
    }

    /**
     * Handle the Item "force deleted" event.
     */
    public function forceDeleted(Item $item): void
    {
        //
    }
}
