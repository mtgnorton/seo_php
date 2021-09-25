<?php

namespace App\Listeners;

use App\Constants\ContentConstant;
use App\Constants\RedisCacheKeyConstant;
use App\Events\FileDeletingEvent;
use App\Events\TemplateExportDeletingEvent;
use App\Models\ContentCategory;
use App\Models\Diy;
use App\Services\ContentService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TemplateExportDeletingeventListener
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
    public function handle(TemplateExportDeletingEvent $event)
    {
        $model = $event->model;

        // 页面删除后, 对应文件也要删除
        $id = $model->id;
        $path = $model->path;

        try {
            if (Storage::disk('public')->exists($path)) {
                $result = Storage::disk('public')->delete($path);

                common_log('导出模板文件: '.$id.',删除结果: '.$result.', 导出模板文件路径: '.$path);
            } else {
                common_log('导出模板文件: '.$id.',删除失败, 导出模板文件不存在, 导出模板文件路径: '.$path);
            }
        } catch (Exception $e) {
            common_log('导出模板文件: '.$id.',删除失败, 导出模板文件路径'.$path, $e);
        }
    }
}
