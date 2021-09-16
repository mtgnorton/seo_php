<?php

namespace App\Http\Middleware;

use Closure;

class DisableSessionMiddleware
{
    public function handle($request, Closure $next)
    {

        //判断是否为禁用路由，如果是，关闭session记录
        if (!$request->is('admin*')) {

            config()->set('session.driver', 'array');
        }

        return $next($request);
    }
}
