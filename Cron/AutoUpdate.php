<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\CryptoWidget\Cron;

use XF\Entity\Widget;
use Truonglv\CryptoWidget\Api;

class AutoUpdate
{
    const MAX_REQUESTS_PER_MINUTE = 30;

    protected static $requestCount = 0;

    public static function updateCryptoData()
    {
        /** @noinspection SpellCheckingInspection */
        $widgets = \XF::finder('XF:Widget')
            ->where('definition_id', 'tl_cryptowidget')
            ->fetch();

        if ($widgets->count() < 1) {
            return;
        }

        $cryptoIds = [];
        foreach ($widgets as $widget) {
            if (empty($widget->options['crypto_ids'])) {
                continue;
            }

            $cryptoIds = array_merge($cryptoIds, $widget->options['crypto_ids']);
        }

        $cryptoIds = array_unique($cryptoIds);
        if (!$cryptoIds) {
            return;
        }

        $simpleCache = \XF::app()->simpleCache();
        /** @noinspection SpellCheckingInspection */
        $cacheData = $simpleCache->getValue('Truonglv/CryptoWidget', 'cryptoData');
        if (!is_array($cacheData)) {
            $cacheData = [];
        }

        // 10 minutes
        $ttl = 10 * 60;

        foreach ($cryptoIds as $cryptoId) {
            if (self::$requestCount >= self::MAX_REQUESTS_PER_MINUTE) {
                break;
            }

            $fetchData = true;
            if (!empty($cacheData[$cryptoId])) {
                $fetchData = (($cacheData[$cryptoId]['xf_last_updated'] + $ttl) < \XF::$time);
            }

            if (!$fetchData) {
                continue;
            }

            self::$requestCount++;

            $data = Api::getInstance()->getItem($cryptoId);
            $data['xf_last_updated'] = \XF::$time;

            $cacheData[$cryptoId] = $data;
        }

        $simpleCache->setValue('Truonglv/CryptoWidget', 'cryptoData', $cacheData);
    }
}
