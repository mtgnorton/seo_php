<?php

namespace App\Listeners;

use App\Constants\ContentConstant;
use App\Constants\RedisCacheKeyConstant;
use App\Events\FileDeletingEvent;
use App\Events\PushFileDeletingEvent;
use App\Models\ContentCategory;
use App\Models\Diy;
use App\Services\ContentService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PushFileDeletingeventListener
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
    public function handle(PushFileDeletingEvent $event)
    {
        common_log('开始删除推送文件');
        $model = json_encode($event->model, JSON_UNESCAPED_UNICODE);

        $path = $event->model->name;
        if (empty($path)) {
            common_log('删除推送文件失败, 路径为空'.$model);
        }

        if (file_exists($path)) {
            $result = unlink($path);
            if ($result) {
                common_log('删除推送文件成功'.$model);
            } else {
                common_log('删除推送文件失败'.$model);
            }
        } else {
            common_log('删除推送文件失败, 文件不存在'.$model);
        }
    }
}
