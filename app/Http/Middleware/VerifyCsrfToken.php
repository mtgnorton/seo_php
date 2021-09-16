<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/admin/gathers*'
    ];


    //新增代码
    public function handle($request, $next)
    {
        // 判断是否为禁用路由，如果是，关闭CSRF验证
        if (!$request->is('admin/*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
