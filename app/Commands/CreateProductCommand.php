<?php

namespace App\Commands;

use App\Jobs\ProcessGenerateMockupsTasks;
use App\Services\MockupGeneratorService;
use App\Structures\Api\ApiMockupGeneratorTask;
use App\Telegram\UserStateService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;


/**
 * This command can be triggered by /create_product
 */
class CreateProductCommand implements CommandInterface
{
    public const string COMMAND_CREATE_PRODUCT = 'create_product';

    public const string STATE_INITIAL = 'initial';
    public const string STATE_WAITING_IMAGE = 'image';
    public const string STATE_GENERATING_MOCKUPS = 'generating-mockups';
    public const string STATE_WAITING_PRODUCT_SELECTION = 'waiting-product-selection';

    public const TEXT_UPLOAD_YOUR_DESIGN = 'Please upload you design...';
    public const TEXT_SELECT_PRODUCTS = 'Please select desired products by providing comma(,) seperated ids...';

    public function __construct(
        private readonly UserStateService $userStateService,
        private readonly MockupGeneratorService $mockupGeneratorService
    ) {}

    public function process(Update $update): void
    {
        $userId = $update->getMessage()->from->id;

        $state = $this->userStateService->getUserState($userId);

        $commandState = $state[1];

        $this->processState($commandState, $update);
    }

    public function processState(string $state, Update $update): void
    {
        $userId = $update->getMessage()->from->id;
        $message = $update->getMessage()->text ?? '';

        switch ($state) {
            case self::STATE_INITIAL:
                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => self::TEXT_UPLOAD_YOUR_DESIGN,
                    ]
                );

                $this->userStateService->setUserState($userId, self::COMMAND_CREATE_PRODUCT, self::STATE_WAITING_IMAGE);

                return;
            case self::STATE_WAITING_IMAGE:
                $photos = $update->getMessage()->photo?->toArray();

                if (!$photos) {
                    Telegram::sendMessage(
                        [
                            'chat_id' => $userId,
                            'text' => self::TEXT_UPLOAD_YOUR_DESIGN,
                        ]
                    );
                    return;
                }

                $largestFileId = $this->getLargestFileId($photos);


                $file = Telegram::getFile([
                    'file_id' => $largestFileId,
                ]);

                $filePath = $file->getFilePath();

                $fileUrl = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $filePath;

                $tasks = $this->mockupGeneratorService->generateMockupsByUrl($fileUrl);
                $taskIds = array_map(
                    static fn (ApiMockupGeneratorTask $task) => $task->id,
                    $tasks
                );

                ProcessGenerateMockupsTasks::dispatch($userId, $fileUrl, $taskIds);

                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => 'Design received. Generating mockups...',
                    ]
                );

                $this->userStateService->setUserState($userId, self::COMMAND_CREATE_PRODUCT, self::STATE_GENERATING_MOCKUPS);

                return;
            case self::STATE_GENERATING_MOCKUPS:
                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => 'Hold up. I\'m still generating mockups..',
                    ]
                );

                return;
            case self::STATE_WAITING_PRODUCT_SELECTION:
                $ids = $this->extractIntegers($message);

                if (!$ids) {
                    Telegram::sendMessage(
                        [
                            'chat_id' => $userId,
                            'text' => self::TEXT_SELECT_PRODUCTS,
                        ]
                    );

                    return;
                }

                // create products

                // todo create products

                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => 'Products created!',
                    ]
                );
                break;
            default:
                Telegram::sendMessage(
                    [
                        'chat_id' => $userId,
                        'text' => "State:" . $state . " not mapped",
                    ]
                );
        }
    }

    private function getLargestFileId(array $files): ?string
    {
        if (empty($files)) {
            return null;
        }

        $largest = array_reduce(
            $files,
            static fn ($carry, $item) => ($carry === null || $item['file_size'] > $carry['file_size']) ? $item : $carry
        );

        return $largest['file_id'] ?? null;
    }

    protected function extractIntegers(string $message): array
    {
        return array_values(array_filter(
            array_map(function ($item) {
                $trimmed = trim($item);
                return filter_var($trimmed, FILTER_VALIDATE_INT) !== false ? (int) $trimmed : null;
            }, explode(',', $message)),
            static fn($item) => !is_null($item)
        ));
    }

    public function getStartState(): string
    {
        return self::STATE_INITIAL;
    }
}
