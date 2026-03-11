<?php

namespace App\Domain\Orders\Listeners;

use App\Domain\Orders\Events\OrderPlacedEvent;
use App\Domain\Orders\Mail\AdministratorOrderPlacedMail;
use App\Domain\Orders\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendAdministratorOrderEmailListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPlacedEvent $event): void
    {
        /** @var Order $order */
        $order = $event->order->loadMissing('site');

        Mail::to((string) config('orders.administrator_email'))
            ->send(new AdministratorOrderPlacedMail($order));
    }
}
