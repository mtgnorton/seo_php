<?php

namespace App\Listeners;

use App\Constants\RedisCacheKeyConstant;
use App\Events\TemplateDeletingEvent;
use App\Events\TemplateGroupDeletingEvent;
use App\Events\WebsiteTemplateDeletingEvent;
use App\Models\Config;
use App\Models\ContentCategory;
use App\Models\Template;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TemplateGroupDeletingeventListener
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
    public function handle(TemplateGroupDeletingEvent $event)
    {
        common_log('开始删除模板分组');
        DB::beginTransaction();

        try {
            $group = $event->model;
            if (empty($group)) {
                throw new Exception('删除分组失败, 分组获取失败.');
            }
            $groupId = $group->id;
            // 获取分类信息
            // $categoryId = $group->category_id;
            // $categoryKey = RedisCacheKeyConstant::CACHE_DELETE_CATEGORY . $categoryId;
            // $category = Cache::get($categoryKey, []);
            // $categoryTag = $category['tag'] ?? '';
            // $groupTag = $group->tag;

            // if (empty($groupId) || empty($categoryTag) || empty($groupTag)) {
            if (empty($groupId)) {
                throw new Exception('删除分组失败, 分组数据获取失败.');
            }
            // 将分组数据写入缓存
            $key = RedisCacheKeyConstant::CACHE_DELETE_GROUP . $groupId;
            Cache::put($key, $group, 3600);
    
            // 1. 删除模板
            $templates = Template::where('group_id', $groupId)->get();
            foreach  ($templates as $template) {
                $template->delete();
            }
    
            // 2. 删除配置
            Config::where('group_id', $groupId)->delete();
    
            // 3. 域名刪除
            $websites = $group->websites;
            
            foreach ($websites as $website) {
                $website->delete();
            }
    
            // 4. 内容分类删除
            $contentCategoires = ContentCategory::where('group_id', $groupId)->get();
            foreach ($contentCategoires as $contentCategory) {
                $contentCategory->delete();
            }
            // 5. 删除目录文件夹
            // 判断分类是否还存在
            // if (!empty($group->category)) {
            //     $path = 'template/'.$categoryTag . '/' . $groupTag;
            //     if ($path == 'template/') {
            //         throw new Exception('标签数据获取失败');
            //     }
            //     if (Storage::disk('public')->exists($path)) {
            //         Storage::disk('public')->deleteDirectory($path);
            //     }
            // }

            DB::commit();

            common_log('删除模板分组成功, ID为: '.$groupId);
        } catch (Exception $e) {
            DB::rollBack();
            common_log('删除分组失败, 失败ID为: '.$event->model->id ?? 0, $e);
        }
    }
}
