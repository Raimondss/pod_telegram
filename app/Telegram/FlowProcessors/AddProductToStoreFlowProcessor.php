<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Jobs\ProcessGenerateMockupsTasks;
use App\Services\MockupGeneratorService;
use App\Structures\Api\ApiMockupGeneratorTask;
use App\Telegram\Structures\UserState;
use App\Telegram\UpdateProcessor;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class AddProductToStoreFlowProcessor implements FlowProcessorInterface
{
    public const string STATE_INITIAL = 'initial';
    public const string STATE_WAITING_IMAGE = 'image';
    public const string STATE_GENERATING_MOCKUPS = 'generating-mockups';
    public const string STATE_WAITING_PRODUCT_SELECTION = 'waiting-product-selection';

    public const TEXT_UPLOAD_YOUR_DESIGN = 'Please upload you design...';
    public const TEXT_SELECT_PRODUCTS = 'Please select desired products by providing comma(,) seperated ids...';

    private const string STEP_WAIT_IMAGE_UPLOAD = 'waiting_image_upload';

    public function __construct(private readonly MockupGeneratorService $mockupGeneratorService) {}

    public function startFlow(int $userId, Update $update, int $selectedStoreId): UserState
    {
        $state = UserState::getFreshState($userId, UpdateProcessor::ADD_PRODUCT_TO_STORE_FLOW_KEY, [
            'selectedStoreId' => $selectedStoreId,
        ]);

        return $this->processUserState($state, $update);
    }

    //Use current state + update - to send messages to user and return new state.
    public function processUserState(UserState $previousState, Update $update): UserState
    {
        //Flow just started - no previous step taken - ask to upload image
        if (!$previousState->previousStepKey) {
            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => self::TEXT_UPLOAD_YOUR_DESIGN,
            ]);

            $previousState->previousStepKey = self::STEP_WAIT_IMAGE_UPLOAD;

            return $previousState;
        }

        //We promoted to upload image and this $update should be a message
        if ($previousState->previousStepKey == self::STEP_WAIT_IMAGE_UPLOAD) {
            $photos = $update->getMessage()->photo?->toArray();

            if (!$photos) {
                Telegram::sendMessage(
                    [
                        'chat_id' => $previousState->userId,
                        'text' => self::TEXT_UPLOAD_YOUR_DESIGN,
                    ]
                );

                return $previousState;
            }

            $largestFileId = $this->getLargestFileId($photos);

            $file = Telegram::getFile([
                'file_id' => $largestFileId,
            ]);

            $filePath = $file->getFilePath();

            $fileUrl = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $filePath;

            $tasks = $this->mockupGeneratorService->generateMockupsByUrl($fileUrl);
            $taskIds = array_map(
                static fn(ApiMockupGeneratorTask $task) => $task->id,
                $tasks
            );

            ProcessGenerateMockupsTasks::dispatch($previousState->userId, $fileUrl, $taskIds);

            Telegram::sendMessage(
                [
                    'chat_id' => $previousState->userId,
                    'text' => 'Design received. Generating mockups...',
                ]
            );

            $previousState->previousStepKey = self::STATE_GENERATING_MOCKUPS;

            return $previousState;
        }

        return $previousState;
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

    protected function extractIntegers(string $message): array
    {
        return array_values(array_filter(
            array_map(function ($item) {
                $trimmed = trim($item);
                return filter_var($trimmed, FILTER_VALIDATE_INT) !== false ? (int)$trimmed : null;
            }, explode(',', $message)),
            static fn($item) => !is_null($item)
        ));
    }
}
