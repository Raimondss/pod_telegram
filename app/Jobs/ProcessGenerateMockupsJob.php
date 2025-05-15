<?php

namespace App\Jobs;

use App\Services\MockupGeneratorService;
use App\Structures\Api\ApiMockupGeneratorTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class ProcessGenerateMockupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RELEASE_DELAY_SECONDS = 1; // TODO increase for production

    public int $tries = 100; // max 100 attempts

    public function backoff(): int
    {
        return 1; // retry after 1 seconds
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $productId,
        private int $taskId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var MockupGeneratorService $mockupGeneratorService */
        $mockupGeneratorService = app()->make(MockupGeneratorService::class);

        $task = $mockupGeneratorService->getGeneratorTaskById($this->taskId);

        if (!$task->isComplete()) {
            Log::info('Task is not completed(Status: ' . $task->status . '). Putting back in queue...');
            $this->release(self::RELEASE_DELAY_SECONDS);
            return;
        }

        Log::info('All tasks are completed. Processing...');

        $mockupGeneratorService->processCompletedGeneratorTask(
            $task,
            $this->productId
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed: " . $exception->getMessage(), [
            'exception' => $exception,
            'userId' => $this->userId,
            'fileUrl' => $this->fileUrl,
            'taskIds' => $this->taskIds,
        ]);
    }

    /**
     * @param ApiMockupGeneratorTask[] $tasks
     * @return bool
     */
    protected function areAllTasksCompleted(array $tasks): bool
    {
        foreach ($tasks as $task) {
            if (!$task->isComplete()) {
                return false;
            }
        }

        return true;
    }
}
