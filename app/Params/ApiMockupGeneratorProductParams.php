<?php

declare(strict_types=1);

namespace App\Params;

class ApiMockupGeneratorProductParams
{
    public string $source = 'catalog';
    public string $orientation = 'vertical';
    public int $catalogProductId;
    public array $catalogVariantIds = [];
    public array $mockupStyleIds = [];

    /** @var ApiMockupGeneratorProductPlacementParams[] */
    public array $placements = [];

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'orientation' => $this->orientation,
            'catalog_product_id' => $this->catalogProductId,
            'catalog_variant_ids' => $this->catalogVariantIds,
            'mockup_style_ids' => $this->mockupStyleIds,
            'placements' => array_map(
                static fn (ApiMockupGeneratorProductPlacementParams $params) => $params->toArray(),
                $this->placements
            ),
        ];
    }
}
