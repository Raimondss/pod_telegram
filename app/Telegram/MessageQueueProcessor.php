<?php

declare(strict_types=1);

namespace App\Telegram;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class MessageQueueProcessor
{
    public function __construct(private UpdateProcessor $updateProcessor) {}

    private const string LAST_PROCESSED_UPDATE_KEY = "LAST_PROCESSED_UPDATE";
    private const int MESSAGE_LIMIT = 1;

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

            try {
                $this->updateProcessor->processUpdate($update);
            } catch (\Throwable $throwable) {
                Log::error($throwable);
                echo "Error processing update - check storage/logs" . PHP_EOL;
                echo $throwable->getMessage() . PHP_EOL;
            }

            $this->setLastProcessedId($update->getUpdateId());
        }
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
