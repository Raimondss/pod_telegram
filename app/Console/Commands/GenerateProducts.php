<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGenerateMockupsJob;
use App\Models\TelegramUserProduct;
use App\Models\TelegramUserVariant;
use App\Repository\ProductMapRepository;
use App\Services\MockupGeneratorService;
use Illuminate\Console\Command;
use Log;

class GenerateProducts extends Command
{
    private const BATCH_SIZE = 10;

    public function __construct(
        private MockupGeneratorService $mockupGeneratorService,
        private ProductMapRepository $productMapRepository
    ) {
        parent::__construct();
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
        $pendingProducts = TelegramUserProduct::where(['status' => TelegramUserProduct::STATUS_PENDING])->limit(self::BATCH_SIZE)->get();

        Log::info('Products up for processing: ' . $pendingProducts->count());

        foreach ($pendingProducts as $pendingProduct) {

            Log::info('Creating a task for: ' . $pendingProduct->id);

            $pendingProduct->status = TelegramUserProduct::STATUS_PROCESSING;
            $pendingProduct->save();

            $pendingVariants = TelegramUserVariant::where([
                'status' => TelegramUserProduct::STATUS_PENDING,
                'telegram_user_product_id' => $pendingProduct->id
            ])->get();

            $productMap = $this->productMapRepository->getProductMapById($pendingProduct->product_id);

            $isProductLevelMockupStyles = $productMap['mockup_style_ids'] ?? null;
            if ($isProductLevelMockupStyles) {
                $task = $this->mockupGeneratorService->generateMockupsForVariants(
                    $pendingProduct->product_id,
                    $pendingVariants->pluck('variant_id')->toArray(),
                    $pendingProduct->uploaded_file_url,
                );

                foreach ($pendingVariants as $pendingVariant) {
                    $pendingVariant->status = TelegramUserVariant::STATUS_PROCESSING;
                    $pendingVariant->save();
                }

                ProcessGenerateMockupsJob::dispatch($pendingProduct->id, $task->id);
            } else { // variant level mockup styles

                $groupedByStyles = [];
                foreach ($pendingVariants as $pendingVariant) {
                    $variantMap = collect($productMap['variants'])->firstWhere('id', $pendingVariant->variant_id);
                    $groupedByStyles[$variantMap['mockup_style_id']] = $groupedByStyles[$variantMap['mockup_style_id']] ?? [];
                    $groupedByStyles[$variantMap['mockup_style_id']][] = $pendingVariant;
                }

                foreach ($groupedByStyles as $styleId => $groupedPendingVariants) {
                    $task = $this->mockupGeneratorService->generateMockupsForVariants(
                        $pendingProduct->product_id,
                        collect($groupedPendingVariants)->pluck('variant_id')->toArray(),
                        $pendingProduct->uploaded_file_url,
                        [$styleId]
                    );

                    ProcessGenerateMockupsJob::dispatch($pendingProduct->id, $task->id);

                    foreach ($groupedPendingVariants as $pendingVariant) {
                        $pendingVariant->status = TelegramUserVariant::STATUS_PROCESSING;
                        $pendingVariant->save();
                    }
                }
            }

            Log::info('Tasks created');
        }
    }
}
