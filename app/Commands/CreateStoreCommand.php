<?php

namespace App\Commands;

use App\Stores\Services\StoreCreationService;
use App\Telegram\UserStateService;
use App\Users\Services\TelegramUserService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;


/**
 * This command can be triggered in two ways:
 * /start and /join due to the alias.
 */
class CreateStoreCommand implements CommandInterface
{

    public const string STATE_WAITING_NAME = 'name';
    public const string STATE_INITIAL = 'initial';

    public const string COMMAND_CREATE_STORE = 'create_store';

    public function __construct(private readonly UserStateService     $userStateService,
                                private readonly TelegramUserService  $telegramUserService,
                                private readonly StoreCreationService $storeCreationService,
    ) {}

    public function process(Update $update): void
    {
        $userId = $update->getMessage()->from->id;

        $state = $this->userStateService->getUserState($userId);

        //TODO SHOULD BE STRUCTURE.
        $commandState = $state[1];

        $this->processState($commandState, $update);
    }

    public function processState(string $state, Update $update): void
    {
        $userId = $update->getMessage()->from->id;
        $message = $update->getMessage()->text ?? '';

        switch ($state) {
            case self::STATE_INITIAL:
                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => "How would you like to call your store?",
                    ]
                );

                $this->userStateService->setUserState($userId, self::COMMAND_CREATE_STORE, self::STATE_WAITING_NAME);

                return;

            case self::STATE_WAITING_NAME:

                $user = $this->telegramUserService->findOrCreateUserFromUpdate($update);
                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => "Store created",
                    ]
                );

                $this->storeCreationService->createStore($user, $message);
                $this->userStateService->setUserState($userId, self::COMMAND_CREATE_STORE, self::STATE_WAITING_NAME);
                $this->userStateService->clearUserState($userId);

                return;

            default:
                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => "State:" . $state . " not mapped",
                    ]
                );
        }
    }

    public function getStartState(): string
    {
        return self::STATE_INITIAL;
    }
}
