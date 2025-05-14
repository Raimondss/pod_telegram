<?php

namespace App\Commands;


use Telegram\Bot\Objects\Update;

interface CommandInterface
{
    public function process(Update $update);

    public function getStartState();
}
