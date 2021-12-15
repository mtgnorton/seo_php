<?php

namespace App\Utils\Translate;

use App\Contracts\Translate;
use Exception;

/**
 * 有道翻译
 */
class Nice extends Base implements Translate
{
    public function get($content='', $to='en', $from='zh-Hans')
    {
        try {
            $url = "https://api.microsofttranslator.com/V2/Ajax.svc/Translate?appId=DB50E2E9FBE2E92B103E696DCF4E3E512A8826FB&oncomplete=?&from=$from&to=$to&text=".urlencode($content);
    
            $curlData = $this->curlSearch($url);

            $data = $curlData['data'] ?: '';
            $name = $curlData['name'] ?: '未知';

            if (empty($curlData)) {
                common_log('厂家: '.$name.',NICE翻译结果为空, 返回内容为: '.(string)$content, null, [], 'ai-content');
            }
    
            return $data;
        } catch (Exception $e) {
            common_log('厂家: '.($name ?? '未知').', NICE翻译内容失败', $e, [], 'ai-content');
        }
    }

    public function twice($content='')
    {
        $onceTime = time();
        $onceData = '';
        $result = '';
        for ($i=0; $i<10; $i++) {
            $onceData = self::get($content, 'en', 'zh-Hans');
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
            $result = self::get($onceData, 'zh-Hans', 'en');
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
