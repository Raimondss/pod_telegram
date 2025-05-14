<?php

namespace App\Jobs;

use App\APIs\PrintfulApi;
use App\Params\ApiMockupGeneratorParams;
use App\Services\MockupGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMockups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $userId,
        private string $fileUrl,
        private MockupGeneratorService $mockupGeneratorService
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->mockupGeneratorService->generateMockupsByUrl($this->fileUrl);

    }
}
