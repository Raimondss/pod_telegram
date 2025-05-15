<?php

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

/**
 * Flow processors processes state and move client to next steps using previous state and update received.
 */
interface FlowProcessorInterface
{
    /**
     * Processes and returns new user state.
     * @param UserState $previousState
     * @param Update $update
     * @return UserState
     */
    public function processUserState(UserState $previousState, Update $update): UserState;
}
