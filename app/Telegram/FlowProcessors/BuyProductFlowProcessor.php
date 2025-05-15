<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\MessageHelpers\ProductCardMessageHelper;
use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

class BuyProductFlowProcessor implements FlowProcessorInterface
{

    public function __construct(private ProductCardMessageHelper $productCardMessageHelper) {}

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $message = $update->getMessage()->text ?? '';


        $buyProductString = explode(" ", $message)[1] ?? null;
        $variantId = str_replace('buy_product_', '', $buyProductString);

        if (!$variantId) {
            return UserState::getFreshState($previousState->userId, null);
        }

        $this->productCardMessageHelper->sendVariantCard($previousState->userId, (int)$variantId);

        return $previousState;
    }
}
