<?php

namespace App\Domain\Orders\Listeners;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Events\OrderPlacedEvent;
use App\Domain\Orders\Mail\CustomerOrderPlacedMail;
use App\Domain\Orders\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCustomerOrderConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPlacedEvent $event): void
    {
        /** @var Order $order */
        $order = $event->order->loadMissing('customer');
        /** @var Customer $customer */
        $customer = $order->customer;

        Mail::to((string) $customer->getAttribute(Customer::EMAIL))
            ->send(new CustomerOrderPlacedMail($order));
    }
}
