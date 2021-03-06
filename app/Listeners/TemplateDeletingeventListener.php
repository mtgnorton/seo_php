<?php

namespace App\Listeners;

use App\Constants\RedisCacheKeyConstant;
use App\Events\TemplateDeletingEvent;
use App\Events\WebsiteTemplateDeletingEvent;
use App\Models\Template;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TemplateDeletingeventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TemplateDeletingEvent $event)
    {
        $template = $event->model;
        $templateId = $template->id ?? 0;
        common_log('开始删除模板,模板ID为: '.$templateId);

        $key = RedisCacheKeyConstant::CACHE_DELETE_TEMPLATE . $templateId;
        Cache::put($key, $template, 3600);

        // 模塊刪除
        $modules = $template->modules;

        foreach ($modules as $module) {
            $module->delete();
        }

        // 素材刪除
        $materials = $template->materials;

        foreach ($materials as $material) {
            $material->delete();
        }

        // 删除缓存
        $key = RedisCacheKeyConstant::CACHE_TEMPLATES;
        Cache::store('file')->forget($key);

        common_log('删除模板成功,模板ID为: '.$templateId);
        // // 域名刪除
        // $websiteTemplates = $template->websiteTemplates;
        
        // foreach ($websiteTemplates as $websiteTemplate) {
        //     $websiteTemplate->delete();
        // }
    }
}
