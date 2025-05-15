<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

class PreCheckoutQueryProcessor implements FlowProcessorInterface
{
    //Enables us to validate data beofore actual payment - so far always all good!
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        \Telegram::answerPreCheckoutQuery([
            'pre_checkout_query_id' => $update->preCheckoutQuery->id,
            'ok' => true,
        ]);

        return UserState::getFreshState($previousState->userId, null);
    }
}
