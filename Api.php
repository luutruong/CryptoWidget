<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\CryptoWidget;

use XF\Util\File;

class Api
{
    const API_BASE_URL = 'https://min-api.cryptocompare.com';
    const INTERNAL_CRYPTO_LIST_FILENAME = 'tcw_crypto_list.json';

    const CURRENCY_DEFAULT = 'USD';

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

    public function getAllCrypto($force = false)
    {
        $filePath = 'internal-data://' . self::INTERNAL_CRYPTO_LIST_FILENAME;
        if (File::abstractedPathExists($filePath) && !$force) {
            $json = \XF::app()->fs()->read($filePath);
            if ($json) {
                $data = json_decode($json, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        $cryptoList = $this->request('GET', 'data/all/coinlist');
        if (!$cryptoList || empty($cryptoList['Data'])) {
            return [];
        }

        $results = [];
        foreach ($cryptoList['Data'] as $item) {
            $results[$item['Id']] = [
                'id' => $item['Id'],
                'name' => $item['Name'],
                'symbol' => $item['Symbol'],
                'iconUrl' => isset($item['ImageUrl']) ? ($cryptoList['BaseImageUrl'] . $item['ImageUrl']) : null
            ];
        }

        File::writeToAbstractedPath($filePath, json_encode($results));
        return $results;
    }

    public function getItem($id)
    {
        $data = $this->request('GET', 'data/price', [
            'query' => [
                'fsym' => $id,
                'tsyms' => self::CURRENCY_DEFAULT
            ]
        ]);

        if (!$data || !isset($data[self::CURRENCY_DEFAULT])) {
            return false;
        }

        return [
            'symbol' => $id,
            'price' => $data[self::CURRENCY_DEFAULT]
        ];
    }

    public function getApiBaseUrl()
    {
        return self::API_BASE_URL;
    }

    protected function request($method, $endPoint, array $options = [])
    {
        $uri = $this->getApiBaseUrl() . '/' . $endPoint;
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
            if (isset($results['Response']) && $results['Response'] === 'Error') {
                $this->logError(sprintf(
                    'API request info $endPoint=%s, $error=%s, $body=%s',
                    $endPoint,
                    $response->getReasonPhrase(),
                    $body
                ));

                return null;
            }

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
