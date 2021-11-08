<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\PushFile as AppPushFile;
use App\Models\PushFile;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PushFileController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '推送文件上传';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PushFile());

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Name'));
        $grid->column('path', ll('Path'))->display(function ($path) {
            $host = request()->getSchemeAndHttpHost();
            
            return $host.$path;
        })->downloadable();
        $grid->column('content', ll('Content'))->display(function ($content) {
            if (mb_strlen($content) >= 40) {
                return mb_substr($content, 0, 40) . '...';
            }
            
            return $content;
        });
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用查询过滤器
        $grid->disableFilter();
        // 禁用新增
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 名称
            $filter->like('name', ll('Name'));
        });

        $grid->tools(function ($tools) {
            $tools->append(new AppPushFile());
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            $actions->disableEdit();
        });
        $js = <<<EOT
        var h = `<section class="tip_modal load_body">
            <div class="tip_content"> 
                <div class="cloud"><\/div>
                文件上传中... 
            <\/div>
        <\/section>`
        $(".box.grid-box").append(h);
        $(".btn.btn-primary").click(function(){
            let timer = setTimeout(()=>{
                if(timer){
                    clearTimeout(timer)
                };
                window.location.reload();
            }, 600*1000);
            $('.tip_modal').show();
        });
EOT;

        Admin::script($js);
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

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PushFile());

        $form->text('name', ll('Name'));
        $form->text('path', ll('Path'));

        return $form;
    }
}
