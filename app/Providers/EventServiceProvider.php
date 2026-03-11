<?php

namespace App\Providers;

use App\Domain\Orders\Events\OrderPlacedEvent;
use App\Domain\Orders\Listeners\NotifyBackofficeOfNewOrderListener;
use App\Domain\Orders\Listeners\SendAdministratorOrderEmailListener;
use App\Domain\Orders\Listeners\SendCustomerOrderConfirmationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderPlacedEvent::class => [
            SendCustomerOrderConfirmationListener::class,
            SendAdministratorOrderEmailListener::class,
            NotifyBackofficeOfNewOrderListener::class,
        ],
    ];
}
