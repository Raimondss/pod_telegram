<?php

declare(strict_types=1);

namespace App\Repository;

use App\Telegram\Structures\ProductConfig;

class ProductMapRepository
{
    public function getProductMap(): array
    {
        return [
            ProductConfig::CATEGORY_T_SHIRT_BELLA_CANVAS => [ // bella canvas
                'id' => 71,
                'title' => 'Bella Canvas',
                'category' => ProductConfig::CATEGORY_T_SHIRT_BELLA_CANVAS,
                'variants' => [
                    [
                        'id' => 4011,
                        'size' => 'S',
                        'color' => 'White',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4012,
                        'size' => 'M',
                        'color' => 'White',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4013,
                        'size' => 'L',
                        'color' => 'White',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4016,
                        'size' => 'S',
                        'color' => 'Black',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4017,
                        'size' => 'M',
                        'color' => 'Black',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4018,
                        'size' => 'L',
                        'color' => 'Black',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4081,
                        'size' => 'S',
                        'color' => 'Gold',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4082,
                        'size' => 'M',
                        'color' => 'Gold',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4083,
                        'size' => 'L',
                        'color' => 'Gold',
                        'base_price' => 2000
                    ],
                ],
                'mockup_style_ids' => [
                    744
                ],
            ],
            ProductConfig::CATEGORY_MUG_GLOSSY_MUG => [ // Glossy mug
                'id' => 19,
                'title' => 'White Glossy Mug',
                'category' => ProductConfig::CATEGORY_MUG_GLOSSY_MUG,
                'variants' => [
                    [
                        'id' => 1320,
                        'size' => '11 oz',
                        'color' => 'White',
                        'base_price' => 1500
                    ],
//                    [
//                        'id' => 4830,
//                        'size' => '15 oz',
//                        'color' => 'White',
//                        'base_price' => 1500
//                    ],
                ],
                'mockup_style_ids' => [
                    10423, // office env
//                    10438, // office env
                ],
                'placement' => 'default',
                'technique' => 'sublimation',
            ],
            ProductConfig::CATEGORY_POSTER => [ // poster
                'id' => 1,
                'title' => 'Poster',
                'category' => ProductConfig::CATEGORY_POSTER,
                'variants' => [
                    [
                        'id' => 3876,
                        'size' => '12x18',
                        'base_price' => 1500
                    ],
//                    [
//                        'id' => 6240,
//                        'size' => '14x14',
//                        'base_price' => 1500
//                    ],
                ],
                'mockup_style_ids' => [
                    9093, // Life style
                ],
                'placement' => 'default',
                'technique' => 'digital',
            ],
        ];
    }

    public function getProductMapById(int $id): ?array
    {
        return $this->getProductMap()[$id] ?? null;
    }
}
