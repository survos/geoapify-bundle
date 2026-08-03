<?php

declare(strict_types=1);

namespace Survos\GeoapifyBundle\Service;

use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoapifyService
{
    public const BASE_URL = 'https://api.geoapify.com/v1/geocode/';
    public function __construct(
        private ?string $apiKey=null,
        private ?CacheInterface $cache=null,
        private ?HttpClientInterface $httpClient=null
    )
    {
    }

//https://api.geoapify.com/v1/geocode/search?text=11%20Rue%20Grenette%2C%2069002%20Lyon%2C%20France&apiKey=YOUR_API_KEY

//https://api.geoapify.com/v1/geocode/search?housenumber=11&street=Rue%20Grenette&postcode=69002&city=Lyon&country=France&apiKey=YOUR_API_KEY

    public function lookup(string $text): ?array
    {
        return $this->cache->get(md5($text), fn (CacheItem $item) => $this->makeCall('search', [
            'text' => $text,
        ]));
    }

    public function reverseGeocode(float|string $lat, float|string $lng): ?array
    {
        $key = sprintf('%s-%sx', $lat, $lng);
        return $this->cache
            ? $this->cache->get($key, fn(CacheItem $item) => $this->reverseApiCall($lat, $lng))
            : $this->reverseApiCall($lat, $lng);

    }

    private function makeCall(string $action, array $params = []): ?array
    {
        if ($this->httpClient) {
            $params = array_merge($params, ['apiKey' => $this->apiKey]);
            $request = $this->httpClient->request('GET', self::BASE_URL . $action, [
                'query' => $params,
            ]);
            $statusCode = $request->getStatusCode();
            if ($statusCode === 200) {
                $response = $request->toArray();
            } else {
                throw new \RuntimeException(sprintf(
                    'Geoapify "%s" request failed with status %d (params: %s)',
                    $action,
                    $statusCode,
                    json_encode($params, JSON_THROW_ON_ERROR),
                ));
            }
        } else {
            assert(false,"inject http client");
            // or use curl?
        }
        return $response;

    }

    public function reverseApiCall(float|string $lat, float|string $lng): ?array
    {
        $response = null;
        // https://myprojects.geoapify.com/api/SaXR1ujolOktGdYjGwMW/keys

//        $url = sprintf('https://api.geoapify.com/v1/geocode/reverse?lat=%s&lon=%s&apiKey=%s', $lat, $lng, $this->apiKey);
        return $this->makeCall('reverse', [
            'lat' => $lat,
            'lon' => $lng,
        ]);
//        $data = $json['data']; // json_decode($json, true);
//        return $data['features'][0]['properties'];
    }

}
