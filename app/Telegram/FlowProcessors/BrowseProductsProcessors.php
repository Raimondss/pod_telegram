<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Models\TelegramUserProduct;
use App\Telegram\Helpers\Helpers;
use App\Telegram\MessageHelpers\ProductCardMessageHelper;
use App\Telegram\Structures\ProductConfig;
use App\Telegram\Structures\UserState;
use App\Users\Models\TelegramUser;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class BrowseProductsProcessors implements FlowProcessorInterface
{

    public const string FLOW_CHECKOUT_DESIGN = 'checkout_design';
    public const string FLOW_WAITING_DESIGN_SELECTION = 'waiting_design_selection';
    public const string FLOW_WAITING_PRODUCT_CATEGORY_SELECTION = 'waiting_category_selection';
    public const string FLOW_WAITING_COLOR_SELECTION = 'waiting_color_selection';
    public const string FLOW_WAITING_SIZE_SELECTION = 'waiting_size_selection';

    public function __construct(private ProductCardMessageHelper $productCardMessageHelper) {}

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $message = $update->getMessage()->text ?? '';

        if (!$previousState->previousStepKey) {
            $browseProductString = explode(" ", $message)[1] ?? null;

            if (!$browseProductString) {
                Helpers::sendMessage($previousState->userId, "Something went wrong!");
                return UserState::getFreshState($previousState->userId, null);
            }

            $storeOwnerUserId = (int)str_replace('browse_products', '', $browseProductString);

            if (!$storeOwnerUserId) {
                Helpers::sendMessage($previousState->userId, "Something went wrong!");
                return UserState::getFreshState($previousState->userId, null);
            }

            $previousState->extra['storeOwnerUserId'] = $storeOwnerUserId;
            $storeOwnerUser = TelegramUser::whereId($storeOwnerUserId)->first();

            Helpers::sendMessage($previousState->userId, "Welcome to " . $storeOwnerUser->store_name . ' - chose design!');

            $designs = $this->getAvailableDesigns($storeOwnerUserId);

            $sentMessageIds = [];
            foreach ($designs as $design) {
                $uploadedFile = InputFile::create($design['image_url'], "design_file");

                $sentMessage = Telegram::sendPhoto([
                    'chat_id' => $previousState->userId,
                    'photo' => $uploadedFile,
                    'caption' => $design['name'],
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => 'Buy', 'callback_data' => $design['name']]
                            ]
                        ]
                    ])
                ]);

                $sentMessageIds[] = $sentMessage->messageId;
            }

            $previousState->extra['sentMessageIds'] = $sentMessageIds;
            $previousState->previousStepKey = self::FLOW_WAITING_DESIGN_SELECTION;

            return $previousState;
        }

        //TODO Extarct into functions.
        if ($previousState->previousStepKey == self::FLOW_CHECKOUT_DESIGN) {
            $designName = $previousState->extra['designName'];
            $categories = $this->getAvailableCategories($previousState->extra['storeOwnerUserId'], $designName);

            $categoryKeyBoards = [];
            foreach ($categories as $category) {
                $categoryKeyBoards[] = [
                    ['text' => $category, 'callback_data' => $category]
                ];
            }

            $product = TelegramUserProduct::whereDesignName($designName)->first();
            $uploadedFile = InputFile::create($product->uploaded_file_url, "design_file");

            Telegram::sendPhoto([
                'chat_id' => $previousState->userId,
                'photo' => $uploadedFile,
                'caption' => $product->design_name,
            ]);

            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => "Choose product",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $categoryKeyBoards
                ])
            ]);

            $previousState->previousStepKey = self::FLOW_WAITING_PRODUCT_CATEGORY_SELECTION;

            return $previousState;
        }

        if ($previousState->previousStepKey == self::FLOW_WAITING_DESIGN_SELECTION) {
            if (!$update->isType('callback_query')) {
                Helpers::sendMessage($previousState->userId, "Please choose product!");
                return UserState::getFreshState($previousState->userId, null);
            }

            $designName = $update->callbackQuery?->data ?? null;

            if (!$designName) {
                Helpers::sendMessage($previousState->userId, "Something went wrong with product selection");
                return UserState::getFreshState($previousState->userId, null);
            }

            $previousState->extra['designName'] = $designName;

            //Delete sent design messages - so we dont need to handle updates comming in with shity state
            //Not so bad looks cool :D
            foreach ($previousState->extra['sentMessageIds'] ?? [] as $messageId) {
                Telegram::deleteMessage([
                    'chat_id' => $previousState->userId,
                    'message_id' => $messageId
                ]);
                $previousState->extra['sentMessageIds'] = [];
            }

            $categories = $this->getAvailableCategories($previousState->extra['storeOwnerUserId'], $designName);

            $categoryKeyBoards = [];
            foreach ($categories as $category) {
                $categoryKeyBoards[] = [
                    ['text' => $category, 'callback_data' => $category]
                ];
            }

            $product = TelegramUserProduct::whereDesignName($designName)->first();
            $uploadedFile = InputFile::create($product->uploaded_file_url, "design_file");

            Telegram::sendPhoto([
                'chat_id' => $previousState->userId,
                'photo' => $uploadedFile,
                'caption' => $product->design_name,
            ]);

            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => "Choose product!",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $categoryKeyBoards
                ])
            ]);

            $previousState->previousStepKey = self::FLOW_WAITING_PRODUCT_CATEGORY_SELECTION;

            return $previousState;
        }

        //CATEOGRY BLOCK START
        if ($previousState->previousStepKey == self::FLOW_WAITING_PRODUCT_CATEGORY_SELECTION) {
            if (!$update->isType('callback_query')) {
                Helpers::sendMessage($previousState->userId, "Please choose product!");
                return UserState::getFreshState($previousState->userId, null);
            }

            $category = $update->callbackQuery?->data ?? null;

            if (!$category) {
                Helpers::sendMessage($previousState->userId, "Something went wrong with category selection");
                return UserState::getFreshState($previousState->userId, null);
            }

            $previousState->extra['category'] = $category;

            $colors = $this->getAvailableColors(
                $previousState->extra['storeOwnerUserId'],
                $previousState->extra['category'],
                $previousState->extra['designName']);


            $colorKeyboards = [];
            foreach ($colors as $color) {
                $colorKeyboards[] = [
                    ['text' => "$category $color", 'callback_data' => $color]
                ];
            }

            Telegram::editMessageText([
                'chat_id' => $previousState->userId,
                'message_id' => $update->getMessage()->message_id,
                'text' => "Chose color",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $colorKeyboards
                ])
            ]);

            $previousState->previousStepKey = self::FLOW_WAITING_COLOR_SELECTION;
            return $previousState;
        }

        //CATEOGRY BLOCK END

        //COLOR BLOCK START
        if ($previousState->previousStepKey == self::FLOW_WAITING_COLOR_SELECTION) {
            if (!$update->isType('callback_query')) {
                Helpers::sendMessage($previousState->userId, "Something went wrong");
                return UserState::getFreshState($previousState->userId, null);
            }

            $color = $update->callbackQuery?->data ?? null;

            if (!$color) {
                Helpers::sendMessage($previousState->userId, "Something went wrong with color selection");
                return UserState::getFreshState($previousState->userId, null);
            }

            $previousState->extra['color'] = $color;
            $category = $previousState->extra['category'];
            $sizes = $this->getAvailableSizes(
                $previousState->extra['storeOwnerUserId'],
                $category,
                $color,
                $previousState->extra['designName']);


            $sizeKeyboards = [];
            foreach ($sizes as $size) {
                $sizeKeyboards[] = [
                    ['text' => "$category $color $size", 'callback_data' => $size]
                ];
            }

            Telegram::editMessageText([
                'chat_id' => $previousState->userId,
                'message_id' => $update->getMessage()->message_id,
                'text' => "Choose size",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $sizeKeyboards
                ])
            ]);

            $previousState->previousStepKey = self::FLOW_WAITING_SIZE_SELECTION;
            return $previousState;

        }

        if ($previousState->previousStepKey == self::FLOW_WAITING_SIZE_SELECTION) {
            if (!$update->isType('callback_query')) {
                Helpers::sendMessage($previousState->userId, "Something went wrong");
                return UserState::getFreshState($previousState->userId, null);
            }

            $size = $update->callbackQuery?->data ?? null;

            if (!$size) {
                Helpers::sendMessage($previousState->userId, "Something went wrong with size selection");
                return UserState::getFreshState($previousState->userId, null);
            }

            $previousState->extra['size'] = $size;

            $variant = $this->getVariant(
                $previousState->extra['storeOwnerUserId'],
                $previousState->extra['category'],
                $previousState->extra['color'],
                $previousState->extra['designName'],
                $size,
            );

            Telegram::deleteMessage([
                'chat_id' => $previousState->userId,
                'message_id' => $update->getMessage()->message_id,
            ]);

            $this->productCardMessageHelper->sendVariantCard($previousState->userId, $variant);

            return UserState::getFreshState($previousState->userId, null);
        }

        return $previousState;
    }

    //Return ID and URL for IMAGE
    private function getAvailableDesigns(int $storeOwnerUserId): array
    {
        $availableProducts = TelegramUserProduct::where('status', TelegramUserProduct::STATUS_READY)
            ->where('telegram_user_id', $storeOwnerUserId)
            ->groupBy('design_name')
            ->get();

        $availableDesigns = [];
        foreach ($availableProducts as $product) {
            $availableDesigns[] = [
                'image_url' => $product->uploaded_file_url,
                'name' => $product->design_name,
            ];
        }

        return $availableDesigns;
    }

    private function getAvailableCategories(int $storeOwnerUserId, string $designName): array
    {
        $products = TelegramUserProduct::with('variants')
            ->where('status', TelegramUserProduct::STATUS_READY)
            ->where('design_name', $designName)
            ->where('telegram_user_id', $storeOwnerUserId)
            ->groupBy('product_id')
            ->get();

        $categories = [];
        foreach ($products as $product) {
            $categories[] = $product->product_id;
        }

        return array_unique(ProductConfig::getCategoryNamesByIds($categories));
    }

    private function getAvailableColors(int $storeOwnerUserId, string $category, string $designName): array
    {
        $productId = ProductConfig::getCategoryIdByCategoryName($category);

        $products = TelegramUserProduct::with('variants')
            ->where('status', TelegramUserProduct::STATUS_READY)
            ->where('product_id', $productId)
            ->where('design_name', $designName)
            ->where('telegram_user_id', $storeOwnerUserId)
            ->get();

        $colors = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $colors[] = $variant->color;
            }
        }

        return array_unique($colors);
    }

    private function getAvailableSizes(int $storeOwnerUserId, string $category, string $selectedColor, string $designName): array
    {
        $productId = ProductConfig::getCategoryIdByCategoryName($category);
        //TODO REWORK

        $products = TelegramUserProduct::with('variants')
            ->where('status', TelegramUserProduct::STATUS_READY)
            ->where('product_id', $productId)
            ->where('design_name', $designName)
            ->where('telegram_user_id', $storeOwnerUserId)
            ->get();

        $sizes = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                if ($variant->color !== $selectedColor) {
                    continue;
                }
                $sizes[] = $variant->size;
            }
        }

        return array_unique($sizes);
    }

    private function getVariant($storeOwnerUserId, string $category, string $selectedColor, string $designName, string $selectedSize): int
    {
        $productId = ProductConfig::getCategoryIdByCategoryName($category);
        //TODO REWORK

        $products = TelegramUserProduct::with('variants')
            ->where('status', TelegramUserProduct::STATUS_READY)
            ->where('product_id', $productId)
            ->where('design_name', $designName)
            ->where('telegram_user_id', $storeOwnerUserId)
            ->get();

        $variants = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                if ($variant->color !== $selectedColor) {
                    continue;
                }
                if ($variant->size !== $selectedSize) {
                    continue;
                }

                $variants[] = $variant->id;
            }
        }

        return $variants[0];
    }
}
