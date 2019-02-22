<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\CryptoWidget\Cron;

use XF\Timer;
use Truonglv\CryptoWidget\Api;

class AutoUpdate
{
    const CACHE_TTL = -600;

    public static function updateCryptoData()
    {
        /** @noinspection SpellCheckingInspection */
        $widgets = \XF::finder('XF:Widget')
            ->where('definition_id', 'tl_cryptowidget')
            ->fetch();

        if ($widgets->count() < 1) {
            return;
        }

        $cryptoNames = [];
        foreach ($widgets as $widget) {
            if (empty($widget->options['crypto_ids'])) {
                continue;
            }

            $cryptoNames = array_merge($cryptoNames, $widget->options['crypto_ids']);
        }

        $cryptoNames = array_unique($cryptoNames);
        if (!$cryptoNames) {
            return;
        }

        $simpleCache = \XF::app()->simpleCache();
        /** @noinspection SpellCheckingInspection */
        $cacheData = $simpleCache->getValue('Truonglv/CryptoWidget', 'cryptoData');
        if (!is_array($cacheData)) {
            $cacheData = [];
        }

        $ttl = self::CACHE_TTL;
        $timer = new Timer(3);
        $api = Api::getInstance();
        $allCrypto = $api->getAllCrypto(true);

        foreach ($cryptoNames as $cryptoName) {
            preg_match('#(\d+)#', $cryptoName, $matches);
            if (!$matches) {
                continue;
            }

            $id = $matches[1];
            $fetchData = true;
            if (!empty($cacheData[$id])) {
                $fetchData = (($cacheData[$id]['xf_last_updated'] + $ttl) < \XF::$time);
            }

            if (!$fetchData) {
                continue;
            }

            if (!isset($allCrypto[$id])) {
                continue;
            }

            $data = $api->getItem($allCrypto[$id]['symbol']);
            if (!$data) {
                continue;
            }
            $data['xf_last_updated'] = \XF::$time;

            $cacheData[$cryptoName] = array_replace($allCrypto[$id], $data);
            if ($timer->limitExceeded()) {
                break;
            }
        }

        $simpleCache->setValue('Truonglv/CryptoWidget', 'cryptoData', $cacheData);
    }
}
