<?php

namespace App\Console\Commands;

use App\Constants\RedisCacheKeyConstant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class UpdateSpiderData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新蜘蛛相关数据';

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
        dump("开始更新蜘蛛数据-----");
        $baseCountKey   = RedisCacheKeyConstant::REDIS_SPIDER_COUNT;
        $basePieKey   = RedisCacheKeyConstant::REDIS_SPIDER_PIE;
        $baseHourKey   = RedisCacheKeyConstant::REDIS_SPIDER_HOUR;

        $today = now()->toDateString();
        $key1 = $baseCountKey . $today;
        $key2 = $baseCountKey . 'all';
        $key3 = $basePieKey . $today;
        $key4    = $basePieKey . Carbon::parse('-6 days')->toDateString() . '_' . $today;
        $key5   = $basePieKey . Carbon::parse('-29 days')->toDateString() . '_' . $today;
        $key6  = $basePieKey . Carbon::parse('-364 days')->toDateString() . '_' . $today;
        $key7 = $baseHourKey . $today;

        Redis::del($key1);
        Redis::del($key2);
        Redis::del($key3);
        Redis::del($key4);
        Redis::del($key5);
        Redis::del($key6);
        Redis::del($key7);
        dump("-----更新蜘蛛数据成功");
    }
}
