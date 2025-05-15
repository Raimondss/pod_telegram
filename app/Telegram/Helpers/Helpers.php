<?php

declare(strict_types=1);

namespace App\Telegram\Helpers;

use Telegram\Bot\Laravel\Facades\Telegram;

class Helpers
{
    public static function getBuyProductLink(int $variantId): string
    {
        $botUsername = env('BOT_USERNAME');

        return "https://t.me/" . $botUsername . "?start=" . $variantId;
    }

    public static function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }
}
