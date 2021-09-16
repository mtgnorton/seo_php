<?php

namespace App\Services;

use App\Constants\RedisCacheKeyConstant;
use App\Constants\SpiderConstant;
use App\Models\SpiderRecord;
use App\Models\SpiderUserAgent;
use App\Models\Website;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

use Redis as RedisOrigin;

/**
 * 蜘蛛服务类
 */
class SpiderService extends BaseService
{
    /**
     * 记录蜘蛛
     *
     * @param string $urlType 链接类型
     * @return void
     */
    public static function spiderRecord($urlType = '')
    {
        if ($urlType === '') {
            $urlType = IndexService::getUriType();
        }
        $type      = self::getSpider();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (empty($type)) {
            return [];
        }
        $ip   = CommonService::getUserIpAddr();
        $host = request()->getHost();
        $url  = request()->url();

        $categoryId = CommonService::getCategoryId();
        $groupId    = TemplateService::getGroupId();
        $templateId = TemplateService::getWebsiteTemplateId();

        // 获取对应的user_agent_id
        $userAgents = self::userAgents();
        if (array_key_exists($userAgent, $userAgents)) {
            $userAgentId = $userAgents[$userAgent];
        } else {
            $agent       = SpiderUserAgent::create(['user_agent' => $userAgent]);
            $userAgentId = $agent->id;
            Cache::forget(RedisCacheKeyConstant::CACHE_SPIDER_USER_AGENTS);
        }

        // 获取redis中的蜘蛛存量数, 如果数量已满20条, 则插入数据库
        $now        = Carbon::now()->toDateTimeString();
        $insertData = [
            'type'          => $type,
            // 'user_agent' => $userAgent,
            'user_agent_id' => $userAgentId,
            'ip'            => $ip,
            'host'          => $host,
            'url'           => $url,
            'url_type'      => $urlType,
            'category_id'   => $categoryId,
            'group_id'      => $groupId,
            'template_id'   => $templateId,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];


        try {
            $spiderKey = RedisCacheKeyConstant::REDIS_SPIDER_RECORDS;
            $len       = Redis::hlen($spiderKey);
            $maxNum    = 20;
            if ($len >= $maxNum - 1) {
                $baseData = Redis::hgetall($spiderKey);
                $allData  = [];
                foreach ($baseData as $base) {
                    $allData[] = json_decode($base, true);
                }
                $allData[] = $insertData;

                DB::table('spider_records')->insert($allData);

                Redis::del($spiderKey);
            } else {
                $spiderField = mt_rand(0, 10000);
                Redis::hset($spiderKey, $spiderField, json_encode($insertData, JSON_UNESCAPED_UNICODE));
            }

            // 更新redis数据
            self::updateRedisCount($type);

        } catch (Exception $e) {
            Redis::del($spiderKey);
            $len = isset($len) ? $len : 0;
            common_log('插入蜘蛛记录失败, 失败条数为: ' . $len, $e);
        }
    }

    /**
     * 更新redis数据
     *
     * @return void
     */
    public static function updateRedisCount($type = '')
    {

        // 1. 蜘蛛数量统计
        // 1.1. 今日蜘蛛统计+1
        $baseCountKey   = RedisCacheKeyConstant::REDIS_SPIDER_COUNT;
        $spiderCountKey = $baseCountKey . now()->toDateString();

        $pipeGet = Redis::multi(RedisOrigin::PIPELINE);

        $todayCount = $pipeGet->get($spiderCountKey);
        // 1.2. 全部蜘蛛+1
        $spiderAllCountKey = $baseCountKey . 'all';
        $allCount          = $pipeGet->get($spiderAllCountKey);


        $basePieKey   = RedisCacheKeyConstant::REDIS_SPIDER_PIE;
        $today        = now()->toDateString();
        $spiderPieKey = $basePieKey . $today;
        $pieData      = $pipeGet->hgetall($spiderPieKey);

        // 2.2更新七日, 30日, 一年数据
        $spider7PieKey    = $basePieKey . Carbon::parse('-6 days')->toDateString() . '_' . $today;
        $spider7PieData   = $pipeGet->hgetall($spider7PieKey);
        $spider30PieKey   = $basePieKey . Carbon::parse('-29 days')->toDateString() . '_' . $today;
        $spider30PieData  = $pipeGet->hgetall($spider30PieKey);
        $spider365PieKey  = $basePieKey . Carbon::parse('-364 days')->toDateString() . '_' . $today;
        $spider365PieData = $pipeGet->hgetall($spider365PieKey);


        // 3. 更新时段走势图
        $baseHourKey   = RedisCacheKeyConstant::REDIS_SPIDER_HOUR;
        $spiderHourKey = $baseHourKey . $today;
        $todayData     = $pipeGet->hgetall($spiderHourKey);

        $replies = $pipeGet->exec();

        list($todayCount, $allCount, $pieData, $spider7PieData, $spider30PieData, $spider365PieData, $todayData) = $replies;


        $pipeSet = Redis::multi(RedisOrigin::PIPELINE);

        if (!empty($todayCount)) {
            $pipeSet->incr($spiderCountKey);
        }

        if (!empty($allCount)) {
            $pipeSet->incr($spiderAllCountKey);
        }

        // 2. 蜘蛛访问比率统计
        // 2.1 更新今日蜘蛛数据
        $typeKey = SpiderConstant::typeNumText()[$type];

        if (!empty($pieData)) {
            $pipeSet->hincrby($spiderPieKey, $typeKey, 1);
        }

        if (!empty($spider7PieData)) {
            $pipeSet->hincrby($spider7PieKey, $typeKey, 1);
        }
        if (!empty($spider30PieData)) {
            $pipeSet->hincrby($spider30PieKey, $typeKey, 1);
        }
        if (!empty($spider365PieData)) {
            $pipeSet->hincrby($spider365PieKey, $typeKey, 1);
        }
        $typeKey = now()->hour;

        if (!empty($todayData)) {
            $pipeSet->hincrby($spiderHourKey, $typeKey, 1);
        }
        $pipeSet->exec();
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
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $spiderTypes                               = SpiderConstant::allRuleText();
        $spiderTypes[SpiderConstant::SPIDER_OTHER] = SpiderConstant::ruleText()[SpiderConstant::SPIDER_OTHER];

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
            $type       = $spiderConfig['no_spider']['type'] ?? 'black_list';
            $spiderType = self::getAllSpider();
            if (!empty($spiderType)) {
                if ($type == 'white_list') {
                    $whiteList = array_filter($spiderConfig['no_spider']['white_list']);

                    if (!in_array($spiderType, $whiteList)) {
                        abort(403);
                    }
                } else {
                    $blackList = array_filter($spiderConfig['no_spider']['black_list']);

                    if (in_array($spiderType, $blackList)) {
                        abort(403);
                    }
                }
            }

            // $typeStr = $spiderConfig['no_spider']['type'];
            // $noTypes = CommonService::linefeedStringToArray($typeStr);

            // $type = self::getAllSpider();

            // if (in_array($type, $noTypes)) {
            //     abort(403);
            // }
        }

        // 2. 判断是否开启强引
        if ($spiderConfig['spider_strong_attraction']['is_open'] == 'on') {
            $typeStr = $spiderConfig['spider_strong_attraction']['type'];
            $types   = CommonService::linefeedStringToArray($typeStr);

            $type = self::getAllSpider();

            if (in_array($type, $types)) {
                $urlStr = $spiderConfig['spider_strong_attraction']['urls'];
                $urls   = CommonService::linefeedStringToArray($urlStr);

                if (!empty($urls)) {
                    $randKey = count($urls) - 1 >= 0 ? count($urls) - 1 : 0;
                    $url     = $urls[mt_rand(0, $randKey)] ?? '';

                    return redirect($url);
                }
            }
        }

        return false;
    }

    /**
     * 推送百度等
     *
     * @param string $api 推送目标地址
     * @param array $urls 推送地址
     * @return void
     */
    public static function pushUrl(string $api, array $urls)
    {
        $ch = curl_init();

        $options = array(
            CURLOPT_URL            => $api,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => implode("\n", $urls),
            CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
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
    public static function getCount($type = 'today')
    {
        $condition = [];
        $baseKey   = RedisCacheKeyConstant::REDIS_SPIDER_COUNT;

        switch ($type) {
            case 'today':
                $key       = $baseKey . now()->toDateString();
                $condition = [
                    Carbon::now()->startOfDay()->toDateTimeString(),
                    Carbon::now()->endOfDay()->toDateTimeString(),
                ];
                break;
            case 'yesterday':
                $key       = $baseKey . now()->yesterday()->toDateString();
                $condition = [
                    Carbon::yesterday()->startOfDay()->toDateTimeString(),
                    Carbon::yesterday()->endOfDay()->toDateTimeString(),
                ];
                break;
            case 'all':
                $key       = $baseKey . 'all';
                break;
        }
        $data = Redis::get($key);
        if (!empty($data)) {
            return $data;
        }

        if (!empty($condition)) {
            $data = SpiderRecord::whereBetween('created_at', $condition)
                ->count();
        } else {
            $data = SpiderRecord::count();
        }
        // $expiredTime = now()->tomorrow()->addSecond()->timestamp;
        // $seconds     = $expiredTime - time();
        $seconds = mt_rand(3000, 3500);
        
        Redis::setex($key, $seconds, $data);

        return $data;
    }

    /**
     * 获取所有的蜘蛛user_agent
     *
     * @return array
     */
    public static function userAgents()
    {
        $key = RedisCacheKeyConstant::CACHE_SPIDER_USER_AGENTS;
        if (!empty(cache_static_file_get($key))) {
            return cache_static_file_get($key);
        }

        $data = SpiderUserAgent::pluck('id', 'user_agent')->toArray();

        Cache::store('file')->put($key, $data, 86400);

        return $data;
    }
}
