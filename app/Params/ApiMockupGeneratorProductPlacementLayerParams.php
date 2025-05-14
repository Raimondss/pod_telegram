<?php

declare(strict_types=1);

namespace App\Params;

class ApiMockupGeneratorProductPlacementLayerParams
{
    public string $type = 'file';
    public string $url = '';

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
