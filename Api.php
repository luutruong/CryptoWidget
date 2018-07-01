<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */
namespace Truonglv\CryptoWidget;

class Api
{
    const API_BASE_URL = 'https://api.coinmarketcap.com';

    protected $client;

    public function __construct()
    {
        $this->client = \XF::app()->http()->createClient([
            'base_url' => $this->getApiBaseUrl(),
            'exceptions' => \XF::$debugMode
        ]);
    }

    public function getAllCrypto()
    {
        return $this->request('GET', 'listing');
    }

    public function getItem($id)
    {
        return $this->request('GET', 'ticker/' . urlencode($id));
    }

    public function getApiBaseUrl()
    {
        return self::API_BASE_URL;
    }

    protected function request($method, $endPoint)
    {
        $request = $this->client->createRequest($method, $this->getApiVersion() . '/' . $endPoint);
        $response = $this->client->send($request);
        $body = $response->getBody()->getContents();

        if ($response->getStatusCode() !== 200) {
            \XF::logError(sprintf(
                'API request info $endPoint=%s, $error=%s, $body=%s',
                $endPoint,
                $response->getReasonPhrase(),
                $body
            ));

            return [];
        }

        $results = json_decode($body, true);
        if (isset($results['data'])) {
            return $results['data'];
        }

        return [];
    }

    protected function getApiVersion()
    {
        return 'v2';
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