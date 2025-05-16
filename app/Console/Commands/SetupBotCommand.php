<?php

namespace App\Console\Commands;

use App\Telegram\Structures\BotCommands;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetupBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets up bots commands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Telegram::setMyCommands([
            'commands' => BotCommands::getAllAvailableCommands(),
        ]);
    }
}
