<?php

namespace App\Console\Commands;

use App\Jobs\TestJob;
use App\Telegram\MessageQueueProcessor;
use Illuminate\Console\Command;

class ProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:process-command {runPrevious}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes command queue';

    /**
     * Execute the console command.
     * 290544079
     */
    public function handle()
    {
        $runPrevious = $this->argument('runPrevious', false);

        /** @var MessageQueueProcessor $processor */
        $processor = app(MessageQueueProcessor::class);
        if ($runPrevious) {
            $processor->decrementProcessedId();
        }

        $processor->run();
    }
}
