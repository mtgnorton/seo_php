<?php

namespace App\Services;

use App\Services\Gather\CrawlService;
use Illuminate\Support\Facades\Storage;

class FakerOriginService
{


    public static function toSynonym($text)
    {

        $synonymArr = CommonService::getSynonyms();

        $content = strtr($text, $synonymArr);
        return [
            'state'   => 1,
            'content' => $content,
            'msg'     => ''
        ];
    }


    public static function to5118($text)
    {

        $errMsg  = '';
        $content = preg_replace_callback('/[\x{4e00}-\x{9fff} ,.:!?\'"; ，。：？’“；、]{8,}/u', function ($matches) use (&$errMsg) {

            $rs = FakerOriginService::toLength5118($matches[0]);
            if ($rs['state'] == 1) {
                return data_get($rs, 'content');
            }

            $errMsg = data_get($rs, 'msg');
            return $matches[0];
        }, $text);

        if ($errMsg != "") {
            return [
                'state'   => 0,
                'content' => $text,
                'msg'     => $errMsg
            ];
        }
        return [
            'state'   => 1,
            'content' => $content,
            'msg'     => ''
        ];

    }

    /**
     * author: mtg
     * time: 2021/7/28   14:33
     * function description: 伪原创5118接口
     * @param $text
     * @return array
     */
    public static function toLength5118($text): array
    {

        $apiKey = conf('fakeorigin.5118_key');

        $crawClient = CrawlService::clearOptions()->setOptions('', [
            'CURLOPT_HTTPHEADER' => [
                'Authorization: ' . $apiKey,
                "Content-Type" . ":" . "application/x-www-form-urlencoded; charset=UTF-8"
            ]
        ]);

        if (!$apiKey) {
            return [
                'state'   => 0,
                'msg'     => '没有填写伪原创key,无法进行伪原创',
                'content' => $text,
            ];
        }
        $waitTranslateText = $text;
        $translateRs       = '';

        while (mb_strlen($waitTranslateText) > 0) {

            $thisTimeText = mb_substr($waitTranslateText, 0, 4000);

            $waitTranslateText = mb_substr($waitTranslateText, 4000);

            $rs = self::request5118($thisTimeText, $apiKey, $crawClient);

            if (!$rs['state']) {
                return $rs;
            } else {
                $translateRs .= data_get($rs, 'content');
            }
        }

        return [
            'state'   => 1,
            'content' => $translateRs,
            'msg'     => ''
        ];
    }

    /**
     * author: mtg
     * time: 2021/7/28   14:40
     * function description:通过谷歌翻译实现伪原创
     * @param $text
     * @return string
     */
    public static function toGoogle($text): array
    {
        $text = self::requestGoogle($text, 'en');
        if ($text === false) {
            return [
                'state'   => 0,
                'content' => '',
                'msg'     => '谷歌请求频率限制'
            ];
        }
        $text = self::requestGoogle($text);
        if ($text === false) {
            return [
                'state'   => 0,
                'content' => '',
                'msg'     => '谷歌请求频率限制'
            ];
        }
        return [
            'state'   => 1,
            'content' => $text,
            'msg'     => ''
        ];
    }


    private static function requestGoogle($text, $to = 'zh-CN')
    {

        $waitTranslateText = $text;
        $translateRs       = '';

        while (mb_strlen($waitTranslateText) > 0) {

            $thisTimeText = mb_substr($waitTranslateText, 0, 1500);

            $waitTranslateText = mb_substr($waitTranslateText, 1500);

            $thisTimeText = urlencode($thisTimeText);

            $url = 'https://translate.google.cn/translate_a/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=auto&tl=' . $to . '&q=' . $thisTimeText;

            $res        = CrawlService::get($url);
            $thisTimeRs = json_decode($res);
            if (!$thisTimeRs) {
                return false;
            }
            if ($rs = data_get($thisTimeRs, 0)) {
                if (is_array($rs)) {
                    foreach ($rs as $item) {
                        $translateRs .= data_get($item, '0');
                    }
                }
            }
            sleep(1);
        }

        return $translateRs;


    }

    /**
     * author: mtg
     * time: 2021/7/28   14:33
     * function description:请求5118
     * @param $text
     * @param $apiKey
     * @param $crawClient
     * @return array
     */
    public static function request5118($text, $apiKey, $crawClient)
    {
        $host = "http://apis.5118.com";
        $path = "/wyc/akey";

        $content = 'txt=' . urlencode($text);
        $res     = $crawClient->post($host . $path, $content);


        $errCode = data_get($res, 'errcode', 220);

        if ($errCode != 0) {
            return [
                'state'   => 0,
                'msg'     => \App\Constants\FakeOriginConstants::errText($errCode),
                'content' => ''
            ];
        }


        return [
            'state'   => 1,
            'content' => data_get($res, 'data'),
            'msg'     => ''
        ];

    }
}
