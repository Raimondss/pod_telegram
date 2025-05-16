<?php

declare(strict_types=1);

namespace App\Telegram\Helpers;

use Telegram\Bot\Laravel\Facades\Telegram;

class Helpers
{
    public static function getBuyProductLink(int $variantId): string
    {
        $botUsername = env('BOT_USERNAME');

        return "https://t.me/" . $botUsername . "?start=buy_product_" . $variantId;
    }

    public static function getBrowseStoreLink(int $storeOwnerUserId): string
    {
        $botUsername = env('BOT_USERNAME');
        return "https://t.me/" . $botUsername . "?start=browse_products" . $storeOwnerUserId;
    }

    public static function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    public static function getCheckoutDesignLink(int $storeOwnerUserId, string $designName): string
    {
        $botUsername = env('BOT_USERNAME');

        $designName = base64_encode($designName);

        return "https://t.me/" . $botUsername . "?start=checkout_design_" . $storeOwnerUserId . '_' . $designName;
    }
}
