<?php

namespace Truonglv\CryptoWidget\XF\Admin\Controller;

use Truonglv\CryptoWidget\Api;

class Tools extends XFCP_Tools
{
    public function actionTCWCryptoList()
    {
        $query = $this->filter('q', 'str');
        $cryptoList = Api::getInstance()->getAllCrypto();

        $results = [];
        $html = '<img src="%s" width="32" height="32" />';

        foreach ($cryptoList as $value) {
            if (stripos($value['name'], $query) !== false) {
                $results[] = [
                    'id' => $value['id'],
                    'text' => sprintf('%s (%d)', $value['name'], $value['id']),
                    'iconHtml' => sprintf($html, $value['iconUrl'])
                ];
            }
        }

        usort($results, function ($a, $b) {
            return strlen($a['text']) - strlen($b['text']);
        });

        if (count($results) > 10) {
            $results = array_slice($results, 0, 10);
        }

        $replier = $this->view('', '');
        $replier->setJsonParam('results', $results);

        return $replier;
    }
}
