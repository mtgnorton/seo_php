<?php

namespace App\Utils\AiContent;

use App\Contracts\AiContent;
use App\Services\CommonService;
use Exception;

class SougouAiContent extends BaseAiContent implements AiContent
{
    public function get($keyword='', $page=1)
    {
        try {
            $beforeKeyword = $keyword;
            $keyword = urlencode($keyword);
            $url = "http://www.sogou.com/web?query=".$keyword."&page=".$page;
            
            $contentData = $this->curlSearch($url);
            $content = $contentData['data'] ?? '';
            $name = $contentData['name'] ?? '';

            $pattern = "#<div class=\"fz-mid space-txt\"(.*?)>([\s\S]*?)</div>#";
            preg_match_all($pattern, $content, $match);
            
            $result = $match[2] ?? [];

            if (!empty($result)) {
                foreach ($result as $key => &$val) {
                    $pattern1 = "#<span ([\s\S]*?)>([\s\S]*?)</span>#";
                    $pattern2 = "#<a ([\s\S]*?)>([\s\S]*?)</a>#";
                    $pattern3 = "#<img([\s\S]*?)/>#";
                    $val = preg_replace([
                        $pattern1,
                        $pattern2,
                        $pattern3,
                    ], '', $val);

                    $val = str_replace([
                        '<em>', '</em>', ' ', '...',
                        '<!--red_beg-->', '<!--red_end-->'
                    ], '', $val);

                    $val = $this->detachLastMarkWords($val);
                    if (empty($val)) {
                        unset($result[$key]);
                    }
                }
            }

            // $patternImg = '#<img([^\>]*?)src="http([^\>]*?)>#';
            // preg_match_all($patternImg, $content, $matchImg);

            // $resultImg = $matchImg[0] ?? [];
            // shuffle($resultImg);

            // $num = 0;
            // $count = count($result);
            // $imgData = [];
            // foreach ($resultImg as &$val) {
            //     if ($num >= $count) {
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
                    common_log('厂家: '.$name.', 搜狗搜索获取内容为空, 关键字为: '.$beforeKeyword.', 返回内容为: '.(string)$content, null, [], 'ai-content');
                }
            }

            return [
                'content' => $result,
                'img' => [],
            ];
        } catch (Exception $e) {
            common_log('厂家: '.($name ?? '未知').', 获取搜狗搜索内容失败', $e, [], 'ai-content');

            return [
                'content' => [],
                'img' => []
            ];
        }
    }
}
