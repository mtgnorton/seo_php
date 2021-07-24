<?php

namespace App\Listeners;

use App\Events\FileDeletingEvent;
use App\Models\Diy;
use App\Services\ContentService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
        $model = $event->model;
        $type = $model->getOriginal('type');
        $path = $model->path;

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

                $contentModel::where('file_id', $model->id)->delete();
            }
        } catch (Exception $e) {
            common_log('文件内容删除失败, 文件ID为: '.$model->id, $e);
        }
    }
}
