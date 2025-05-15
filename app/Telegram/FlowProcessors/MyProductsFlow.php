<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

class MyProductsFlow implements FlowProcessorInterface
{
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        // TODO: Implement processUserState() method.
    }
}
