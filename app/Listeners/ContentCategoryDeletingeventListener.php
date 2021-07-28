<?php

namespace App\Listeners;

use App\Constants\RedisCacheKeyConstant;
use App\Events\ContentCategoryDeletingEvent;
use App\Events\TemplateDeletingEvent;
use App\Events\TemplateGroupDeletingEvent;
use App\Events\WebsiteTemplateDeletingEvent;
use App\Models\Config;
use App\Models\ContentCategory;
use App\Models\File;
use App\Models\Template;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ContentCategoryDeletingeventListener
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
    public function handle(ContentCategoryDeletingEvent $event)
    {
        $categoryId = $event->model->id;
        // 4. 内容分类删除
        $files = File::where('category_id', $categoryId)->get();
        foreach ($files as $file) {
            $file->delete();
        }

        // 清除缓存
        $key = RedisCacheKeyConstant::CACHE_CONTENT_CATEGORIES;
        Cache::forget($key);
    }
}
