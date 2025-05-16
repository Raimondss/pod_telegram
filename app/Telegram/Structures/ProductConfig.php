<?php

declare(strict_types=1);

namespace App\Telegram\Structures;

class ProductConfig
{
    public const int CATEGORY_T_SHIRT_BELLA_CANVAS = 71;
    public const int CATEGORY_MUG_GLOSSY_MUG = 19;
    public const int CATEGORY_POSTER = 1;

    public const CATEGORY_NAME_TSHIRT = 'T-shirt';
    public const CATEGORY_NAME_MUG = 'Mug';
    public const CATEGORY_NAME_POSTER = 'Poster';

   public const array CONFIG_CATEGORY_PRODUCT_ID_MAP = [
       self::CATEGORY_NAME_TSHIRT => self::CATEGORY_T_SHIRT_BELLA_CANVAS,
       self::CATEGORY_NAME_MUG => self::CATEGORY_MUG_GLOSSY_MUG,
       self::CATEGORY_NAME_POSTER => self::CATEGORY_POSTER,
   ];

   public const array CONFIG_PRODUCT_ID_CATEGORY_MAP = [
       self::CATEGORY_T_SHIRT_BELLA_CANVAS => self::CATEGORY_NAME_TSHIRT,
       self::CATEGORY_MUG_GLOSSY_MUG => self::CATEGORY_NAME_MUG,
       self::CATEGORY_POSTER => self::CATEGORY_NAME_POSTER,
   ];

    public static function getCategoryIdByCategoryName(string $categoryName): ?int
    {
        if (array_key_exists($categoryName, self::CONFIG_CATEGORY_PRODUCT_ID_MAP)) {
            return self::CONFIG_CATEGORY_PRODUCT_ID_MAP[$categoryName];
        }

        return null;
    }

    /**
     * @param int[] $ids
     */
    public static function getCategoryNamesByIds(array $ids): array
    {
        $categoryNames = [];
        foreach (self::CONFIG_PRODUCT_ID_CATEGORY_MAP as $categoryId => $categoryName) {
            if (!in_array($categoryId, $ids)) {
                continue;
            }
            $categoryNames[] = $categoryName;
        }

        return $categoryNames;
    }
}
