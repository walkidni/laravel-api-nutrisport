<?php

namespace App\Domain\Orders\Listeners;

use App\Domain\Orders\Events\OrderPlacedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyBackofficeOfNewOrderListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPlacedEvent $event): void
    {
        Log::info('BackOffice order notification placeholder dispatched.', [
            'order_id' => $event->order->getKey(),
            'reference' => $event->order->getAttribute('reference'),
        ]);
    }
}
