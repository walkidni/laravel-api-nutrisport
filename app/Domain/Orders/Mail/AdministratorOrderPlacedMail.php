<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdministratorOrderPlacedMail extends Mailable
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
            ->subject('Nouvelle commande '.$this->order->getAttribute(Order::REFERENCE))
            ->view('emails.orders.administrator_order_placed');
    }
}
