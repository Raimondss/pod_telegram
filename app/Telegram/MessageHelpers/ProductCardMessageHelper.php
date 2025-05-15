<?php

declare(strict_types=1);

namespace App\Telegram\MessageHelpers;

use App\Models\TelegramUserVariant;
use Telegram;

class ProductCardMessageHelper
{
    public function sendVariantCard(int $chatId, int $variantId): void
    {
        $variant = TelegramUserVariant::whereId($variantId)->firstOrFail();

        Telegram::sendInvoice(
            [
                'chat_id' => $chatId,
                'prices' => [
                    [
                        'label' => $variant->getDisplayTitle(),
                        'amount' => $variant->price,
                    ]
                ],
                'photo_url' => $variant->mockup_url,
                'provider_token' => env("PAYMENT_TOKEN"),
                'currency' => 'USD',
                'title' => $variant->getDisplayTitle(),
                'description' => $variant->getDisplayTitle(),
                'payload' => $variantId,
                'need_shipping_address' => true,
                'need_email' => true,
            ]
        );
    }
}
