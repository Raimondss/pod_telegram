<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

class CheckoutFlowProcessor implements FlowProcessorInterface
{
    public const string STEP_SELECT_STORE = 'step_select_store';
    public const string STEP_SELECT_PRODUCT = 'step_select_product';
    public const string STEP_SELECT_VARIANT = 'step_select_variant';


    public function processUserState(UserState $previousState, Update $update): UserState
    {
        // TODO: Implement processUserState() method.
    }
}
