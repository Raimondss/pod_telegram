<?php

declare(strict_types=1);

namespace App\Params;

class ApiMockupGeneratorProductPlacementParams
{
    public string $placement = 'front';
    public string $technique = 'dtg';

    /** @var ApiMockupGeneratorProductPlacementLayerParams[] */
    public array $layers = [];

    public function toArray(): array
    {
        return [
            'placement' => $this->placement,
            'technique' => $this->technique,
            'layers' => array_map(
                static fn (ApiMockupGeneratorProductPlacementLayerParams $params) => $params->toArray(),
                $this->layers
            ),
        ];
    }
}
