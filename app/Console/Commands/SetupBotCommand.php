<?php

namespace App\Console\Commands;

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
            'commands' => [
                [
                    'command' => '/create_store',
                    'description' => 'Create new store',
                ],
                [
                    'command' => '/manage_stores',
                    'description' => 'Manage your stores',
                ],
                [
                    'command' => '/create_product',
                    'description' => 'Create product',
                ]
            ]
        ]);
    }
}
