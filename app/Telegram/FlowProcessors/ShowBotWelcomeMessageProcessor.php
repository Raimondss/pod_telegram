<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\FlowProcessors\FlowProcessorInterface;
use App\Telegram\Helpers\Helpers;
use App\Telegram\Structures\BotCommands;
use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

final class ShowBotWelcomeMessageProcessor implements FlowProcessorInterface
{
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $botName = str_replace('@', '', env('BOT_USERNAME'));
        $botName = '@' . $botName;
        $helperText = "Hey there! 👋" . PHP_EOL .
            "Welcome to " . $botName . " – your one-stop shop for turning your awesome designs into real, sellable products! 🚀💰" . PHP_EOL . PHP_EOL;
        $helperText .= "Here are the commands you can use:" . PHP_EOL;
        $availableCommands = BotCommands::getAllAvailableCommands();
        foreach ($availableCommands as $availableCommand) {
            $helperText .= $availableCommand['command'] . " - " . $availableCommand['description'] . PHP_EOL;
        }

        Helpers::sendMessage(
            $previousState->userId,
            $helperText
        );

        return UserState::getFreshState($previousState->userId, null);
    }
}
