<?php

namespace App\Listeners;

use App\Events\MaterialDeletingEvent;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class MaterialDeletingeventListener
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
    public function handle(MaterialDeletingEvent $event)
    {
        common_log('开始删除素材');
        $material = $event->model;

        // 页面删除后, 对应文件也要删除
        $path = $material->full_path;

        try {
            if (Storage::disk('public')->exists($path)) {
                $result = Storage::disk('public')->delete($path);

                common_log('文件删除结果: '.$result.', 文件路径: '.$path);
            }
        } catch (Exception $e) {
            common_log('文件删除失败, 文件路径'.$path, $e);
        }
    }
}
