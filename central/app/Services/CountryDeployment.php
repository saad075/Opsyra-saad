<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to one country deployment's internal API, signing every request
 * the way that deployment's VerifyCentralSignature expects:
 *
 *   X-Opsyra-Timestamp: <unix seconds>
 *   X-Opsyra-Signature: hex(hmac_sha256("<timestamp>.<raw body>", secret))
 */
class CountryDeployment
{
    private function __construct(
        private readonly string $country,
        private readonly string $apiUrl,
        private readonly string $secret,
    ) {}

    public static function for(string $country): self
    {
        $config = config("opsyra.countries.{$country}");

        if (! $config || empty($config['api_url']) || empty($config['secret'])) {
            throw new RuntimeException("The {$country} deployment isn't connected to Central yet.");
        }

        return new self($country, rtrim($config['api_url'], '/'), $config['secret']);
    }

    public static function isConnected(string $country): bool
    {
        $config = config("opsyra.countries.{$country}");

        return ! empty($config['api_url']) && ! empty($config['secret']);
    }

    public function post(string $path, array $payload): Response
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();

        try {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'X-Opsyra-Timestamp' => $timestamp,
                'X-Opsyra-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, $this->secret),
            ])
                ->withBody($body, 'application/json')
                ->timeout(20)
                ->post($this->apiUrl.'/api/internal/'.ltrim($path, '/'));
        } catch (ConnectionException $e) {
            throw new RuntimeException("Couldn't reach the {$this->country} deployment: {$e->getMessage()}", previous: $e);
        }
    }
}
