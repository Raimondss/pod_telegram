<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use App\Telegram\UpdateProcessor;
use App\Users\Services\TelegramUserService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class ManageStoresFlowProcessor implements FlowProcessorInterface
{
    private const string STEP_WAIT_STORE_SELECTION = 'wait_store_selection';
    private const string STEP_WAIT_STORE_ACTION_SELECTION = 'wait_store_action_selection';

    private const string STORE_ACTION_ADD_PRODUCT = 'add_product';
    private const string STORE_ACTION_MANAGE_PRODUCTS = 'manage_products';

    public function __construct(private TelegramUserService            $telegramUserService,
                                private AddProductToStoreFlowProcessor $addProductToStoreFlowProcessor
    ) {}

    public function startWithSelectedStore(int $userId, int $selectedStoreId, Update $update): UserState
    {
        $state = UserState::getFreshState($userId, UpdateProcessor::MANAGE_STORES_FLOW_KEY, [
            'selectedStoreId' => $selectedStoreId,
        ]);

        return $this->processUserState($state, $update);
    }

    /**
     * Processes user state and returns new user state
     * @param UserState $previousState
     * @param Update $update
     * @return UserState
     */
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        if (!$previousState->previousStepKey) {
            $this->sendStoreListSelection($update);
            $previousState->previousStepKey = self::STEP_WAIT_STORE_SELECTION;

            return $previousState;
        }

        //User is presented with store selection message and we wait for updates
        if ($previousState->previousStepKey == self::STEP_WAIT_STORE_SELECTION) {
            return $this->processStoreSelected($previousState, $update);
        }

        //User has selected store
        if ($previousState->previousStepKey == self::STEP_WAIT_STORE_ACTION_SELECTION) {
            return $this->processStoreActionSelected($previousState, $update);
        }

        return $previousState;
    }

    private function processStoreSelected(UserState $userState, Update $update): UserState
    {
        if ($update->callbackQuery) {
            $storeId = $update->callbackQuery->data;
            $this->sendStoreActionList($update->getMessage()->messageId, $userState);

            $userState->previousStepKey = self::STEP_WAIT_STORE_ACTION_SELECTION;
            $userState->extra['selectedStoreId'] = (int)$storeId;
            return $userState;
        }

        return $userState;
    }

    public function processStoreActionSelected(UserState $userState, Update $update): UserState
    {
        if ($update->callbackQuery) {
            $storeActionId = $update->callbackQuery->data;

            if ($storeActionId == self::STORE_ACTION_ADD_PRODUCT) {
                Telegram::deleteMessage(
                    [
                        'chat_id' => $userState->userId,
                        'message_id' => $update->getMessage()->messageId
                    ]
                );
                return $this->addProductToStoreFlowProcessor->startFlow($userState->userId, $update, $userState->extra['selectedStoreId']);
            }

            var_dump($storeActionId);

            return $userState;
        }

        return $userState;
    }

    private function sendStoreActionList(int $previousMessageId, UserState $userState): void
    {
        Telegram::editMessageText([
            'chat_id' => $userState->userId,
            'message_id' => $previousMessageId,
            'text' => "Select store",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => "Add Product",
                            'callback_data' => self::STORE_ACTION_ADD_PRODUCT,
                        ],
                        [
                            'text' => "Manage Products",
                            'callback_data' => self::STORE_ACTION_MANAGE_PRODUCTS,
                        ],
                    ]
                ],
                'force_reply' => true,
                'selective' => true,
            ]),
        ]);
    }

    private function sendStoreListSelection(Update $update): void
    {
        $userId = $update->getMessage()->from->id;

        Telegram::sendMessage([
            'chat_id' => $userId,
            'text' => "Select store",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    $this->getUserStoreList($update)
                ],
                'force_reply' => true,
                'selective' => true,
            ]),
        ]);
    }

    private function getUserStoreList(Update $update): array
    {
        $user = $this->telegramUserService->findOrCreateUserFromUpdate($update);

        $stores = $user->stores;

        $storeArray = [];
        foreach ($stores as $store) {
            $storeArray[] = [
                'text' => $store->name,
                'callback_data' => $store->id,
            ];
        }

        return $storeArray;
    }
}
