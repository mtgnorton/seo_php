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
        $category = $event->model;
        $categoryId = $category->id;
        common_log('开始删除内容分类, 分类ID为: '.$categoryId);
        $key = RedisCacheKeyConstant::CACHE_DELETE_CONTENT_TEMPLATE . $categoryId;
        Cache::put($key, $category, 3600);
        // 删除分类下对应文件
        $files = File::where('category_id', $categoryId)->get();
        foreach ($files as $file) {
            $file->delete();
        }

        // 删除子类
        $children = ContentCategory::where('parent_id', $categoryId)->get();
        common_log($children);
        foreach ($children as $child) {
            $child->delete();
        }

        // 清除缓存
        $key = RedisCacheKeyConstant::CACHE_CONTENT_CATEGORIES;
        Cache::store('file')->forget($key);

        common_log('删除内容分类成功, 分类ID为: '.$categoryId);
    }
}
