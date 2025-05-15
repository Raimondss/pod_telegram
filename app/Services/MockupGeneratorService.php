<?php

declare(strict_types=1);

namespace App\Services;

use App\APIs\PrintfulApi;
use App\Params\ApiMockupGeneratorParams;
use App\Params\ApiMockupGeneratorProductParams;
use App\Params\ApiMockupGeneratorProductPlacementLayerParams;
use App\Params\ApiMockupGeneratorProductPlacementParams;
use App\Structures\Api\ApiMockupGeneratorTask;

class MockupGeneratorService
{
    public function __construct(private PrintfulApi $api)
    {
    }

    /**
     * @param string $url
     * @return ApiMockupGeneratorTask[]
     */
    public function generateMockupsByUrl(string $url): array
    {
        $productMap = [
            71 => [ // bella canvas
                'variant_ids' => [
                    4011, // White + M
                    4012,
                    4013
                ],
                'mockup_style_ids' => [
                    744
                ],
            ],
        ];

        $params = new ApiMockupGeneratorParams();

        foreach ($productMap as $productId => $variantData) {
            $productParams = new ApiMockupGeneratorProductParams();
            $productParams->catalogProductId = $productId;
            $productParams->catalogVariantIds = $variantData['variant_ids'];
            $productParams->mockupStyleIds = $variantData['mockup_style_ids'];

            $placements = new ApiMockupGeneratorProductPlacementParams();

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
}
