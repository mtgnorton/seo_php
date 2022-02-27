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

    public function twice($content='')
    {
        $onceTime = time();
        $onceData = '';
        $result = '';
        for ($i=0; $i<10; $i++) {
            $onceData = self::get($content, 'en', 'cn');
            if (!empty($onceData)) {
                break;
            }

            $onceNowTime = time();
            if ($onceNowTime - $onceTime > 15) {
                common_log('第一次翻译超过15秒结果为空', null, [], 'ai-content');

                return '';
            }
        }
        // 去除两边的双引号
        $onceData = str_replace_once('"', '', $onceData);
        $onceData = trim($onceData, '"\'');

        $twiceTime = time();
        for ($j=0; $j<15; $j++) {
            $result = self::get($onceData, 'cn', 'en');
            if (!empty($result)) {
                break;
            }
            $twiceNowTime = time();
            if ($twiceNowTime - $twiceTime > 15) {
                common_log('第二次翻译超过15秒结果为空', null, [], 'ai-content');

                return '';
            }
        }
        // 去除两边的双引号
        $result = str_replace_once('"', '', $result);
        $result = trim($result, '"');

        return $result;
    }
}
