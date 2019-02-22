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
        foreach ($options['crypto_ids'] as $cryptoName) {
            if (isset($cacheData[$cryptoName])) {
                $data[$cryptoName] = $cacheData[$cryptoName];
                if (!empty($options['icons'])
                    && isset($options['icons'][$cryptoName])
                ) {
                    $data[$cryptoName]['iconUrl'] = $options['icons'][$cryptoName];
                }
            }
        }

        $viewParams = [
            'data' => $data,
            'title' => $this->getTitle()
        ];

        return $this->renderer('tl_crypto_widget_crypto', $viewParams);
    }

    public function getOptionsTemplate()
    {
        return 'admin:tl_crypto_widget_options_crypto';
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options += $request->filter([
            'crypto_ids' => 'str'
        ]);

        $cryptoIds = explode(',', $options['crypto_ids']);
        $cryptoIds = array_map('trim', $cryptoIds);
        $cryptoIds = array_unique($cryptoIds);

        $allCrypto = Api::getInstance()->getAllCrypto();
        $validCrypto = [];
        foreach ($cryptoIds as $cryptoId) {
            preg_match('#(\d+)#', $cryptoId, $matches);
            if (!$matches) {
                continue;
            }

            $id = $matches[1];
            if (!isset($allCrypto[$id])) {
                continue;
            }

            $validCrypto[] = $cryptoId;
        }

        $options['crypto_ids'] = $validCrypto;

        return true;
    }
}
