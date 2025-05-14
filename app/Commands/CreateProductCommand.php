<?php

namespace App\Commands;

use App\Jobs\GenerateMockups;
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

    public const TEXT_UPLOAD_YOUR_DESIGN = 'Please upload you design...';

    public function __construct(private readonly UserStateService $userStateService) {}

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

                GenerateMockups::dispatch($userId, $fileUrl);

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

    public function getStartState(): string
    {
        return self::STATE_INITIAL;
    }
}
