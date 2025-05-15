<?php

namespace App\Jobs;

use App\Services\MockupGeneratorService;
use App\Structures\Api\ApiMockupGeneratorTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGenerateMockupsTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RELEASE_DELAY_SECONDS = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $userId,
        private string $fileUrl,
        private array $taskIds,
        private MockupGeneratorService $mockupGeneratorService
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tasks = $this->mockupGeneratorService->getGeneratorTasksByIds($this->taskIds);

        if (!$this->areAllTasksCompleted($tasks)) {
            $this->release(self::RELEASE_DELAY_SECONDS);
            return;
        }

        $this->mockupGeneratorService->processCompletedGeneratorTasks(
            $tasks,
            $this->userId,
            $this->fileUrl
        );
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
