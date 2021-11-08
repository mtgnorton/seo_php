<?php

namespace App\Admin\Forms;

use App\Admin\Components\Renders\DynamicOutput;
use App\Constants\SpiderConstant;
use App\Models\Config;
use App\Services\Gather\CrawlService;
use App\Services\ImportAndExportService;
use App\Services\SystemUpdateService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class PushFile extends Base
{
    public function tabTitle()
    {
        return '上传文件';
    }

    public function handle(Request $request)
    {
        
    }


    /**
     * Build a form here.
     */
    public function form()
    {

        Admin::style('
        .pull-left{display:none;}
        ');

        DynamicOutput::modalRender("上传", '点击上传', '推送文件上传', '/admin/push-files-upload');
    }
}
