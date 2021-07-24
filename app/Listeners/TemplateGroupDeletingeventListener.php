<?php

namespace App\Listeners;

use App\Events\TemplateDeletingEvent;
use App\Events\TemplateGroupDeletingEvent;
use App\Events\WebsiteTemplateDeletingEvent;
use App\Models\Config;
use App\Models\ContentCategory;
use App\Models\Template;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
        $group = $event->model;
        $groupId = $group->id;

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
        if (!empty($group->category)) {
            $path = 'template/'.$group->category->tag . '/' . $group->tag;
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->deleteDirectory($path);
            }
        }
    }
}
