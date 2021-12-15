<?php

namespace App\Utils\AiContent;

use App\Contracts\AiContent;
use Exception;

class ToutiaoAiContent extends BaseAiContent implements AiContent
{
    public function get($keyword='', $page=1)
    {
        try {
            $data = [
                'offset' => 10*($page-1),
                'format' => 'json',
                'keyword' => $keyword,
                'autoload' => true,
                'count' => 20,
                'cur_tab' => 1
            ];
            $jsonData = http_build_query($data);
            
            $url = "https://www.toutiao.com/api/search/content?".$jsonData;
    
            $ua = $this->getRandPCUA();
            $header = [
                'User-Agent: '.$ua,
                'Referer: https://www.toutiao.com/',
                'accept: application/json, text/javascript',
                'accept-language: zh-CN,zh;q=0.9',
                'cookie: ttcid=1763af9c7be346e885474e39d544f79720; tt_webid=7030677535524488735; csrftoken=4f8e8169d08f435e72685fcb3c0a923b; _tea_utm_cache_2018=undefined; MONITOR_DEVICE_ID=1168ab13-26e4-4801-846a-27109f700d5e; _S_DPR=1; _S_IPAD=0; tt_webid=7030677535524488735; MONITOR_WEB_ID=7030677535524488735; _S_WIN_WH=2560_407; __ac_nonce=06195c97a006bfe1cab7d; __ac_signature=_02B4Z6wo00f01rksYQAAAIDD2iahaKM3mB65CGWAAM.rH0GniaG8CJRJiLc44XKpE5pTzLPpl7eQVgaJxzUIh8.CLDIkSgXza31t05zGdKTZhC16OnZg9AZV9RoHh4xIlxGd-y8XySrWHtoo23; ttwid=1%7C3-iAQ4wnwary5cNYzMc8fNLIepXUi0FOrrmF1U9JSiU%7C1637206396%7C0a540b1798cf1cbc6758273db7e04ab92c459b7b1f5bb6638a604e5c89b5dea3; s_v_web_id=verify_802bc95c6de5fe546194b26199dc89dc; tt_scid=RGcrf9pCJOQFxYX9oIDHmPabDLLezO3yvNR7xzoYYLzTHC7j1ikvgqcCK2iygnjd05f0'
            ];
            
            $contentData = $this->curlSearch($url, $header);
            $content = $contentData['data'] ?? '';
            $name = $contentData['name'] ?? '未知';

            $result = json_decode($content, true);

            $resData = $result['data'];

            $summaryData = [];
            if (!empty($resData)) {
                foreach ($resData as $val) {
                    if (isset($val['display']['summary']['text']) && !empty($val['display']['summary']['text'])) {
                        $tempSummary = $val['display']['summary']['text'];
                        $summaryData[] = str_replace('...', '', $tempSummary);
                    }
                }
            }
            
            // return $summaryData;
            return [
                'content' => $summaryData,
                'img' => [],
            ];
        } catch (Exception $e) {
            common_log('厂家: '.($name ?? '未知').', 获取今日头条搜索内容失败', $e, [], 'ai-content');

            return [
                'content' => [],
                'img' => []
            ];
        }
    }
}
