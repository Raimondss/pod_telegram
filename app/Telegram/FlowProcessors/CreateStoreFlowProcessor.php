<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Stores\Services\StoreCreationService;
use App\Telegram\Structures\UserState;
use App\Users\Services\TelegramUserService;
use Telegram;
use Telegram\Bot\Objects\Update;

class CreateStoreFlowProcessor implements FlowProcessorInterface
{
    private const STEP_ASK_STORE_NAME = 'ask_store_name';

    public function __construct(private StoreCreationService           $storeCreationService,
                                private TelegramUserService            $telegramUserService,
                                private AddProductToStoreFlowProcessor $addProductToStoreFlowProcessor,
    ) {}

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        //User just started /create_store flow -> we ask what should store name be.
        if (!$previousState->previousStepKey) {
            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => "How would you like to name your store?",
            ]);

            $previousState->previousStepKey = self::STEP_ASK_STORE_NAME;

            return $previousState;
        }

        if ($previousState->previousStepKey == self::STEP_ASK_STORE_NAME) {
            //TODO VALIDATE
            $storeName = $update->getMessage()->text ?? '';

            $telegramUser = $this->telegramUserService->findOrCreateUserFromUpdate($update);
            $store = $this->storeCreationService->createStore($telegramUser, $storeName);

            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => "Your store has been created - lets add product!",
            ]);

            return $this->addProductToStoreFlowProcessor->startFlow($previousState->userId, $update, $store->id);
        }

        return $previousState;
    }
}
