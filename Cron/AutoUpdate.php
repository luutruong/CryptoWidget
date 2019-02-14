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
        $timer = new Timer(3);

        foreach ($cryptoIds as $cryptoId) {
            $fetchData = true;
            if (!empty($cacheData[$cryptoId])) {
                $fetchData = (($cacheData[$cryptoId]['xf_last_updated'] + $ttl) < \XF::$time);
            }

            if (!$fetchData) {
                continue;
            }

            $data = Api::getInstance()->getItem($cryptoId);
            if ($data === null) {
                continue;
            }
            $data['xf_last_updated'] = \XF::$time;

            $cacheData[$cryptoId] = $data;
            if ($timer->limitExceeded()) {
                break;
            }
        }

        $simpleCache->setValue('Truonglv/CryptoWidget', 'cryptoData', $cacheData);
    }
}
