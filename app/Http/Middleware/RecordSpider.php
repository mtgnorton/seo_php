<?php

namespace App\Http\Middleware;

use App\Services\CommonService;
use App\Services\IndexService;
use App\Services\SpiderService;
use App\Services\TemplateService;
use Closure;
use Illuminate\Http\RedirectResponse;

class RecordSpider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 判断是否屏蔽或强引蜘蛛
        $spiderResult = SpiderService::spiderOption();

        if ($spiderResult instanceof RedirectResponse) {
            return $spiderResult;
        }

        // 判断uri类型
        $type = IndexService::getUriType();

        // 记录蜘蛛
        SpiderService::spiderRecord($type);

        return $next($request);
    }
}
