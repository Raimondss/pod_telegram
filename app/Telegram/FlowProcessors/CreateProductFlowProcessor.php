<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Models\TelegramUserProduct;
use App\Telegram\Structures\UserState;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class CreateProductFlowProcessor implements FlowProcessorInterface
{
    public const STEP_WAITING_IMAGE = 'waiting_image';

    public const STEP_WAITING_PRODUCT_NAME = 'waiting_product_name';

    public const STEP_WAITING_PROFIT_MARGIN = 'waiting_profit_margin';

    public const string REQUEST_IMAGES_TEXT = 'Please provide images';
    public const string REQUEST_PRODUCT_NAME_TEXT = 'How would you like to call your product?';

    const string REQUEST_PROFIT_MARIN_TEXT = 'What should be profit margin?';


    public function processUserState(UserState $previousState, Update $update): UserState
    {
        if (!$previousState->previousStepKey) {
            $this->sendMessage($previousState->userId, self::REQUEST_PRODUCT_NAME_TEXT);
            $previousState->previousStepKey = self::STEP_WAITING_PRODUCT_NAME;

            return $previousState;
        }

        if ($previousState->previousStepKey === self::STEP_WAITING_PRODUCT_NAME) {
            $productName = $update->getMessage()->text ?? null;

            $previousState->extra['product_name'] = $productName;

            $this->sendMessage($previousState->userId, self::REQUEST_PROFIT_MARIN_TEXT);
            $previousState->previousStepKey = self::STEP_WAITING_PROFIT_MARGIN;

            return $previousState;
        }

        if ($previousState->previousStepKey === self::STEP_WAITING_PROFIT_MARGIN) {
            $profitMargin = $update->getMessage()->text ?? null;

            if (!$profitMargin || is_int($profitMargin)) {
                $this->sendMessage($previousState->userId, "Please provide correct value");
                return $previousState;
            }

            $previousState->extra['profit_margin'] = $profitMargin;

            $this->sendMessage($previousState->userId, self::REQUEST_IMAGES_TEXT);
            $previousState->previousStepKey = self::STEP_WAITING_IMAGE;

            return $previousState;
        }


        if ($previousState->previousStepKey === self::STEP_WAITING_IMAGE) {
            $photos = $update->getMessage()->photo?->toArray();

            if (!$photos) {
                $this->sendMessage($previousState->userId, self::REQUEST_IMAGES_TEXT);
                return $previousState;
            }

            foreach ($photos as $photo) {
                $file = Telegram::getFile([
                    'file_id' => $photo->file_id,
                ]);

                $filePath = $file->getFilePath();
                $fileUrl = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $filePath;
                $this->createVariantsFromFileUrl($previousState->userId, $fileUrl, $previousState->extra['']);
            }

            //NO PHOTOS - SEND MESSAGE ASK FOR PHOTOS AGAIN
            //TODO QUEUE PRODUCT CREATION JOB
        }

        if ($previousState->previousStepKey == self::STEP_SET_PROFIT_MARGIN) {
            //TODO PROCESS UPDATE AS IT WOULD BE PROFIT MARGIN + MOVE TO NEXT STEP
        }
    }

    public function sendMessage(int $chatId, string $message): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }

    public function createVariantsFromFileUrl(int $userId, string $imageUrl, int $marginPercentage, string $userProductName): void
    {
        $map = $this->getGeneratedProductsMap();

        $telegramUserProducts = [];
        foreach ($map as $productId => $productData) {
            $namePrefix = $productData['name_prefix'];
            foreach ($productData['variants'] as $variant) {
                $id = $variant['id'];
                $color = $variant['color'];
                $size = $variant['size'];
                $basePrice = $variant['base_price'];
                $telegramUserProducts[] = [
                    'telegram_user_id' => $userId,
                    'color' => $color,
                    'variant_id' => $id,
                    'product_id' => $productId,
                    'size' => $size,
                    'product_name' => $namePrefix . ' ' . $userProductName,
                    'price' => $basePrice + ($basePrice * $marginPercentage),
                ];
            }
        }

        if ($telegramUserProducts !== []) {
            TelegramUserProduct::insert($telegramUserProducts);
            dump('Products are saved');
        } else {
            dump('No products were created');
        }
    }

    protected function getGeneratedProductsMap(): array
    {
        return [
            71 => [ // bella canvas
                'id' => 71,
                'name_prefix' => 'Shirt',
                'variants' => [
                    [
                        'id' => 4011,
                        'size' => 'M',
                        'color' => 'White',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4017,
                        'size' => 'M',
                        'color' => 'Black',
                        'base_price' => 2000
                    ],
                    [
                        'id' => 4082,
                        'size' => 'M',
                        'color' => 'Gold',
                        'base_price' => 2000
                    ],
                ],
            ],
            19 => [ // Glossy mug
                'id' => 19,
                'name_prefix' => 'Glossy Mug',
                'variants' => [
                    [
                        'id' => 1320,
                        'size' => '11 oz',
                        'color' => 'White',
                        'base_price' => 1500
                    ],
                ],
                'placement' => 'default',
                'technique' => 'sublimation',
            ],
        ];
    }
}
