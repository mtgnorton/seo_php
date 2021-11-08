<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddTemplateModulePage;
use App\Models\TemplateModule;
use App\Models\TemplateModulePage;
use App\Services\CommonService;
use App\Services\TemplateService;


use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TemplateModulePageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('');
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        $titleHeader = <<<HTML
        var h = `<div class="conmon-icon-title">
            <label class="title-word">模块页面<\/label>
        <\/div>`
        $('.content_template_modules_default .box-header:nth-child(1)').append(h);
HTML;
        Admin::script($titleHeader);
        $templateId = request()->input('template_id');

        $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->breadcrumb(
                ['text' => lp('Template', 'List'), 'url' => '/templates'],
                ['text' => lp('Module', 'List'), 'url' => '/template-modules?template_id=' . $templateId],
                ['text' => lp('Module', 'Page', 'List')]
            )->body($this->grid());

        $content = $content->render();
        $content = str_replace('<section class="content">','<section class="content content_template_modules_default">',$content);
        return $content;
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        $page       = TemplateModulePage::find($id);
        $templateId = $page->template_id;
        $moduleId   = $page->module_id;

        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->breadcrumb(
                ['text' => lp('Template', 'List'), 'url' => '/templates'],
                ['text' => lp('Module', 'List'), 'url' => "/template-modules?template_id={$templateId}"],
                ['text' => lp('Module', 'Page', 'List'), 'url' => "/template-modules??template_id={$templateId}&module_id={$moduleId}"],
                ['text' => lp('Page', 'Edit')]
            )->body($this->form()->edit($id));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TemplateModulePage());

        // 模板筛选
        $moduleId = request()->input('module_id', 0);

        if (!empty($moduleId)) {
            $grid->model()->where('module_id', $moduleId);
        }

        $grid->column('id', ll('Id'));
        $grid->column('template_id', ll('Template name'))->display(function () {
            return $this->template->name ?? '';
        });
        $grid->column('module_id', ll('Module name'))->display(function () {
            return $this->module->route_name ?? '';
        });
        $grid->column('file_name', ll('File name'))->link(function () {
            return "/admin/template-module-pages/".$this->id."/edit";
        }, "__self");
        $grid->column('full_path', ll('File'))->display(function ($fullPath) {
            if (Storage::disk('public')->exists($fullPath)) {
                return Storage::disk('public')->url($fullPath);
            } else {
                return '';
            }
        })->downloadable();
        $grid->column('path', ll('Full path'))->display(function ($fullPath) {
            return $this->full_path;
        });
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用创建
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 名称
            $filter->like('name', ll('Template name'));
            // 标签
            $filter->like('tag', ll('Template tag'));
            // 模板
            // $filter->equal('template_id', ll('Template name'))
            //     ->select(TemplateService::templateOptions())
            //     ->load('module_id', '/admin/template-module-pages/module');

            // // 模块
            // $filter->equal('module_id', ll('Module'))
            //     ->select();
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
        });

        $grid->tools(function (Grid\Tools $tools) use ($moduleId) {
            $module = TemplateModule::find($moduleId);
            $templateId = $module->template_id;
            $groupId = $module->template->group_id ?? 0;
            $parentId = $module->parent_id ?? 0;
            if ($templateId != 0) {
                $moduleUrl = '/admin/template-modules?template_id=' . $templateId . '&parent_id='.$parentId;
            } else {
                $moduleUrl = '/admin/template-modules&parent_id='.$parentId;
            }
            $templateUrl = '/admin/templates?group_id='.$groupId;
            $tools->append(CommonService::getActionJumpUrl($moduleUrl, lp('Module', 'List')));
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template', 'List')));
            $tools->append(new AddTemplateModulePage($moduleId));
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
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(TemplateModulePage::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('template_id', ll('Template name'));
        $show->field('module_id', ll('Module name'));
        $show->field('file_name', ll('File name'));
        $show->field('full_path', ll('Full path'));
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
        $form = new Form(new TemplateModulePage());

        $params = request()->route()->parameters();
        $id     = $params['template_module_page'] ?? 0;

        $content = TemplateService::getPageFileContent($id);


        if (stripos($content, 'charset="GBK"') !== false || stripos($content, 'charset="gb2312"') !== false) {
            $content = htmlspecialchars($content, ENT_NOQUOTES, 'ISO-8859-1');
        } else {
            $content = htmlspecialchars($content, ENT_NOQUOTES);

        }
        $form->textareaHtml('file_content', lp('File', 'Content'))
            ->default($content)
            ->rows(45);


        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });


        $form->saving(function (Form $form) use ($id) {
            // 更新文件内容
            $content = request()->input('file_content');

            TemplateService::editPageFileContent($id, $content);
        });

        $form->saved(function (Form $form) use ($id) {
            // 配置定义跳转连接
            $page = TemplateService::pageInfo($id);
            // 清空对应缓存
            // $key = 'public'.($page->full_path ?? '');
            // Cache::forget($key);

            $query = http_build_query([
                'module_id'   => $page->module_id,
                'template_id' => $page->template_id
            ]);

            $url = '/admin/template-module-pages?' . $query;

            return redirect($url);
        });

        $form->tools(function (Form\Tools $tools) use ($id) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();

            // 获取模板页面对象
            $page = TemplateModulePage::find($id);
            $url  = "/admin/template-module-pages?module_id={$page->module_id}&template_id={$page->template_id}";

            // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
            $tools->add('<a href="' . $url . '" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>');
        });

        $form->ignore(['file_content']);

        return $form;
    }

    /**
     * get template options api
     *
     * @return void
     */
    public function module()
    {
        $templateId = request()->input('q');

        return TemplateService::moduleChainOptions($templateId);
    }
}
