<?php

declare(strict_types=1);

namespace App\Commands;

use Telegram\Bot\Objects\Update;

class StartCommand implements CommandInterface
{
    public function process(Update $update)
    {
        var_dump("Doing start command");
    }

    public function getStartState()
    {
        return "initial";
    }
}
