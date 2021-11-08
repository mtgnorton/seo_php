<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddTemplateGroup;
use App\Admin\Components\Actions\AddTemplateWebsiteRow;
use App\Admin\Components\Actions\DeleteCategory;
use App\Admin\Components\Actions\Template;
use App\Admin\Components\Actions\TemplateSiteConfig;
use App\Models\TemplateGroup;
use App\Services\CategoryService;
use App\Services\ConfigService;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TempalteGroupController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Template group');
        $this->icon = ('');
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $grid = new Grid(new TemplateGroup());
        $categoryId = request()->input('category_id', 0);

        if (!empty($categoryId)) {
            $grid->model()->where('category_id', $categoryId);
        }

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Tempate group name'));
        $grid->column('tag', ll('Tag'));
        $grid->column('category_id', ll('Category'))->display(function () {
            return $this->category->name;
        });
        $grid->column('tempaltes', ll('Template'))->display(function () {
            $templates = $this->templates()
                        ->pluck('name')->toArray() ?? [];
                        
            $str =  implode(',', $templates);
            if (mb_strlen($str) > 40) {
                $str = mb_substr($str, 0, 40) . '...';
            }

            return $str ?: '暂无模板';
        })->link(function () {
            return '/admin/templates?group_id='.$this->id;
        }, '__self');

        $grid->column('websites', ll('Bind host'))->display(function () {
            $modules = $this->websites()
                        ->pluck('url')->toArray() ?? [];
                        
            $str =  implode(',', $modules);
            if (mb_strlen($str) > 40) {
                $str = mb_substr($str, 0, 40) . '...';
            }

            return $str ?: '暂无域名';
        })->link(function () {
            return '/admin/website-templates?group_id='.$this->id;
        }, '__self');

        $grid->column('link', ll('Website config'))->display(function () {
            return ll('Website config');
        })->link(function () {
            return '/admin/sites?group_id=' . $this->id . '&category_id='.$this->category_id;
        }, '__self');
        
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        // 禁用导出
        $grid->disableExport();
        // 禁用新增
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 名称
            $filter->like('name', ll('Template name'));
            // 标签
            $filter->like('tag', ll('Template tag'));
        });

        $grid->tools(function ($tools) use ($categoryId) {
            $tools->append(new AddTemplateGroup($categoryId));
            $tools->append(new DeleteCategory($categoryId));
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            // 去掉编辑
            $actions->disableEdit();
            $actions->add(new Template($actions->row->id));
            $actions->add(new AddTemplateWebsiteRow());
            $actions->add(new TemplateSiteConfig());
        });
        $grid = $grid->render();
        $grid = str_replace('<section class="content">','<section class="content content_template_modules_default">',$grid);

        $js = <<<EOT
        var h = `<section class="tip_modal load_body">
            <div class="tip_content"> 
                <div class="cloud"><\/div>
                正在操作中... 
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

        Admin::script($js);
        
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(TemplateGroup::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Tempate group name'));
        $show->field('tag', ll('Tag'));
        $show->field('category_id', ll('Category'));
        $show->field('created_at', ll('Created at'));
        $show->field('updated_at', ll('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TemplateGroup());

        $form->text('name', ll('Name'));
        $form->text('tag', ll('Tag'));
        $form->select('category_id', ll('Category'))
            ->options(CategoryService::categoryOptions())
            ->rules('required', [
                'required' => lp('Category', 'Cannot be empty')
            ]);

        $form->saved(function (Form $form) {
            if ($form->isCreating()) {
                // // 添加默认配置
                $group = $form->model();
                ConfigService::addDefaultAd($group);
                ConfigService::addDefaultCache($group);
                ConfigService::addDefaultSite($group);
            }
        });

        return $form;
    }
}
