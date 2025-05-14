<?php

declare(strict_types=1);

namespace App\Params;

class ApiMockupGeneratorParams
{
    public string $format = "jpg";

    /** @var ApiMockupGeneratorProductParams[] */
    public array $products = [];

    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'products' => array_map(
                static fn (ApiMockupGeneratorProductParams $product) => $product->toArray(),
                $this->products
            ),
        ];
    }
}
