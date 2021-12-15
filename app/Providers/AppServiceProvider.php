<?php

namespace App\Providers;

use App\Contracts\AiContent;
use App\Services\CommonService;
use App\Utils\BaiduAiContent;
use App\Utils\SougouAiContent;
use Illuminate\Support\Arr;
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
        // // 绑定智能内容契约
        // $this->app->singleton(AiContent::class, function ($app) {
        //     $classes = CommonService::SEARCH_CLASS;
        //     $class = Arr::random($classes);

        //     return new $class();
        // });
        // 绑定智能内容门面
        $this->app->bind('aiContent', function () {
            $classes = CommonService::SEARCH_CLASS;
            $class = Arr::random($classes);

            return new $class();
        });
        // 绑定翻译门面
        $this->app->bind('translate', function () {
            $classes = CommonService::TRANSLATE_CLASS;
            $class = Arr::random($classes);

            return new $class();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.debug') == true) {

            DB::listen(function ($query) {
                $sql       = str_replace("?", "'%s'", $query->sql);
                $processID = getmypid();
                $log       = "[进程:$processID][{$query->time}ms] " . vsprintf($sql, $query->bindings);
                if (Str::contains($log, 'admin_permissions') || Str::contains($log, 'admin_roles') || Str::contains($log, 'admin_operation_log') || Str::contains($log, 'admin_users')) {
                    return;
                }

                Log::channel('sql')->info($log);
            });
        }

        if (!app()->runningInConsole()) {
            $groupId = request()->input('group_id');
            $path = request()->getPathInfo();
            $menus = CommonService::getTemplateMenus($groupId, $path);

            View::share('menu_three', $menus);
        }
    }
}
