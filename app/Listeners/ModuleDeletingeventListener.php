<?php

namespace App\Listeners;

use App\Events\ModuleDeletingEvent;
use App\Models\TemplateModulePage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class ModuleDeletingeventListener
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
    public function handle(ModuleDeletingEvent $event)
    {
        common_log('开始删除模块');
        $module = $event->model;

        try {
            // 1. 判断模块下面是否有页面, 有页面的话要删除
            $ids = $module->pages()->pluck('id');
            $count = count($ids);

            if ($count > 0) {
                TemplateModulePage::destroy($ids);
            }
            // 2. 更新模板中对应的模块
            $modules = $module->template->module;
            if (isset($modules[$module->route_tag])) {
                unset($modules[$module->route_tag]);
            }
            $module->template->module = $modules;
            $module->template->save();
            // 3. 如果存在子模块, 则将子模块也删除
            $children = $module->children;
            foreach ($children as $child) {
                $child->delete();
            }

            common_log('删除模块成功');
        } catch (Exception $e) {
            common_log('删除模块页面文件失败', $e);
        }
    }
}
