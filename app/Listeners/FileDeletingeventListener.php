<?php

namespace App\Listeners;

use App\Constants\ContentConstant;
use App\Constants\RedisCacheKeyConstant;
use App\Events\FileDeletingEvent;
use App\Models\ContentCategory;
use App\Models\Diy;
use App\Services\ContentService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FileDeletingeventListener
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
    public function handle(FileDeletingEvent $event)
    {
        common_log('开始删除文件');
        $model = $event->model;
        $type = $model->getOriginal('type');
        $path = $model->path;
        $categoryId = $model->category_id;
        $tag = ContentService::contentTag($categoryId, $type);

        // 3. 删除对应文件缓存
        if ($type == 'article') {
            $types = ['article_title', 'article_content'];
        } else {
            $types = [$type];
        }

        foreach ($types as $baseType) {
            $baseKey = ContentConstant::cacheKeyText()[$baseType] ?? '';
            // $category = ContentCategory::find($categoryId);
            $categoryKey = RedisCacheKeyConstant::CACHE_DELETE_CONTENT_TEMPLATE . $categoryId;
            $category = Cache::get($categoryKey);
            $groupId = $category->group_id ?? 0;
    
            try {
                // 1. 如果文件存在, 删除文件
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
    
                // 2. 删除文件对应内容数据库记录
                if ($type == 'diy') {
                    Diy::where('file_id', $model->id)->delete();
                } else {
                    $contentModel = ContentService::CONTENT_MODEL[$type] ?? '';
    
                    if (empty($contentModel)) {
                        return '';
                    }
                    // 删除对应缓存
                    $typeName = ContentService::CONTENT_TAG[$type] ?? '';
                    $key1 = $baseKey . $groupId . $typeName;
                    Cache::store('file')->forget($key1);
    
                    $contentModel::where('file_id', $model->id)->delete();
                }
    
                $key2 = $baseKey . $groupId . $tag;
                Cache::store('file')->forget($key2);
                // 上级分类key
                $parentId = $category->parent_id ?? 0;
                $parentTag = ContentService::contentTag($parentId, $type);
                $key3 = $baseKey . $groupId . $parentTag;
                Cache::store('file')->forget($key3);
    
                common_log('删除文件成功, 文件ID为: '.$model->id);
            } catch (Exception $e) {
                common_log('文件内容删除失败, 文件ID为: '.$model->id, $e);
            }
        }
    }
}
