<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\CryptoWidget;

class Api
{
    const API_BASE_URL = 'https://pro-api.coinmarketcap.com';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    public function __construct()
    {
        $apiKey = \XF::app()->options()->tl_CryptoWidget_apiKey;
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Must be set apiKey option!');
        }

        $this->client = \XF::app()->http()->createClient([
            'base_url' => $this->getApiBaseUrl(),
            'timeout' => 3,
            'connect_timeout' => 3,
            'exceptions' => \XF::$debugMode,
            'headers' => [
                'X-CMC_PRO_API_KEY' => $apiKey
            ]
        ]);
    }

    public function getAllCrypto()
    {
        return $this->request('GET', 'cryptocurrency/listings/latest');
    }

    public function getItem($id)
    {
        return $this->request('GET', 'cryptocurrency/info', [
            'query' => [
                'id' => $id
            ]
        ]);
    }

    public function getApiBaseUrl()
    {
        return self::API_BASE_URL;
    }

    protected function request($method, $endPoint, array $options = [])
    {
        try {
            $response = $this->client->request($method, $this->getApiVersion() . '/' . $endPoint, $options);
        } catch (\Exception $e) {
            return null;
        }

        $body = $response->getBody()->getContents();
        $results = json_decode($body, true);

        if ($response->getStatusCode() === 200 && isset($results['data'])) {
            return $results['data'];
        }

        \XF::logError(sprintf(
            '[tl] Crypto Widget: API request info $endPoint=%s, $error=%s, $body=%s',
            $endPoint,
            $response->getReasonPhrase(),
            $body
        ));

        return null;
    }

    protected function getApiVersion()
    {
        return 'v1';
    }

    /**
     * @return static
     * @throws \Exception
     */
    public static function getInstance()
    {
        static $instanced;
        if (!$instanced) {
            $class = \XF::extendClass(__CLASS__);
            $instanced = new $class();
        }

        return $instanced;
    }
}
