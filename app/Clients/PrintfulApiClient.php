<?php

declare(strict_types=1);

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

class PrintfulApiClient
{
    /** Request types  */
    public const REQUEST_GET = 'get';
    public const REQUEST_POST = 'post';
    public const REQUEST_PUT = 'put';
    public const REQUEST_MULTIPART = 'multipart';
    public const REQUEST_PATCH = 'patch';
    public const REQUEST_DELETE = 'delete';

    protected Client $client;

    public function __construct() {
        $this->client = new Client($this->getClientSettings());
    }

    /**
     * Default client settings. Override if you need other settings
     *
     * @return array<string, mixed>
     */
    protected function getClientSettings(): array
    {
        return [
            'base_uri' => 'https://api.printful.com',
            'timeout' => 90,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-agent' => 'Printful integration',
                'Authorization' => 'Bearer ' . env('PRINTFUL_API_TOKEN'),
            ],
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function makeRequest(
        string $method,
        string $url,
        array $settings,
    ): array{
        $response = match ($method) {
            self::REQUEST_GET => $this->client->get($url, $settings),
            self::REQUEST_POST => $this->client->post($url, $settings),
            self::REQUEST_PUT => $this->client->put($url, $settings),
            self::REQUEST_PATCH => $this->client->patch($url, $settings),
            self::REQUEST_MULTIPART => $this->client->request(self::REQUEST_POST, $url, $settings),
            self::REQUEST_DELETE => $this->client->delete($url, $settings),
            default => throw new InvalidArgumentException('Unsupported request method'),
        };

        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function post(string $url, array $data): array
    {
        $settings = $this->buildRequestSettings(self::REQUEST_POST, $data);

        return $this->makeRequest(self::REQUEST_POST, $url, $settings);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function get($url, $params = []): array
    {
        $settings = $this->buildRequestSettings(self::REQUEST_GET, $params);

        return $this->makeRequest(self::REQUEST_GET, $url, $settings);
    }

    /**
     * @param string $method
     * @param array|null $data
     * @return array
     */
    protected function buildRequestSettings($method, $data = [])
    {
        $settings = [];

        if ($method === self::REQUEST_POST && $data) {
            $settings[RequestOptions::JSON] = $data;
        } elseif ($method === self::REQUEST_MULTIPART && $data) {
            $settings[RequestOptions::MULTIPART] = $data;
        } elseif ($method === self::REQUEST_GET && $data) {
            $settings[RequestOptions::QUERY] = $data;
        }

        return $settings;
    }
}
