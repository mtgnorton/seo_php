<?php

namespace App\Listeners;

use App\Constants\RedisCacheKeyConstant;
use App\Events\WebsiteDeletingEvent;
use App\Events\WebsiteTemplateDeletingEvent;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class WebsiteDeletingeventListener
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
    public function handle(WebsiteDeletingEvent $event)
    {
        // 清空缓存数据
        $websiteKey = RedisCacheKeyConstant::CACHE_WEBSITES;
        Cache::store('file')->forget($websiteKey);

        $website = $event->model;
        if (!empty($website->url)) {
            $websiteKey = $website->url  . 'website_name';
            Cache::store('file')->forget($websiteKey);
        }
    }
}
