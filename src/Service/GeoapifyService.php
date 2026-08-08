<?php

declare(strict_types=1);

namespace Survos\GeoapifyBundle\Service;

use Survos\FetchBundle\Contract\PersistentFetcherInterface;

class GeoapifyService
{
    public const BASE_URL = 'https://api.geoapify.com/v1/geocode/';

    public function __construct(
        private readonly PersistentFetcherInterface $persistentFetcher,
        private ?string $apiKey = null,
    ) {
    }

//https://api.geoapify.com/v1/geocode/search?text=11%20Rue%20Grenette%2C%2069002%20Lyon%2C%20France&apiKey=YOUR_API_KEY

//https://api.geoapify.com/v1/geocode/search?housenumber=11&street=Rue%20Grenette&postcode=69002&city=Lyon&country=France&apiKey=YOUR_API_KEY

    public function lookup(string $text): ?array
    {
        return $this->makeCall('search', [
            'text' => $text,
        ]);
    }

    public function reverseGeocode(float|string $lat, float|string $lng): ?array
    {
        return $this->makeCall('reverse', [
            'lat' => $lat,
            'lon' => $lng,
        ]);
    }

    private function makeCall(string $action, array $params = []): ?array
    {
        $params = array_merge($params, ['apiKey' => $this->apiKey]);
        $url = self::BASE_URL . $action . '?' . http_build_query($params);

        // PersistentFetcher caches by URL (forever, until forget()/force_fetch), so repeat
        // lookups of the same coordinates/text never hit Geoapify twice.
        $result = $this->persistentFetcher->fetch($url);

        if (!$result->isOkay()) {
            $paramsWithoutApiKey = $params;
            unset($paramsWithoutApiKey['apiKey']);
            throw new \RuntimeException(sprintf(
                'Geoapify "%s" request failed with status %d (params: %s)',
                $action,
                $result->statusCode,
                json_encode($paramsWithoutApiKey, JSON_THROW_ON_ERROR),
            ));
        }

        return json_decode($result->contents ?? '', true);
    }
}
