<?php

declare(strict_types=1);

namespace App\Commands;

use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class StartCommand implements CommandInterface
{
    public function process(Update $update)
    {
        $userId = $update->getMessage()->from->id;
        $me = Telegram::getMe();


        $link = "https://t.me/testpodbot_bot?start=123";
        Telegram::sendMessage([
            'chat_id' => $userId,
            'text' => "Hello, this is a test message from my bot",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Buy',
                            'url' => $link,
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function getStartState()
    {
        return "initial";
    }
}
