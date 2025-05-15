<?php

declare(strict_types=1);

namespace App\APIs;

use App\Clients\PrintfulApiClient;
use App\Params\ApiMockupGeneratorParams;
use App\Structures\Api\ApiMockupGeneratorTask;
use Log;

class PrintfulApi
{
    public function __construct(private PrintfulApiClient $client)
    {
    }

    /**
     * @param ApiMockupGeneratorParams $params
     * @return ApiMockupGeneratorTask[]
     */
    public function generateMockups(ApiMockupGeneratorParams $params): array
    {
        $response = $this->client->post(
            '/v2/mockup-tasks',
            $params->toArray(),
        );

        return array_map(
            static fn (array $data): ApiMockupGeneratorTask => ApiMockupGeneratorTask::fromArray($data),
            $response['data'] ?? []
        );
    }

    public function getGeneratorTaskById(int $id): ?ApiMockupGeneratorTask
    {
        $response = $this->client->get(
            '/v2/mockup-tasks',
            ['id' => $id]
        );

        $taskData = $response['data'][0] ?? [];

        return $taskData ? ApiMockupGeneratorTask::fromArray($taskData) : null;
    }

    /**
     * @param array $ids
     * @return ApiMockupGeneratorTask[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function getGeneratorTasksByIds(array $ids): array
    {
        $response = $this->client->get(
            '/v2/mockup-tasks',
            ['id' => implode(',', $ids)]
        );

        return array_map(
            static fn (array $data): ApiMockupGeneratorTask => ApiMockupGeneratorTask::fromArray($data),
            $response['data']
        );
    }
}

