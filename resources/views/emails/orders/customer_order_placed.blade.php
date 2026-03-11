Votre commande {{ $order->reference }} a bien ete prise en compte.

Montant total : {{ number_format($order->total_amount_cents / 100, 2, '.', '') }}
