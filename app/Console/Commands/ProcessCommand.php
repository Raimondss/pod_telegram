<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class ProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:process-command';

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
        $updates = Telegram::getUpdates();

        foreach ($updates as $update) {
            echo $update['message']['from']['id'] . "\n";
            Telegram::sendMessage([
                'chat_id' => $update['message']['from']['id'],
                'text' => "Hello world" . $update['message']['from']['first_name'] . " " . $update['message']['from']['last_name'],
            ]);
        }
    }
}
