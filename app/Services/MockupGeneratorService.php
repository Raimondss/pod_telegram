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
use App\Structures\Api\ApiMockupGeneratorTask;
use App\Telegram\FlowProcessors\AddProductToStoreFlowProcessor;
use App\Telegram\UpdateProcessor;
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
    ) {}

    protected function getProductMap(): array
    {
        return [
            71 => [ // bella canvas
                'id' => 71,
                'title' => 'Bella Canvas',
                'variant_ids' => [
                    4011, // White + M
                    4017, // Black + M
                    4082 // Gold + M
                ],
                'mockup_style_ids' => [
                    744
                ],
            ],
            19 => [ // Glossy mug
                'id' => 19,
                'title' => 'White Glossy Mug',
                'variant_ids' => [
                    1320, // 11 oz
                ],
                'mockup_style_ids' => [
                    10423,
                ],
                'placement' => 'default',
                'technique' => 'sublimation',
            ],
        ];
    }

    /**
     * @param string $url
     * @return ApiMockupGeneratorTask[]
     */
    public function generateMockupsByUrl(string $url): array
    {
        $productMap = $this->getProductMap();

        $params = new ApiMockupGeneratorParams();

        foreach ($productMap as $productId => $variantData) {
            $productParams = new ApiMockupGeneratorProductParams();
            $productParams->catalogProductId = $productId;
            $productParams->catalogVariantIds = $variantData['variant_ids'];
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

        $productData = $this->getProductMap()[$productId] ?? null;
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

        Telegram::sendMessage(
            [
                'chat_id' => $product->telegram_user_id,
                'text' => 'Mockups are finished for "' . $product->design_name . '"!',
            ]
        );

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

            Telegram::sendPhoto(
                [
                    'chat_id' => $product->telegram_user_id,
                    'photo' => InputFile::create($variant->mockup_url),
                ]
            );
        }

        Log::info('Finished mockup processing for "' . $product->design_name . '"!');
        $product->status = TelegramUserProduct::STATUS_READY;
        $product->save();
    }
}
