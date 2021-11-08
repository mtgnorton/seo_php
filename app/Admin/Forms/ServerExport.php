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

class ServerExport extends Base
{
    public function tabTitle()
    {
        return '服务器系统迁移';
    }

    public function handle(Request $request)
    {


        return back();
    }


    /**
     * Build a form here.
     */
    public function form()
    {


        $html = <<<HTML

var h = `<div class="form-group ">
    <label class="col-sm-2  control-label">点击下载<\/label>
    <div class="col-sm-8">
        <div class="box box-default box_default_system no-margin">

            <div class="box-body">
            <label  class="btn btn-info download" href="/admin/server-export" target="_blank">下载seo_php.tar.gz</label>
            <\/div>
        <\/div>
    <\/div>
<\/div>`

$('.fields-group').append(h)

 $('.download').click(function(){
    window.open('/admin/server-export')
 })
HTML;


        Admin::script($html);

        $this->disableReset();

        $this->disableSubmit();


    }
}
