<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram;
use Telegram\Bot\Objects\Update;

class EmptyFlowProcessor implements FlowProcessorInterface
{
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        //TODO REMOVE_AFTER TESTING
        Telegram::sendMessage([
            'chat_id' => $previousState->userId,
            'text' => 'Unkown request',
        ]);

        return $previousState;
    }
}
