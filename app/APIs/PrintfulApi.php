<?php

declare(strict_types=1);

namespace App\APIs;

use App\Clients\PrintfulApiClient;
use App\Models\TelegramUserOrder;
use App\Models\TelegramUserProduct;
use App\Models\TelegramUserVariant;
use App\Params\ApiMockupGeneratorParams;
use App\Structures\Api\ApiMockupGeneratorTask;
use Log;

class PrintfulApi
{
    public function __construct(private PrintfulApiClient $client)
    {
    }

    public function createOrder(TelegramUserOrder $order): array
    {
        $variant = TelegramUserVariant::find($order->telegram_user_variant_id);
        $product = TelegramUserProduct::find($variant->telegram_user_product_id);

        return $this->client->post(
            '/orders',
            [
                'recipient' => [
                    'name' => $order->name,
                    'country' => $order->country_code,
                    'state_code' => $order->state,
                    'city' => $order->city,
                    'zip' => $order->post_code,
                    'email' => $order->email,
                    'address1' => $order->street_line1,
                    'address2' => $order->street_line2,
                ],
                'items' => [
                    [
                        'variant_id' => $variant->variant_id,
                        'files' => [
                            [
                                'url' => $product->uploaded_file_url
                            ]
                        ]
                    ]
                ]
            ]
        );
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

        Log::info(print_r($response, true));

        return array_map(
            static fn (array $data): ApiMockupGeneratorTask => ApiMockupGeneratorTask::fromArray($data),
            $response['data']
        );
    }
}

