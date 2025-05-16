<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Telegram::sendMessage([
            'chat_id' => 5552158371,
            'text' => "Your new design is ready for sale!",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => '👕 Share new design',
                            'switch_inline_query' => 'New design available at',
                        ],
                        [
                            'text' => '🏪 Share all merch',
                            'switch_inline_query' => 'See my merch at ',
                        ],
                    ]
                ]
            ])
        ]);
    }
}
