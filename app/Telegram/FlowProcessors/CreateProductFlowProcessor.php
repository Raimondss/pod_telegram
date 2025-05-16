<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Models\TelegramUserProduct;
use App\Models\TelegramUserVariant;
use App\Repository\ProductMapRepository;
use App\Telegram\Structures\ProductConfig;
use App\Telegram\Structures\UserState;
use App\Users\Services\TelegramUserService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class CreateProductFlowProcessor implements FlowProcessorInterface
{
    public const STEP_WAITING_IMAGE = 'waiting_image';

    public const STEP_WAITING_STORE_NAME = 'waiting_store_name';

    public const STEP_WAITING_PRODUCT_NAME = 'waiting_product_name';

    public const STEP_WAITING_PROFIT_MARGIN = 'waiting_profit_margin';

    public const string REQUEST_IMAGES_TEXT = 'Please provide your design';
    public const string REQUEST_PRODUCT_NAME_TEXT = 'How would you like to call your design?';

    const string REQUEST_PROFIT_MARIN_TEXT = 'What should be profit margin %?';

    public function __construct(
        private TelegramUserService $telegramUserService,
        private ProductMapRepository $productMapRepository,
    ) {}

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $user = $this->telegramUserService->findOrCreateUserFromUpdate($update);

        //If user has no store name yet - ask to provide one
        if (!$user->store_name && !$previousState->previousStepKey) {
            $this->sendMessage($previousState->userId, "You have no store created, lets change that, how would you like to call your store?");

            $previousState->previousStepKey = self::STEP_WAITING_STORE_NAME;

            return $previousState;
        }

        if ($previousState->previousStepKey == self::STEP_WAITING_STORE_NAME) {
            $storeName = $update->getMessage()->text ?? null;
            $user->store_name = $storeName;
            $user->save();

            $this->sendMessage($previousState->userId, "Store created! - Users will be able to open your store and pruchase products!");
            $this->sendMessage($previousState->userId, "Lets continue with product creation!");

            $previousState->previousStepKey = null;
        }

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

            $previousState->extra['profit_margin'] = (int)$profitMargin;

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

            $largestFileId = $this->getLargestFileId($photos);

            $file = Telegram::getFile([
                'file_id' => $largestFileId,
            ]);

            $filePath = $file->getFilePath();
            $fileUrl = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $filePath;
            $this->createVariantsFromFileUrl($previousState->userId, $fileUrl, $previousState->extra['profit_margin'], $previousState->extra['product_name']);

            $this->sendMessage($previousState->userId, "We are generating your products. Stay tuned...");

            //TODO Move user to my products flow?
            return UserState::getFreshState($previousState->userId, null);
        }

        return $previousState;
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
        $map = $this->productMapRepository->getProductMap();

        $telegramUserProducts = [];
        foreach ($map as $productId => $productData) {
            $telegramUserProducts[] = TelegramUserProduct::create([
                'telegram_user_id' => $userId,
                'product_id' => $productId,
                'design_name' => str_replace(' ', '-', $userProductName),
                'uploaded_file_url' => $imageUrl,
                'status' => TelegramUserVariant::STATUS_PENDING,
                'category' => $productData['category'],
            ]);
        }

        foreach ($telegramUserProducts as $product) {
            $variantsData = $this->productMapRepository->getProductMapById($product->product_id)['variants'];

            foreach ($variantsData as $variantData) {
                $id = $variantData['id'];
                $color = $variantData['color'];
                $size = $variantData['size'];
                $basePrice = $variantData['base_price'];

                TelegramUserVariant::insert([
                    'telegram_user_product_id' => $product->id,
                    'color' => $color,
                    'variant_id' => $id,
                    'size' => $size,
                    'status' => TelegramUserVariant::STATUS_PENDING,
                    'price' => $basePrice + ($basePrice * ($marginPercentage / 100)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function getLargestFileId(array $files): ?string
    {
        if (empty($files)) {
            return null;
        }

        $largest = array_reduce(
            $files,
            static fn($carry, $item) => ($carry === null || $item['file_size'] > $carry['file_size']) ? $item : $carry
        );

        return $largest['file_id'] ?? null;
    }
}
