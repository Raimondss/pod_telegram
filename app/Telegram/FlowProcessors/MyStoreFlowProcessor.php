<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Models\TelegramUserProduct;
use App\Telegram\Helpers\Helpers;
use App\Telegram\Structures\UserState;
use App\Telegram\UpdateProcessor;
use App\Users\Services\TelegramUserService;
use Telegram\Bot\Objects\Update;

class MyStoreFlowProcessor implements FlowProcessorInterface
{
    public function __construct(private TelegramUserService      $telegramUserService,
                                private BrowseProductsProcessors $browseProductsProcessors) {}

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $user = $this->telegramUserService->findOrCreateUserFromUpdate($update);
        $hasStoreProduct = TelegramUserProduct::whereTelegramUserId($user->id)->exists();

        if (!$hasStoreProduct) {
            Helpers::sendMessage($previousState->userId,
                "Looks like you have no products added - lets add them using: /create_product"
            );

            return UserState::getFreshState($previousState->userId, null);
        }

        $state = UserState::getFreshState($previousState->userId,
            UpdateProcessor::BROWSE_PRODUCTS_FLOW, [
                'storeOwnerUserId' => $user->id,
            ]);

        $state->previousStepKey = BrowseProductsProcessors::FLOW_MY_STORE;

        return $this->browseProductsProcessors->processUserState($state, new Update([]));
    }
}
