<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Commands\CommandInterface;
use App\Commands\CreateStoreCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class MessageProcessor
{

    public function __construct(private UserStateService $userStateService) {}

    private const string LAST_PROCESSED_UPDATE_KEY = "LAST_PROCESSED_UPDATE";
    private const int MESSAGE_LIMIT = 1;

    public const COMMAND_MAPPING = [
        '/create_store' => CreateStoreCommand::class
    ];

    public function run(): void
    {
        $updates = Telegram::getUpdates([
            'offset' => $this->getLastProcessedId() + 1,
            'limit' => self::MESSAGE_LIMIT,
        ]);

        if (empty($updates)) {
            var_dump("No updates found");
            return;
        }

        foreach ($updates as $update) {
            $this->processUpdate($update);
            $this->setLastProcessedId($update->getUpdateId());
        }
    }

    private function processUpdate(Update $update): void
    {
        try {
            var_dump($update->getMessage());
            $this->resolveUpdate($update);
        } catch (\Throwable $throwable) {
            Log::error($throwable->getMessage());
            var_dump($throwable->getMessage());
        }
    }

    private function resolveUpdate(Update $update): void
    {
        $message = $update->getMessage()->text ?? '';

        var_dump($message);

        if (str_starts_with($message, '/')) {
            var_dump("Handling as command");
            $this->handleCommand($update);
        } else {
            var_dump("Handling as message");
            $this->handleMessage($update);
        }
    }

    private function handleMessage(Update $update): void
    {
        $userId = $update->getMessage()->from->id;
        $userState = $this->userStateService->getUserState($userId);

        $commandKey = $userState[0];

        /** @type CommandInterface $stateCommand */
        $command = $this->getCommand("/" . $commandKey);
        $command->process($update);
    }

    private function getCommand(string $commandKey): ?CommandInterface
    {
        $command = self::COMMAND_MAPPING[$commandKey] ?? null;

        if (!$command) {
            return null;
        }

        return app($command);
    }

    private function handleCommand(Update $update): void
    {
        $messageText = $update->getMessage()->text ?? '';
        $userId = $update->getMessage()->from->id;

        $command = $this->getCommand($messageText);

        if (!$command) {
            Telegram::sendMessage([
                'chat_id' => $userId,
                'text' => "Unkown command",
            ]);

            var_dump("NO COMMAND MAPPING FOR : " . $messageText);
        }

        $this->userStateService->setUserState($userId, $command::class, $command->getStartState());

        $command->process($update);
    }

    private function getLastProcessedId()
    {
        return Cache::get(self::LAST_PROCESSED_UPDATE_KEY, 0);
    }

    private function setLastProcessedId(int $lastProcessedId): void
    {
        Cache::set(self::LAST_PROCESSED_UPDATE_KEY, $lastProcessedId);
    }

    public function decrementProcessedId(): void
    {
        $lastMessage = Cache::get(self::LAST_PROCESSED_UPDATE_KEY, 0);
        Cache::set(self::LAST_PROCESSED_UPDATE_KEY, $lastMessage - 1);
    }
}
