<?php

namespace App\Utils\AiContent;

use App\Contracts\AiContent;
use Exception;

class BaiduAiContent extends BaseAiContent implements AiContent
{
    public function get($keyword='', $page=1)
    {
        try {
            $beforeKeyword = $keyword;
            $keyword = urlencode($keyword);

            $start = 10 * ($page - 1);
            $url = "https://www.baidu.com/s?wd=".$keyword."&pn=".$start;
            
            $contentData = $this->curlSearch($url);
            $content = $contentData['data'] ?? '';
            $name = $contentData['name'] ?? '未知';
    
            $pattern = "#<div class=\"c-abstract\">([\s\S]*?)</div>#";
            preg_match_all($pattern, $content, $match);

            $result = $match[1] ?? [];

            if (!empty($result)) {
                foreach ($result as $key => &$val) {
                    // 去掉多余部分
                    $val = str_replace(['...', '<em>', '</em>'], ['','',''], $val);
                    $pattern = "#<span class=\" newTimeFactor_before_abs c-color-gray2 m\">(.*?)</span>#";
                    $val = preg_replace($pattern, '', $val);

                    $val = $this->detachLastMarkWords($val);
                    if (empty($val)) {
                        unset($result[$key]);
                    }
                }
            }

            // $patternImg = '#<img(.*?)class="c-img c-img3 c-img-radius-large"(.*?)>#';
            // preg_match_all($patternImg, $content, $matchImg);

            // $resultImg = $matchImg[0] ?? [];
            // shuffle($resultImg);

            // $num = 0;
            // $limit = count($result);
            // $imgData = [];
            // foreach ($resultImg as &$val) {
            //     if ($num >= $limit) {
            //         break;
            //     }

            //     if (!empty($val)) {
            //         $num++;
            //         $imgData[] = $val;
            //     }
            // }
            // 如果$result为空, 则记录一下返回内容, 分析问题所在
            if (empty($result)) {
                if (mb_strlen($content) <= 5000) {
                    common_log('厂家: '.$name.', 百度搜索获取内容为空, 关键字为: '.$beforeKeyword.', 返回内容为: '.(string)$content, null, [], 'ai-content');
                }
            }
    
            return [
                'content' => $result,
                'img' => []
            ];
        } catch (Exception $e) {
            common_log('厂家: '.($name ?? '未知').', 获取百度搜索内容失败', $e, [], 'ai-content');

            return [];
        }
    }
}
