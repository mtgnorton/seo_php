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


        if (Cache::store('file')->get('pass_auth')) {
            return $next($request);
        }

        $authCode = trim(Config::where('key', 'code')->value('value'));


        $data = [
            'code'        => $authCode,
            'os'          => php_uname(),
            'software'    => $_SERVER["SERVER_SOFTWARE"],
            'ip'          => $_SERVER["SERVER_ADDR"],
            'server_name' => $request->getHost(),
            'server_port' => $_SERVER["SERVER_PORT"],
            'php_version' => PHP_VERSION,
        ];

        $domain = get_auth_domain();

        $url = $domain . '/api/v1/auth';


        $res = CrawlService::post($url, $data);


        if (data_get($res, 'code') != 200) {
            $message = data_get($res, 'message', '授权系统连接失败');
            admin_error($message);

            return redirect()->to('/admin/install');
        }


        conf_insert_or_update('effective_days', data_get($res, 'data.effective_days'), 'auth');
        conf_insert_or_update('use_begin_time', data_get($res, 'data.use_begin_time'), 'auth');
        conf_insert_or_update('domain', data_get($res, 'data.domain'), 'auth');
        conf_insert_or_update('can_modify_times', data_get($res, 'data.can_modify_times'), 'auth', true);

        Cache::store('file')->put('pass_auth', 1, 3600);//每小时访问服务器一次

        return $next($request);
    }

}
