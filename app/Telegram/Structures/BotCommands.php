<?php

declare(strict_types=1);

namespace App\Telegram\Structures;

final class BotCommands
{
    public static function getAllAvailableCommands(): array
    {
        return [
            [
                'command' => '/buy_product',
                'description' => 'Browse stores and purchase products',
            ],
            [
                'command' => '/create_product',
                'description' => 'Create product',
            ],
            [
                'command' => '/my_products',
                'description' => 'My products',
            ],
            [
                'command' => '/help',
                'description' => 'List all available commands',
            ],
        ];
    }
}
