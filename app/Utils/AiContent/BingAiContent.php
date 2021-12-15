<?php

namespace App\Utils\AiContent;

use App\Contracts\AiContent;
use Exception;

class BingAiContent extends BaseAiContent implements AiContent
{
    public function get($keyword='', $page=1)
    {
        try {
            $beforeKeyword = $keyword;
            $keyword = urlencode($keyword);
    
            $start = 10 * ($page - 1) + 1;
            $url = "https://cn.bing.com/search?q=".$keyword."&sp=-1&pq=&sc=0-0&qs=n&sk=&cvid=5B1B1927E854408F9CC117277184CFC0&FORM=PERE&first=".$start;
    
            $contentData = $this->curlSearch($url);
            $content = $contentData['data'] ?? '';
            $name = $contentData['name'] ?? '';
    
            $pattern = "#<p>([\s\S]*?)</p>#";
            preg_match_all($pattern, $content, $match);
    
            $result = $match[1] ?? [
                'content' => [],
                'img' => []
            ];

            if (empty($result)) {
                return $result;
            }

            foreach ($result as $key => &$val) {
                $tempArr = explode('&ensp;&#0183;&ensp;', $val);

                $val = array_pop($tempArr);

                $val = preg_replace('#<a([.*?])</a>#', '', $val);
                $val = str_replace([
                    '<strong>', '</strong>', ' ', '...',
                    '<!--red_beg-->', '<!--red_end-->'
                ], '', $val);

                $val = $this->detachLastMarkWords($val);
            }
            // 如果$result为空, 则记录一下返回内容, 分析问题所在
            if (empty($result)) {
                if (mb_strlen($content) <= 5000) {
                    common_log('厂家: '.$name.', 必应搜索获取内容为空, 关键字为: '.$beforeKeyword.', 返回内容为: '.(string)$content, null, [], 'ai-content');
                }
            }

            return [
                'content' => $result,
                'img' => []
            ];
        } catch (Exception $e) {
            common_log('厂家: '.($name ?? '未知').', 获取必应搜索内容失败', $e, [], 'ai-content');

            return [
                'content' => [],
                'img' => []
            ];
        }
    }
}
