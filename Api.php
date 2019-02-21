<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\CryptoWidget;

class Api
{
    const API_BASE_URL = 'https://min-api.cryptocompare.com';

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
        $cryptoList = $this->request('GET', 'data/all/coinlist');
        if (!$cryptoList || empty($cryptoList['Data'])) {
            return [];
        }

        $results = [];
        foreach ($cryptoList as $item) {
            $results[$item['Id']] = [
                'id' => $item['Id'],
                'name' => $item['Name'],
                'symbol' => $item['Symbol'],
                'fullName' => $item['FullName'],
                'alterName' => $item['CoinName']
            ];
        }

        return $results;
    }

    public function getItem($id)
    {
        $data = $this->request('GET', 'data/coin/generalinfo', [
            'query' => [
                'fsyms' => $id,
                'tsym' => 'USD'
            ]
        ]);

        if (!$data || empty($data['Data'])) {
            return false;
        }

        foreach ($data['Data'] as $item) {
            if ($item['Internal'] === $id) {
                return [
                    'iconUrl' => 'https://www.cryptocompare.com' . $item['CoinInfo']['ImageUrl'],
                    'name' => $item['CoinInfo']['Name'],
                    'symbol' => $id,
                    'price' => $item['ConversionInfo']['TotalVolume24H'],
                    'RAW' => $item
                ];
            }
        }

        return false;
    }

    public function getApiBaseUrl()
    {
        return self::API_BASE_URL;
    }

    protected function request($method, $endPoint, array $options = [])
    {
        $uri = $this->getApiBaseUrl() . '/' . $this->getApiVersion() . '/' . $endPoint;
        $options = array_merge_recursive([
            'query' => [
                'api_key' => $this->apiKey
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

        if ($response->getStatusCode() === 200
            && is_array($results)
        ) {
            return $results;
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
