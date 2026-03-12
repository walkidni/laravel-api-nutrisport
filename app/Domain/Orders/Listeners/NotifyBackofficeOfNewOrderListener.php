<?php

namespace App\Domain\Orders\Listeners;

use App\Domain\Backoffice\Events\BackofficeOrderPlacedNotification;
use App\Domain\Orders\Events\OrderPlacedEvent;

class NotifyBackofficeOfNewOrderListener
{
    public function handle(OrderPlacedEvent $event): void
    {
        BackofficeOrderPlacedNotification::dispatch($event->order);
    }
}
