<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGenerateMockupsJob;
use App\Models\TelegramUserProduct;
use App\Models\TelegramUserVariant;
use App\Services\MockupGeneratorService;
use Illuminate\Console\Command;
use Log;

class GenerateProducts extends Command
{
    private const BATCH_SIZE = 10;

    public function __construct(
      private MockupGeneratorService $mockupGeneratorService,
    ) {
    }

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
        $pendingProducts = TelegramUserProduct::where(['status', TelegramUserProduct::STATUS_PENDING])->limit(self::BATCH_SIZE)->get();

        Log::info('Products up for processing: ' . $pendingProducts->count());

        foreach ($pendingProducts as $pendingProduct) {

            Log::info('Creating a task for: ' . $pendingProduct->id);

            $pendingVariants = TelegramUserVariant::where(['status', TelegramUserProduct::STATUS_PENDING, 'telegram_user_product_id' => $pendingProduct->id])->get();

            $tasks = $this->mockupGeneratorService->generateMockupsForVariants(
                $pendingProduct->product_id,
                $pendingVariants->pluck('variant_id')->toArray(),
                $pendingProduct->uploaded_file_url,
            );
            Log::info('Task created');

            $taskIds = array_map(static fn ($task) => $task['id'], $tasks);

            ProcessGenerateMockupsJob::dispatch($pendingProduct->id, $taskIds);
        }
    }
}
