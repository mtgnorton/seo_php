<?php

namespace App\Listeners;

use App\Events\PageDeletingEvent;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PageDeletingeventListener
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
    public function handle(PageDeletingEvent $event)
    {
        $page = $event->model;

        // 页面删除后, 对应文件也要删除
        $path = $page->full_path;

        try {
            // 清除对应缓存
            $key = 'public' . $path;
            Cache::forget($key);
            
            if (Storage::disk('public')->exists($path)) {
                $result = Storage::disk('public')->delete($path);

                common_log('文件删除结果: '.$result.', 文件路径: '.$path);
            }
        } catch (Exception $e) {
            common_log('文件删除失败, 文件路径'.$path, $e);
        }
    }
}
