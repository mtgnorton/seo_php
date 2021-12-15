<?php

namespace App\Utils\AiContent;

use App\Contracts\AiContent;
use Exception;

class QihooAiContent extends BaseAiContent implements AiContent
{
    public function get($keyword='', $page=1)
    {
        try {
            $data = [
                'q' => $keyword,
                'pn' => $page,
                'psid' => '661328757263bf61525e9c01bf20e1ea',
                'src' => 'srp_paging',
                'fr' => 'none',
            ];
            $jsonData = http_build_query($data);
            
            $url = "https://www.so.com/s?".$jsonData;
    
            $ua = $this->getRandPCUA();
            $header = [
                'User-Agent: '.$ua,
                'cookie: __guid=137774715.3044225624886573600.1626944355153.735; Qs_lvt_100433=1626944355%2C1626944452%2C1626944470; Qs_pv_100433=3560517971794002000%2C2216603047436097300%2C591763102028219600%2C865970512910018800%2C2380363639185899500; QiHooGUID=74B72D634D09838AA69EDB4626751949.1626945328556; __huid=11cJd612ofH7sXaV495vNUqHsAJGVmc4hGw2%2FOvh8qiKM%3D; __gid=9114931.8328512.1627347304287.1630478793773.89; _S=8a9d67lbqvno6ch2ktagqsjls3; so-like-red=2; webp=1; so_huid=11cJd612ofH7sXaV495vNUqHsAJGVmc4hGw2%2FOvh8qiKM%3D; gtHuid=1; homeopenad=1; _uc_silent=1; dpr=1; erules=p4-6%7Cp1-5%7Cp3-15%7Cecl-14%7Cp2-5%7Ckd-8; count=3'
            ];
            
            $contentData = $this->curlSearch($url, $header);
            $content = $contentData['data'] ?? '';
            $name = $contentData['name'] ?? '未知';
            
            $pattern1 = '#<li class="res-list"([^>]*?)>([\s\S]*?)</li>#';

            $result = [];
            preg_replace_callback($pattern1, function ($match) use (&$result) {
                $tempVal = $match[2] ?? '';
                preg_replace_callback_array([
                    '#<p class="res-desc">(.*?)</p>#' => function ($match) use (&$result) {
                        $wholeContent = $match[1] ?? '';

                        $result[] = $wholeContent;
                    },
                    '#<span class="mh-content-desc-info">(.*?)</span>#' => function ($match) use (&$result) {
                        $wholeContent = $match[1] ?? '';

                        $result[] = $wholeContent;
                    },
                    '#<div class="res-comm-con">([\s\S]*?)</div>#' => function ($match) use (&$result) {
                        $wholeContent = $match[1] ?? '';
                        $wholeContent = preg_replace('#<p class="g-linkinfo">(.*?)</p>#', '', $wholeContent);

                        $result[] = $wholeContent;
                    },
                ], $tempVal);
            }, $content);

            foreach ($result as $key => &$val) {
                $val = preg_replace([
                    '#<span(.*?)</span>#',
                    '#<a(.*?)</a>#',
                    '#<p class="g-linkinfo">(.*?)</p>#'
                ], '', $val);

                $val = str_replace(['...', '<em>', '</em>'], '', $val);
                $val = $this->detachLastMarkWords($val);
                if (empty($val)) {
                    unset($result[$key]);
                }
            }

            // $patternImg = '#<img([^\>]*?)src="http([^\>]*?)>#';
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
                    common_log('厂家: '.$name.', 360搜索获取内容为空, 关键字为: '.$keyword.', 返回内容为: '.(string)$content, null, [], 'ai-content');
                }
            }
    
            return [
                'content' => $result,
                'img' => []
            ];
        } catch (Exception $e) {
            common_log('厂家: '.($name ?? '未知').', 获取360搜索内容失败', $e, [], 'ai-content');

            return [
                'content' => [],
                'img' => []
            ];
        }
    }
}
