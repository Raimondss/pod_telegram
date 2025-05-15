<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

class CheckoutFlowProcessor implements FlowProcessorInterface
{
    public const string STEP_WAITING_STORE_SELECTION = 'waiting_store_selection';
    public const string STEP_WAITING_DESIGN_SELECTION = 'step_waiting_design_selection';
    public const string STEP_WAITING_PRODUCT_SELECTION = 'step_waiting_production_selection';
    public const string STEP_WAITING_VARIANT_SELECTION = 'step_waiting_variant_selection';


    public function processUserState(UserState $previousState, Update $update): UserState
    {
        if (!$previousState->previousStepKey) {

            $previousState->previousStepKey = self::STEP_WAITING_STORE_SELECTION;
        }

        if ($previousState->previousStepKey == self::STEP_WAITING_STORE_SELECTION) {

        }
    }
}
