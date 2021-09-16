<?php

namespace App\Http\Middleware;


use App\Constants\RedisCacheKeyConstant;
use App\Models\Config;
use App\Services\Gather\CrawlService;
use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Redis;

class RequestLimit
{

    public function handle(Request $request, Closure $next)
    {

        if ($this->counter()) {
            return $next($request);

        } else {
            $this->deny();
        }


    }


    public function counter()
    {

        $key = RedisCacheKeyConstant::REDIS_LIMIT;

        $limitConcurrentAmount = config('seo.request_concurrent_limit');

        $nowMsTime = ms_time();


        $periodSecond = 1; //1s的并发限制数量
        /**
         * @var $redis \Redis
         */
        $redis = app('redis');

        $pipe = $redis->multi(Redis::PIPELINE); //使用管道提升性能

        $pipe->zadd($key, [], $nowMsTime, $nowMsTime); //value 和 score 都使用毫秒时间戳

        $pipe->zremrangebyscore($key, 0, $nowMsTime - $periodSecond * 1000); //移除时间窗口之前的行为记录，剩下的都是时间窗口内的
        $pipe->zcard($key);  //获取窗口内的行为数量

        //$pipe->expire($key, $periodSecond + 1);  //多加一秒过期时间

        $replies = $pipe->exec();

        $concurrentAmount = data_get($replies, '2', 0);

        return $concurrentAmount <= $limitConcurrentAmount;

    }

    public function deny()
    {
        echo "当前访问过多,请稍后访问";
        exit;
    }
}
