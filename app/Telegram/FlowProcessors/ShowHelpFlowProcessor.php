<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\FlowProcessors\FlowProcessorInterface;
use App\Telegram\Helpers\Helpers;
use App\Telegram\Structures\BotCommands;
use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

final class ShowHelpFlowProcessor implements FlowProcessorInterface
{
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $helperText = "Here are the commands you can use:\n";
        $availableCommands = BotCommands::getAllAvailableCommands();
        foreach ($availableCommands as $availableCommand) {
            $helperText .= $availableCommand['command'] . " - " . $availableCommand['description'] . "\n";
        }

        Helpers::sendMessage(
            $previousState->userId,
            $helperText
        );

        return UserState::getFreshState($previousState->userId, null);
    }
}
