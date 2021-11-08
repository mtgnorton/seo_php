<?php

namespace App\Http\Middleware;


use App\Services\Gather\CrawlService;
use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\MessageBag;

class VerifyDemoAccount
{

    public function handle(Request $request, Closure $next)
    {

        if (!Auth::user()) {
            return $next($request);
        }

        $forbidURLs = ['admin/install-save'];

        $nowURL = trim($request->path(), '/');

        if (stripos(Auth::user()->username, 'demo') !== false && (!$request->isMethod('GET') || in_array($nowURL, $forbidURLs))) {


            if (request()->ajax() && request()->header('X-PJAX') != 'true') {

                return response()->json([
                    'status' => true,
                    'then'   => [
                        'action' => 'refresh'
                    ],
                    'toastr' => [
                        'type'    => 'error',
                        'content' => '演示账号没有权限'
                    ]
                ]);

            } else {

                return back_error('演示账号没有权限');

            }

        }

        return $next($request);
    }

}
