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
        Cache::forget($key);

        // // 域名刪除
        // $websiteTemplates = $template->websiteTemplates;
        
        // foreach ($websiteTemplates as $websiteTemplate) {
        //     $websiteTemplate->delete();
        // }
    }
}
