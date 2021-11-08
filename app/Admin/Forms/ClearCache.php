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

class ClearCache extends Base
{
    public function tabTitle()
    {
        return '清空缓存';
    }

    public function handle(Request $request)
    {

        ImportAndExportService::import($request->file('import'));
        admin_success(ll(sprintf('更新成功,共更新%s个文件,共更新%s个sql', 1, 1)));
        return back();
    }


    /**
     * Build a form here.
     */
    public function form()
    {
        Admin::style('
            .pull-left{display:none;}
        ');

$jsCode = <<<JS
$(function () {
    $("input[name='_token']").after("<p style='color:blue;font-size: 20px;margin-top:20px;margin-left:25px;'>若想单独删除站点的内页、栏目、内页等缓存 请到每个站点配置缓存处删除</p>");
    $("input[name='_token']").after("<input style='margin:5px 30px;' type='button' class='btn btn-danger' id='clear_all' value='立即清除'>");
    var h = `<section class="tip_modal load_body">
        <div class="tip_content"> 
            <div class="cloud"><\/div>
            缓存清空中... 
        <\/div>
    <\/section>`
    $(".box-body.fields-group").append(h);

    $('#clear_all').click(function (item) {
        $('.tip_modal').show();
        $.ajax({
            url: '/admin/clear-all',
            method: 'get',
            dataType: 'json'
        }).done(function (data) {
            if (data.code == 0) {
                swal('清空缓存成功', '', 'success');
            } else {
                swal('清空缓存失败', '', 'error');
            }
        }).fail(function (xhr) {
            swal('清空缓存失败', '', 'error');
        }).always(function () {
            $('.tip_modal').hide();
        });
    });
})
JS;
    Admin::script($jsCode);
    Admin::style('
            .load_body{
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, .3);
                z-index: 9999;
                display: none;
            }
            .tip_content{
                width: 200px;
                height: 100px;
                margin: 100px auto;
                border-radius: 10px;
                background-color: rgba(0,0,0, .4);
                color: #fff;
                text-align:center;
                padding-top: 1px;
            }
            .cloud{
                margin: 20px 80px;
                width: 4px;
                height: 10px;
                opacity: 0.5;
                position: relative;
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,1),
                            18px 0px 0px 0px rgba(255,255,255,1),
                            24px 0px 0px 0px rgba(255,255,255,1),
                            30px 0px 0px 0px rgba(255,255,255,1),
                            36px 0px 0px 0px rgba(255,255,255,1);
                
                -webkit-animation: rain 1s linear infinite alternate;
                   -moz-animation: rain 1s linear infinite alternate;
                        animation: rain 1s linear infinite alternate;
            }
            .cloud:after{
                width: 40px;
                height: 10px;
                position: absolute;
                content: "";
                background-color: rgba(255,255,255,1);
                top: 0px;
                opacity: 1;
                -webkit-animation: line_flow 2s linear infinite reverse;
                   -moz-animation: line_flow 2s linear infinite reverse;
                        animation: line_flow 2s linear infinite reverse;
            }
            
            @-webkit-keyframes rain{
                0%{
                 box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,0.9),
                            18px 0px 0px 0px rgba(255,255,255,0.7),
                            24px 0px 0px 0px rgba(255,255,255,0.6),
                            30px 0px 0px 0px rgba(255,255,255,0.3),
                            36px 0px 0px 0px rgba(255,255,255,0.2);
                }
                100%{
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,0.2),
                            12px 0px 0px 0px rgba(255,255,255,0.3),
                            18px 0px 0px 0px rgba(255,255,255,0.6),
                            24px 0px 0px 0px rgba(255,255,255,0.7),
                            30px 0px 0px 0px rgba(255,255,255,0.9),
                            36px 0px 0px 0px rgba(255,255,255,1);
                    opacity: 1;
                }
            }
            @-moz-keyframes rain{
                0%{
                 box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,0.9),
                            18px 0px 0px 0px rgba(255,255,255,0.7),
                            24px 0px 0px 0px rgba(255,255,255,0.6),
                            30px 0px 0px 0px rgba(255,255,255,0.3),
                            36px 0px 0px 0px rgba(255,255,255,0.2);
                }
                100%{
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,0.2),
                            12px 0px 0px 0px rgba(255,255,255,0.3),
                            18px 0px 0px 0px rgba(255,255,255,0.6),
                            24px 0px 0px 0px rgba(255,255,255,0.7),
                            30px 0px 0px 0px rgba(255,255,255,0.9),
                            36px 0px 0px 0px rgba(255,255,255,1);
                    opacity: 1;
                }
            }
            @keyframes rain{
                0%{
                 box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,0.9),
                            18px 0px 0px 0px rgba(255,255,255,0.7),
                            24px 0px 0px 0px rgba(255,255,255,0.6),
                            30px 0px 0px 0px rgba(255,255,255,0.3),
                            36px 0px 0px 0px rgba(255,255,255,0.2);
                }
                100%{
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,0.2),
                            12px 0px 0px 0px rgba(255,255,255,0.3),
                            18px 0px 0px 0px rgba(255,255,255,0.6),
                            24px 0px 0px 0px rgba(255,255,255,0.7),
                            30px 0px 0px 0px rgba(255,255,255,0.9),
                            36px 0px 0px 0px rgba(255,255,255,1);
                    opacity: 1;
                }
            }
            
            @-webkit-keyframes line_flow{
                0%{ width: 0px;}
                100%{width: 40px;}
            }
            @-moz-keyframes line_flow{
                0%{ width: 0px;}
                100%{width: 40px;}
            }
            @keyframes line_flow{
                0%{ width: 0px;}
                100%{width: 40px;}
            }
            
        ');
    }
}
