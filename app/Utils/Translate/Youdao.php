<?php

namespace App\Utils\Translate;

use App\Contracts\Translate;

/**
 * 有道翻译
 */
class Youdao extends Base implements Translate
{
    public function get($content='', $to='en', $from='cn')
    {
        $url = 'http://fanyi.youdao.com/translate?&doctype=json&type=AUTO&i='.urlencode($content);

        $curlData = $this->curlSearch($url);

        $curlResult = $curlData['data'] ?? '';

        if (empty($curlResult)) {
            return '';
        }

        $tempVal = trim($curlResult);

        $tempArr = json_decode($tempVal, true);

        $contentResult = $tempArr['translateResult'] ?? [];

        $result = '';
        foreach ($contentResult as $contentArr) {
            foreach ($contentArr as $content) {
                $result .= $content['tgt'] ?? '';
            }
        }

        return $result;
    }
}
