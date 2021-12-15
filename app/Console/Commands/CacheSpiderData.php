<?php

namespace App\Console\Commands;

use App\Constants\RedisCacheKeyConstant;
use App\Constants\SpiderConstant;
use App\Models\SpiderRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CacheSpiderData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '缓存首页蜘蛛数据';

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
        common_log('----------开始缓存蜘蛛数据----------');
        dump('----------开始缓存蜘蛛数据----------');
        // 1. 圆饼数据
        $basePieKey = RedisCacheKeyConstant::REDIS_SPIDER_PIE;
        // 1.1. 今日数据
        $todayPieKey = $basePieKey . now()->toDateString();
        $todayPieMinTime = Carbon::now()->startOfDay()->toDateTimeString();
        $todayPieMaxTime = Carbon::now()->endOfDay()->toDateTimeString();
        $this->cachePieData($todayPieKey, $todayPieMinTime, $todayPieMaxTime);
        // 1.2. 昨日数据
        $yesterdayPieKey = $basePieKey . now()->yesterday()->toDateString();
        $yesterdayPieMinTime = Carbon::yesterday()->startOfDay()->toDateTimeString();
        $yesterdayPieMaxTime = Carbon::yesterday()->endOfDay()->toDateTimeString();
        $this->cachePieData($yesterdayPieKey, $yesterdayPieMinTime, $yesterdayPieMaxTime);
        // 1.3. 7日数据
        $sevenPieKey = $basePieKey . Carbon::parse('-6 days')->toDateString() . '_' . now()->toDateString();
        $sevenPieMinTime = Carbon::parse('-6 days')->startOfDay()->toDateTimeString();
        $sevenPieMaxTime = Carbon::now()->endOfDay()->toDateTimeString();
        $this->cachePieData($sevenPieKey, $sevenPieMinTime, $sevenPieMaxTime);
        // 1.4. 30日数据
        $thirtyPieKey = $basePieKey . Carbon::parse('-29 days')->toDateString() . '_' . now()->toDateString();
        $thirtyPieMinTime = Carbon::parse('-29 days')->startOfDay()->toDateTimeString();
        $thirtyPieMaxTime = Carbon::now()->endOfDay()->toDateTimeString();
        $this->cachePieData($thirtyPieKey, $thirtyPieMinTime, $thirtyPieMaxTime);
        // 1.4. 1年数据
        $thirtyPieKey = $basePieKey . Carbon::parse('-364 days')->toDateString() . '_' . now()->toDateString();
        $thirtyPieMinTime = Carbon::parse('-364 days')->startOfDay()->toDateTimeString();
        $thirtyPieMaxTime = Carbon::now()->endOfDay()->toDateTimeString();
        $this->cachePieData($thirtyPieKey, $thirtyPieMinTime, $thirtyPieMaxTime);

        // 2. 时段走势数据
        $baseHourKey = RedisCacheKeyConstant::REDIS_SPIDER_HOUR;
        // 2.1. 今日数据
        $todayHourKey = $baseHourKey . now()->toDateString();
        $todayDate = now()->toDateString();
        $this->cacheHourData($todayHourKey, $todayDate);
        // 2.1. 昨日数据
        $yesterdayHourKey = $baseHourKey . now()->yesterday()->toDateString();
        $yesterdayDate = now()->yesterday()->toDateString();
        $this->cacheHourData($yesterdayHourKey, $yesterdayDate);
        // 2.1. 前日数据
        $beforeYesterdayHourKey = $baseHourKey . now()->subdays(2)->toDateString();
        $beforeYesterdayDate = now()->subdays(2)->toDateString();
        $this->cacheHourData($beforeYesterdayHourKey, $beforeYesterdayDate);
        common_log('----------缓存蜘蛛数据成功----------');
        dump('----------缓存蜘蛛数据成功----------');
    }

    /**
     * 缓存蜘蛛数据
     *
     * @param string $key
     * @param string $minTime
     * @param string $maxTime
     * @return void
     */
    private function cachePieData($key, $minTime, $maxTime)
    {
        $spiderTypes = SpiderConstant::typeText();
        unset($spiderTypes['']);
        // 获取时间段内最小和最大ID
        $minId = SpiderRecord::where('created_at', '>=', $minTime)
                            ->limit(1)
                            ->orderBy('id', 'asc')
                            ->value('id');
        $maxId = SpiderRecord::where('created_at', '<=', $maxTime)
                            ->limit(1)
                            ->orderBy('id', 'desc')
                            ->value('id');

        foreach($spiderTypes as $spiderKey => $type) {
            $count = SpiderRecord::where('type', $spiderKey)
                            ->whereBetween('id', [$minId, $maxId])
                            ->count();
            $values[] = $count;
        }
        Redis::hmset($key, $values);
        Redis::expire($key, 2000);
    }

    /**
     * 缓存分时数据
     *
     * @param string $key
     * @param string $date
     * @return void
     */
    private function cacheHourData($key, $date)
    {
        $condition = [];
        $data = [];
        // 循环获取每小时的数量
        for($i=0; $i<24; $i++) {
            $dateBase = $date . ' ' . sprintf('%02d', $i);
            $condition = [
                $dateBase . ':00:00',
                $dateBase . ':59:59',
            ];

            $data[] = SpiderRecord::whereBetween('created_at', $condition)
                                            ->count();
        }
        Redis::hmset($key, $data);
        Redis::expire($key, 2000);
    }
}
