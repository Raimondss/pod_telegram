<?php

declare(strict_types=1);

namespace App\Services;

use App\APIs\PrintfulApi;
use App\Models\TelegramUserProduct;
use App\Models\TelegramUserVariant;
use App\Params\ApiMockupGeneratorParams;
use App\Params\ApiMockupGeneratorProductParams;
use App\Params\ApiMockupGeneratorProductPlacementLayerParams;
use App\Params\ApiMockupGeneratorProductPlacementParams;
use App\Repository\ProductMapRepository;
use App\Structures\Api\ApiMockupGeneratorTask;
use App\Telegram\Helpers\Helpers;
use App\Telegram\UserStateService;
use Exception;
use Log;
use Telegram;
use Telegram\Bot\FileUpload\InputFile;

class MockupGeneratorService
{
    public function __construct(
        private PrintfulApi      $api,
        private UserStateService $userStateService,
        private ProductMapRepository $productMapRepository
    ) {}

    /**
     * @param string $url
     * @return ApiMockupGeneratorTask[]
     */
    public function generateMockupsByUrl(string $url): array
    {
        $productMap = $this->productMapRepository->getProductMap();

        $params = new ApiMockupGeneratorParams();

        foreach ($productMap as $productId => $variantData) {
            $productParams = new ApiMockupGeneratorProductParams();
            $productParams->catalogProductId = $productId;
            $productParams->catalogVariantIds = collect($variantData['variants'])->pluck('id')->toArray();
            $productParams->mockupStyleIds = $variantData['mockup_style_ids'];

            $placements = new ApiMockupGeneratorProductPlacementParams();
            $placements->placement = $variantData['placement'] ?? $placements->placement;
            $placements->technique = $variantData['technique'] ?? $placements->technique;

            $layer = new ApiMockupGeneratorProductPlacementLayerParams();
            $layer->url = $url;

            $placements->layers[] = $layer;

            $productParams->placements[] = $placements;

            $params->products[] = $productParams;
        }

        return $this->api->generateMockups($params);
    }

    /**
     * @param string $url
     * @return ApiMockupGeneratorTask
     * @throws Exception
     */
    public function generateMockupsForVariants(int $productId, array $variantIds, string $url): ApiMockupGeneratorTask
    {
        $params = new ApiMockupGeneratorParams();

        $productData = $this->productMapRepository->getProductMapById($productId);
        if (!$productData) {
            throw new Exception('Failed to schedule generator task for product ' . $productId);
        }

        $productParams = new ApiMockupGeneratorProductParams();
        $productParams->catalogProductId = $productId;
        $productParams->catalogVariantIds = $variantIds;
        $productParams->mockupStyleIds = $productData['mockup_style_ids'];

        $placements = new ApiMockupGeneratorProductPlacementParams();
        $placements->placement = $productData['placement'] ?? $placements->placement;
        $placements->technique = $productData['technique'] ?? $placements->technique;

        $layer = new ApiMockupGeneratorProductPlacementLayerParams();
        $layer->url = $url;

        $placements->layers[] = $layer;

        $productParams->placements[] = $placements;

        $params->products[] = $productParams;

        return $this->api->generateMockups($params)[0];
    }

    public function getGeneratorTaskById(int $generatorTaskId): ?ApiMockupGeneratorTask
    {
        return $this->api->getGeneratorTaskById($generatorTaskId);
    }

    public function processCompletedGeneratorTask(
        ApiMockupGeneratorTask $generatorTask,
        int    $productId,
    ): void {
        $product = TelegramUserProduct::find($productId);

        if (!$product) {
            Log::info('Product not found');
            return;
        }

        foreach ($generatorTask->catalogVariantMockups as $catalogVariantMockup) {
            $variant = TelegramUserVariant::where([
                'telegram_user_product_id' => $productId,
                'variant_id' => $catalogVariantMockup['catalog_variant_id'],
            ])->first();

            if (!$variant) {
                continue;
            }

            $variant->mockup_url = $catalogVariantMockup['mockups'][0]['mockup_url'];
            $variant->status = TelegramUserVariant::STATUS_READY;
            $variant->save();
        }

        Log::info('Finished mockup processing for "' . $product->design_name . '"!');
        $product->status = TelegramUserProduct::STATUS_READY;
        $product->save();

        $notReadyProductsForDesign =TelegramUserProduct::where([
            'telegram_user_id' => $product->telegram_user_id,
            'design_name' => $product->design_name,
        ])
            ->whereIn('status', [
                TelegramUserProduct::STATUS_PENDING,
                TelegramUserProduct::STATUS_PROCESSING,
            ])
            ->get();

        if ($notReadyProductsForDesign->isEmpty()) { // everything is ready
            Telegram::sendMessage([
                'chat_id' => $product->telegram_user_id,
                'text' => "Your new design is ready for sale!",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '👕 Share new design',
                                'switch_inline_query' => 'New design available at: ' . Helpers::getCheckoutDesignLink($product->telegram_user_id, $product->design_name),
                            ],
                            [
                                'text' => '🏪 Share all merch',
                                'switch_inline_query' => 'See my merch at: ' . Helpers::getBrowseStoreLink($product->telegram_user_id),
                            ],
                        ]
                    ]
                ])
            ]);
        }
    }
}
