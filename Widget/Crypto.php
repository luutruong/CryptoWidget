<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\CryptoWidget\Widget;

use XF\Widget\AbstractWidget;
use Truonglv\CryptoWidget\Api;

class Crypto extends AbstractWidget
{
    protected $defaultOptions = [
        'crypto_ids' => []
    ];

    public function render()
    {
        $options = $this->options;

        if (empty($options['crypto_ids'])) {
            return '';
        }

        $simpleCache = $this->app->simpleCache();
        $cacheData = $simpleCache->getValue('Truonglv/CryptoWidget', 'cryptoData');

        $data = [];
        foreach ($options['crypto_ids'] as $cryptoId) {
            if (isset($cacheData[$cryptoId])) {
                $data[$cryptoId] = $cacheData[$cryptoId];
                if (!empty($options['icons'])
                    && isset($options['icons'][$cryptoId])
                ) {
                    $data[$cryptoId]['iconUrl'] = $options['icons'][$cryptoId];
                }
            }
        }

        $viewParams = [
            'data' => $data,
            'title' => $this->getTitle()
        ];

        return $this->renderer('tl_crypto_widget_crypto', $viewParams);
    }

    protected function getDefaultTemplateParams($context)
    {
        $params = parent::getDefaultTemplateParams($context);

        if ($context === 'options') {
            $params['cryptoList'] = Api::getInstance()->getAllCrypto() ?: [];
            if (!empty($params['options']['crypto_ids'])) {
                $params['activeCryptos'] = array_filter($params['cryptoList'], function ($item) use ($params) {
                    return in_array($item['id'], $params['options']['crypto_ids']);
                });
            }
        }

        return $params;
    }

    public function getOptionsTemplate()
    {
        return 'admin:tl_crypto_widget_options_crypto';
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'crypto_ids' => 'str',
            'icons' => 'array'
        ]);

        $cryptoIds = explode(',', $options['crypto_ids']);
        $cryptoIds = array_map('intval', $cryptoIds);
        $cryptoIds = array_unique($cryptoIds);

        $options['crypto_ids'] = $cryptoIds;

        $icons = [];
        foreach ($cryptoIds as $cryptoId) {
            if (!empty($options['icons'][$cryptoId])) {
                $icons[$cryptoId] = $options['icons'][$cryptoId];
            }
        }
        $options['icons'] = $icons;

        return true;
    }
}
