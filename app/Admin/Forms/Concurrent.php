<?php

namespace App\Admin\Forms;

use App\Admin\Components\Renders\DynamicOutput;
use App\Constants\SpiderConstant;
use App\Models\Config;
use App\Services\Gather\CrawlService;
use App\Services\ImportAndExportService;
use App\Services\NginxRequestLimitService;
use App\Services\SystemUpdateService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class Concurrent extends Base
{
    public function tabTitle()
    {
        return '并发配置';
    }

    public function handle(Request $request)
    {

        $amount = $request->get('number', 0);


        if ($amount) {
            NginxRequestLimitService::setConcurrent($amount);
        }
        admin_success('设置成功');
        return back();
//        ImportAndExportService::import($request->file('import'));
//        admin_success(ll(sprintf('更新成功,共更新%s个文件,共更新%s个sql', 1, 1)));
//        return back();
    }

    /**
     * Build a form here.
     */
    public function form()
    {


        Admin::style('
        #number{height:40px;}

       .form-group:first-child .col-sm-8{
            margin-top:20px;
        }
        ');


        // $this->html('服务器性能测试主要针对于cpu,为取得良好的并发性能,cpu建议2核以上,内存建议4GB内存以上,带宽建议10Mbit/s以上,硬盘40GB以上,购买服务器时建议选择阿里云、腾讯云等主流平台,淘宝或其他小平台可能会出现cpu和带宽实际性能差等情况', '硬件建议配置');

        DynamicOutput::modalRender("服务器性能测试", '点击测试', '服务器性能测试', '/admin/cpu-benchmark', 1);

        $this->number('number', '当前每秒并发数量限制')->help('建议使用推荐并发数量,如果感觉系统卡顿,可以下调并发数量,如果想要提高并发数量,可以上调该值,调整该值时仅适合微调,大幅度向上调整可能导致系统崩溃,调整该值提交后会重启nginx')->default(function () {
            return NginxRequestLimitService::getConcurrent();
        })->rules('min:1');

    }
}
