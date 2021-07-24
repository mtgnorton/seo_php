<?php

namespace App\Http\Middleware;


use App\Services\Gather\CrawlService;
use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VerifyDemoAccount
{

    public function handle(Request $request, Closure $next)
    {

        if (!Auth::user()) {
            return $next($request);
        }

        if (stripos(Auth::user()->username, 'demo') !== false && !\request()->isMethod('GET')) {
            return back_error('演示账号没有权限');
        }

        return $next($request);
    }

}
