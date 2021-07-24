<?php

namespace App\Http\Middleware;


use App\Models\Config;
use App\Services\Gather\CrawlService;
use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VerifyAuthCode
{

    public function handle(Request $request, Closure $next)
    {

        if (!$request->is('*admin/install*') && !conf('auth.code')) {

            return redirect()->to('/admin/install');
        }

        if ($request->is('*admin/install*')) {
            return $next($request);
        }


        if (Cache::get('pass_auth')) {
            return $next($request);
        }

        $authCode = trim(conf('auth.code'));


        $data = [
            'code'        => $authCode,
            'os'          => php_uname(),
            'software'    => $_SERVER["SERVER_SOFTWARE"],
            'ip'          => $_SERVER["SERVER_ADDR"],
            'server_name' => $_SERVER["SERVER_NAME"],
            'server_port' => $_SERVER["SERVER_PORT"],
            'php_version' => PHP_VERSION,
        ];

        $domain = trim(config('seo.auth_domain'), '/');

        $url = $domain . '/api/v1/auth';


        $res = CrawlService::post($url, $data);


        if (data_get($res, 'code') != 200) {
            admin_error($res['message']);

            return redirect()->to('/admin/install');
        }

        Config::updateOrInsert( //更新到期时间
            [
                'module' => 'auth',
                'key'    => 'effective_days',
            ],
            [
                'value' => data_get($res, 'data.effective_days')
            ]);

        Cache::put('pass_auth', 1, 3600);//每小时访问服务器一次

        return $next($request);
    }

}
