<?php

namespace App\Providers;

use App\Services\CommonService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.debug') == true) {

//            DB::listen(function ($query) {
//                $sql       = str_replace("?", "'%s'", $query->sql);
//                $processID = getmypid();
//                $log       = "[进程:$processID][{$query->time}ms] " . vsprintf($sql, $query->bindings);
//                if (Str::contains($log, 'admin_permissions') || Str::contains($log, 'admin_roles') || Str::contains($log, 'admin_operation_log') || Str::contains($log, 'admin_users')) {
//                    return;
//                }
//
//                Log::channel('sql')->info($log);
//            });
        }

        if (!app()->runningInConsole()) {
            $groupId = request()->input('group_id');
            $pathData = [
                '/admin/content-categories',
                '/admin/templates',
                '/admin/ads',
                '/admin/caches'
            ];
            $path = request()->getPathInfo();

            $menus = [];
            if (in_array($path, $pathData)) {
                $menus = [
                    '/admin/templates' => [
                        'status' => false,
                        'url' => 'templates?group_id='.$groupId,
                        'title' => '模板设置'
                    ],
                    '/admin/content-categories' => [
                        'status' => false,
                        'url' => 'content-categories?type=title&group_id='.$groupId,
                        'title' => '物料库'
                    ],
                    '/admin/ads' => [
                        'status' => false,
                        'url' => 'ads?group_id='.$groupId,
                        'title' => '广告管理'
                    ],
                    '/admin/caches' => [
                        'status' => false,
                        'url' => 'caches?group_id='.$groupId,
                        'title' => '缓存设置'
                    ],
                ];

                foreach ($menus as $key => &$menu) {
                    if ($path == $key) {
                        $menu['status'] = true;
                    }
                }
            }

            View::share('menu_three', $menus);
        }
    }
}
