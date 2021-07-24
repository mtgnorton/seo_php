<?php

namespace App\Services;

use App\Constants\RedisCacheKeyConstant;
use App\Constants\SpiderConstant;
use App\Models\SpiderRecord;
use App\Models\Website;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 蜘蛛服务类
 */
class SpiderService extends BaseService
{
    /**
     * 记录蜘蛛
     *
     * @param string $urlType   链接类型
     * @return void
     */
    public static function spiderRecord($urlType = '')
    {
        if ($urlType === '') {
            $urlType = IndexService::getUriType();
        }
        $type = self::getSpider();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (empty($type)) {
            return [];
        }
        $ip = CommonService::getUserIpAddr();
        $host = request()->getHost();
        $url = request()->url();
        
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        $templateId = TemplateService::getWebsiteTemplateId();

        // 获取redis中的蜘蛛存量数, 如果数量已满20条, 则插入数据库
        $now = Carbon::now()->toDateTimeString();
        $insertData = [
            'type' => $type, 
            'user_agent' => $userAgent,
            'ip' => $ip, 
            'host' => $host, 
            'type' => $type, 
            'url' => $url, 
            'url_type' => $urlType, 
            'category_id' => $categoryId, 
            'group_id' => $groupId, 
            'template_id' => $templateId, 
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::beginTransaction();

        try {
            $spiderKey = RedisCacheKeyConstant::REDIS_SPIDER_RECORDS;
            $len = Redis::hlen($spiderKey);
            $maxNum = 20;
            if ($len >= $maxNum - 1) {
                $baseData = Redis::hgetall($spiderKey);
                $allData = [];
                foreach ($baseData as $base) {
                    $allData[] = json_decode($base, true);
                }
                $allData[] = $insertData;
    
                DB::table('spider_records')->insert($allData);
    
                Redis::del($spiderKey);
            } else {
                $spiderField = (int)$len + 1;
                Redis::hset($spiderKey, $spiderField, json_encode($insertData, JSON_UNESCAPED_UNICODE));
            }

            DB::commit();
        } catch (Exception $e) {
            $len = isset($len) ? $len : 0;
            common_log('插入蜘蛛记录失败, 失败条数为: '.$len, $e);
        }
    }

    /**
     * 获取当前页面蜘蛛(基于常用蜘蛛库)
     *
     * @return void
     */
    public static function getSpider()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $spiderTypes = SpiderConstant::ruleText();

        foreach ($spiderTypes as $key => $type) {
            foreach ($type as $spider) {
                if (stripos(strtolower($userAgent), strtolower($spider)) !== false) {
                    return $key;
                }
            }
        }

        return '';
    }

    /**
     * 获取当前页面蜘蛛(基于所有蜘蛛库)
     *
     * @return void
     */
    public static function getAllSpider()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $spiderTypes = SpiderConstant::allRuleText();

        foreach ($spiderTypes as $key => $type) {
            foreach ($type as $spider) {
                if (stripos(strtolower($userAgent), strtolower($spider)) !== false) {
                    return $key;
                }
            }
        }

        return '';
    }

    /**
     * 判断是否拦截或强引蜘蛛
     *
     * @return void
     */
    public static function spiderOption()
    {
        // 1. 判断是否开启屏蔽
        $spiderConfig = conf('spider');
        if ($spiderConfig['no_spider']['is_open'] == 'on') {
            $typeStr = $spiderConfig['no_spider']['type'];
            $noTypes = CommonService::linefeedStringToArray($typeStr);

            $type = self::getAllSpider();

            if (in_array($type, $noTypes)) {
                abort(404);
            }
        }

        // 2. 判断是否开启强引
        if ($spiderConfig['spider_strong_attraction']['is_open'] == 'on') {
            $typeStr = $spiderConfig['spider_strong_attraction']['type'];
            $types = CommonService::linefeedStringToArray($typeStr);

            $type = self::getAllSpider();

            if (in_array($type, $types)) {
                $urlStr = $spiderConfig['spider_strong_attraction']['urls'];
                $urls = CommonService::linefeedStringToArray($urlStr);

                if (!empty($urls)) {
                    $randKey = count($urls)-1 >=0 ? count($urls) -1 : 0;
                    $url = $urls[mt_rand(0, $randKey)] ?? '';

                    return redirect($url);
                }
            }
        }

        return false;
    }

    /**
     * 推送百度等
     *
     * @param string $api   推送目标地址
     * @param array $urls   推送地址
     * @return void
     */
    public static function pushUrl(string $api, array $urls)
    {
        $ch = curl_init();
        
        $options =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);

        return curl_exec($ch);
    }

    /**
     * 获取蜘蛛数量
     *
     * @param string $type
     * @return void
     */
    public static function getCount($type='today')
    {
        $condition = [];
        switch ($type) {
            case 'today':
                $condition = [
                    Carbon::now()->startOfDay()->toDateTimeString(),
                    Carbon::now()->endOfDay()->toDateTimeString(),
                ];
                break;
            case 'yesterday':
                $condition = [
                    Carbon::yesterday()->startOfDay()->toDateTimeString(),
                    Carbon::yesterday()->endOfDay()->toDateTimeString(),
                ];
                break;
            case 'all':
                $condition = [];
                break;
            default:
                $condition = [];
        }

        if (!empty($condition)) {
            $result = SpiderRecord::whereBetween('created_at', $condition)
                        ->count();
        } else {
            $result = SpiderRecord::count();
        }

        return $result;
    }
}
