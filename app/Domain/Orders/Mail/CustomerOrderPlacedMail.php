<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerOrderPlacedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Votre commande '.$this->order->getAttribute(Order::REFERENCE).' a bien ete prise en compte')
            ->view('emails.orders.customer_order_placed');
    }
}
