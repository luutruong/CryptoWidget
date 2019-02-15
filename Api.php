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
    protected $apiKey;

    public function __construct()
    {
        $apiKey = \XF::app()->options()->tl_CryptoWidget_apiKey;
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Must be set apiKey option!');
        }

        $this->apiKey = $apiKey;
        $this->client = \XF::app()->http()->client();
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
        $uri = $this->getApiBaseUrl() . '/' . $this->getApiVersion() . '/' . $endPoint;
        $options = array_merge_recursive([
            'headers' => [
                'X-CMC_PRO_API_KEY' => $this->apiKey
            ]
        ], $options);

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (\Exception $e) {
            $this->logError($e);

            return null;
        }

        $body = $response->getBody()->getContents();
        $results = json_decode($body, true);

        if ($response->getStatusCode() === 200 && isset($results['data'])) {
            return $results['data'];
        }

        $this->logError(sprintf(
            'API request info $endPoint=%s, $error=%s, $body=%s',
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

    protected function logError($error)
    {
        \XF::logException(
            ($error instanceof \Exception) ? $error : new \Exception($error),
            false,
            '[tl] Crypto Widget: '
        );
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
