<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:generate-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates pending products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo  "Generating...";
    }
}
