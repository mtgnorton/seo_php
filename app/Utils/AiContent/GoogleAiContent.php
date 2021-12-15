<?php

namespace App\Utils\AiContent;

use App\Contracts\AiContent;
use Exception;

class GoogleAiContent extends BaseAiContent implements AiContent
{
    public function get($keyword='', $page=1)
    {
        try {
            $keyword = urlencode($keyword);
    
            $start = 10 * ($page - 1);
            $url = "https://www.google.com/search?q=".$keyword.'&start='.$start;
    
            $header = [
                'User-Agent: Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36'
            ];
            
            $ch = curl_init ();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            
            // 执行
            $content = curl_exec ($ch);
            
            if ($content == FALSE) {
                common_log("获取谷歌搜索内容失败:" . curl_error($ch), null, [], 'ai-content');

                return [];
            }
            
            // 关闭
            curl_close($ch);
    
            $pattern = "#<div class=\"BNeawe s3v9rd AP7Wnd\"><div><div><div class=\"BNeawe s3v9rd AP7Wnd\">([\s\S]*?)</div>#";
            preg_match_all($pattern, $content, $match);
    
            $result = $match[1] ?? [];

            return [
                'content' => $result,
                'img' => [],
            ];
        } catch (Exception $e) {
            common_log('获取谷歌搜索内容失败', $e, [], 'ai-content');

            return [
                'content' => [],
                'img' => [],
            ];
        }
    }
}
