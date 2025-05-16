<?php

declare(strict_types=1);

namespace App\Telegram\Structures;

final class BotCommands
{
    public static function getAllAvailableCommands(): array
    {
        return [
            [
                'command' => '/help',
                'description' => 'List all available commands!',
            ],
            [
                'command' => '/create_product',
                'description' => 'Create your own product!',
            ],
            [
                'command' => '/my_store',
                'description' => 'Check out your store!',
            ],
        ];
    }
}
