<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

class RateLimited
{
    /**
     * 处理队列中的任务.
     *
     * @param mixed $job
     * @param callable $next
     * @return mixed
     */
    public function handle($job, $next)
    {


        Redis::throttle('concurrent-limit')
            ->block(0)->allow(2)->every(2)
            ->then(function () use ($job, $next) {

                $next($job);
            }, function () use ($job) {
                // 无法获取锁…

                $job->release(5);
            });
    }
}
