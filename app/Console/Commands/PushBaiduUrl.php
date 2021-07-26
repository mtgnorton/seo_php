<?php

namespace App\Console\Commands;

use App\Constants\RedisCacheKeyConstant;
use App\Services\CommonService;
use App\Services\IndexPregService;
use App\Services\SpiderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PushBaiduUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baiduUrl:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将网址推送到百度等平台中';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 获取配置数据
        $config = conf('push');
        $autoConfig = conf('push.auto_push');

        // 判断是否开启推送
        if ($autoConfig['is_open'] != 'on') {
            return false;
        }

        $time = time();
        $lastPushTime = Cache::get('lastPushTime');

        // 判断是否到下次推送时间
        if (($time - (int)$lastPushTime) < (60 * (int)$autoConfig['interval'])) {
            return false;
        }

        Cache::put('lastPushTime', $time - 10);

        $pushSumKey = RedisCacheKeyConstant::BAIDU_PUSH_AMOUNT;
        $pushSum = 0;
        // 判断缓存中是否存在该值
        if (Cache::has($pushSumKey)) {
            $pushSum = Cache::get($pushSumKey);
        }

        // 获取百度普通收录参数
        $baiduNormalData = CommonService::linefeedStringToArray($config['baidu_normal']);

        foreach ($baiduNormalData as $normal) {
            if (empty($normal)) {
                continue;
            }
            $normalData = explode('----', $normal);
            $host = $normalData[0] ?? '';
            $token = $normalData[1] ?? '';
            $pushCount = $normalData[2] ?? 10;
            $urlRule = $normalData[3] ?? '{随机字母5}/{随机数字3}.html';
            $baseUrl = rtrim($host, '/') . '/' . ltrim($urlRule, '/');

            // 去除域名中的http://和https://
            $host = str_replace('http://', '', $host);
            $host = str_replace('https://', '', $host);

            // 判断缓存中是否有已推送完标识
            $key = $host . $urlRule . '_finish_push';
            if (Cache::has($key)) {
                continue;
            }

            $api = "http://data.zz.baidu.com/urls?site=".$host."&token=".$token;
            $urls = [];
            
            for ($i = 0; $i < $pushCount; $i++) {
                $urls[] = preg_replace_callback('/{(随机数字|随机字母)+\d*}/', function ($match) {
                    $key = $match[0];
                    $type = $match[1] ?? '';

                    return IndexPregService::randSystemTag($type, $key);
                }, $baseUrl);
            }

            $result = SpiderService::pushUrl($api, $urls);

            $resArr = json_decode($result, true);
            $error = isset($resArr['error']) ? $resArr['error'] : 0;
            $endTime = Carbon::now()->endofday()->timestamp;
            $now = Carbon::now()->timestamp;
            $expiredTime = $endTime - $now;

            if ($error == 400) {
                Cache::put($key, true, $expiredTime);
            }

            // 加入新增数量
            $successCount = $resArr['success'] ?: 0;
            $pushSum += $successCount;

            Cache::put($pushSumKey, $pushSum, $expiredTime);

            common_log('百度普通收录, 参数: '.$normal.', 收录结果: '.$result);
        }
    }
}
