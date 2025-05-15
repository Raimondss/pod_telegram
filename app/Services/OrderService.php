<?php

declare(strict_types=1);

namespace App\Services;

use App\APIs\PrintfulApi;
use App\Models\TelegramUserOrder;

class OrderService
{
    public function __construct(private PrintfulApi $api)
    {
    }

    public function sendOrderToPrintful(TelegramUserOrder $order): void
    {
        $this->api->createOrder($order);
    }
}
