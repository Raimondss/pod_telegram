<?php

declare(strict_types=1);

namespace App\Services;

use App\APIs\PrintfulApi;
use App\Params\ApiMockupGeneratorParams;
use App\Params\ApiMockupGeneratorProductParams;
use App\Params\ApiMockupGeneratorProductPlacementLayerParams;
use App\Params\ApiMockupGeneratorProductPlacementParams;
use App\Structures\Api\ApiMockupGeneratorTask;
use App\Telegram\FlowProcessors\AddProductToStoreFlowProcessor;
use App\Telegram\UpdateProcessor;
use App\Telegram\UserStateService;
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
                    10421
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

    public function getGeneratorTaskById(int $generatorTaskId): ?ApiMockupGeneratorTask
    {
        return $this->api->getGeneratorTaskById($generatorTaskId);
    }

    /**
     * @param int[] $taskIds
     * @return ApiMockupGeneratorTask[]
     */
    public function getGeneratorTasksByIds(array $taskIds): array
    {
        return $this->api->getGeneratorTasksByIds($taskIds);
    }

    /**
     * @param ApiMockupGeneratorTask[] $generatorTasks
     * @param int $userId
     * @param string $fileUrl
     * @return void
     */
    public function processCompletedGeneratorTasks(
        array  $generatorTasks,
        int    $userId,
        string $fileUrl
    ): void
    {
        Telegram::sendMessage(
            [
                'chat_id' => $userId,
                'text' => 'Mockups are finished!',
            ]
        );

        var_dump($generatorTasks);

        foreach ($generatorTasks as $generatorTask) {
            Telegram::sendMessage(
                [
                    'chat_id' => $userId,
                    'text' => 'Tasks we have!',
                ]
            );

            foreach ($generatorTask->catalogVariantMockups as $catalogVariantMockup) {
                $product = $this->findProductByVariantId($catalogVariantMockup['catalog_variant_id']);
                if (!$product) {
                    continue;
                }

                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => $product['title'] . '(ID:' . $product['id'] . ')',
                    ]
                );

                foreach ($catalogVariantMockup['mockups'] as $mockup) {
                    Telegram::sendPhoto([
                        'chat_id' => $userId,
                        'photo' => InputFile::create($mockup['mockup_url']),
                    ]);
                }
            }
        }

        Telegram::sendMessage(
            [
                'chat_id' => $userId,
                'text' => 'Mockups usser state!',
            ]
        );

        $userState = $this->userStateService->getUserState($userId);

        if ($userState->getStartedFlowKey() != UpdateProcessor::ADD_PRODUCT_TO_STORE_FLOW_KEY) {
            //Customer already started different flow while waiting
            return;
        }

        $userState->previousStepKey = AddProductToStoreFlowProcessor::STATE_WAITING_PRODUCT_SELECTION;
        /**
         * @type AddProductToStoreFlowProcessor $storeFlow
         */
        $storeFlow = app(AddProductToStoreFlowProcessor::class);
        $storeFlow->processUserState($userState, new Telegram\Bot\Objects\Update([]));

        //TODO Resume AddProductToStoreFlowProcessor with new state.
    }

    protected function findProductByVariantId(int $variantId): array
    {
        foreach ($this->getProductMap() as $product) {
            if (in_array($variantId, $product['variant_ids'], true)) {
                return $product;
            }
        }

        return [];
    }
}
