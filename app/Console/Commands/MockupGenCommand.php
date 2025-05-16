<?php

namespace App\Console\Commands;

use App\Services\MockupGeneratorService;
use App\Services\ProductGeneratorService;
use Illuminate\Console\Command;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * @deprecated
 */
class MockupGenCommand extends Command
{
    public function __construct(
        private MockupGeneratorService $mockupGeneratorService,
    ) {
        return parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:mockup-gen-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updates = Telegram::getUpdates();

        foreach ($updates as $update) {
            $chatId = $update['message']['chat']['id'];

            $photos = $update['message']['photo'] ?? null;
            if ($photos) {

                $fileId = $this->getLargestFileId($photos);
                if (!$fileId) {
                    continue;
                }

                $file = Telegram::getFile([
                    'file_id' => $fileId,
                ]);

                $filePath = $file->getFilePath();

                $fileUrl = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $filePath;

                $result = $this->mockupGeneratorService->generateMockupsByUrl($fileUrl);

                foreach ($result as $task) {
                    while (true) {
                        $message = 'Waiting for mockups...';

                        Telegram::sendMessage([
                            'chat_id' => $chatId,
                            'text' => $message,
                        ]);

                        sleep(1);

                        $taskResult = $this->mockupGeneratorService->getGeneratorTaskById($task->id);
                        if (!$taskResult) {
                            break;
                        }

                        if ($taskResult->isComplete()) {

                            foreach ($taskResult->catalogVariantMockups as $catalogVariant) {
                                $mockups = [];
                                foreach ($catalogVariant['mockups'] as $mockup) {
                                    $mockups[] = $mockup['mockup_url'];
                                }
                            }

                            foreach ($mockups as $mockup) {
                                Telegram::sendPhoto([
                                    'chat_id' => $chatId,
                                    'photo' => InputFile::create($mockup),
                                    'caption' => 'Here is your mockup!'
                                ]);
                            }

                            break;
                        }
                    }
                }

                break;
            }
        }
    }

    private function getLargestFileId(array $files): ?string {
        if (empty($files)) {
            return null;
        }

        $largest = array_reduce($files, function ($carry, $item) {
            return ($carry === null || $item['file_size'] > $carry['file_size']) ? $item : $carry;
        });

        return $largest['file_id'] ?? null;
    }
}
