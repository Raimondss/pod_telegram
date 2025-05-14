<?php

declare(strict_types=1);

namespace App\APIs;

use App\Clients\PrintfulApiClient;
use App\Params\ApiMockupGeneratorParams;

class PrintfulApi
{
    public function __construct(private PrintfulApiClient $client)
    {
    }

    public function generateMockups(ApiMockupGeneratorParams $params): array
    {
        var_dump(json_encode($params->toArray(), JSON_PRETTY_PRINT));

        $response = $this->client->post(
            '/v2/mockup-tasks',
            $params->toArray(),
        );

        return $response['data'] ?? [];
    }

    public function getGeneratorTaskById(int $id): array
    {
        $response = $this->client->get(
            '/v2/mockup-tasks',
            ['id' => $id]
        );

        return $response['data'] ?? [];
    }
}

