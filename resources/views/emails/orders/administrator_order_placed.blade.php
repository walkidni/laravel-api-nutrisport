Nouvelle commande recue : {{ $order->reference }}.

Client ID : {{ $order->customer_id }}
Montant total : {{ number_format($order->total_amount_cents / 100, 2, '.', '') }}
