<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getCurrentWeather(string $city, string $countryCode = 'TN'): ?array
    {
        if (trim($city) === '') {
            return null;
        }

        $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
            'query' => [
                'q' => sprintf('%s,%s', $city, $countryCode),
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr',
            ],
        ]);

        try {
            return $response->toArray(false);
        } catch (\Throwable) {
            return null;
        }
    }
}

