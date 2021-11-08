<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddTemplateModule;
use App\Admin\Components\Actions\TemplateModuleFileUpload;
use App\Admin\Components\Actions\TemplateModulePage;
use App\Models\Template;
use App\Models\TemplateModule;
use App\Services\CommonService;
use App\Services\TemplateService;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\ContextMenuActions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class TemplateModuleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Template';

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
            <img class="title-icon" src="/asset/imgs/default_icon/28.png" class="default" alt="">
            <label class="title-word">查看模块<\/label>
        <\/div>`
$('.content_template_modules_default .box-header:nth-child(1)').append(h);
HTML;
        Admin::script($titleHeader);
        $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->breadcrumb(
                ['text' => '模板列表', 'url' => '/templates'],
                ['text' => '模块列表']
            )
            ->body($this->grid());
        $content = $content->render();
        $content = str_replace('<section class="content">','<section class="content content_template_modules_default">',$content);
        return $content;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TemplateModule());

        // 筛选不同模板的模块
        $templateId = request()->input('template_id');
        $parentId = request()->input('parent_id', 0);
        if ($templateId) {
            $grid->model()->where('template_id', $templateId);
        }
        $grid->model()->where('parent_id', $parentId);

        $grid->column('id', ll('Id'));
        $grid->column('template_id', ll('Template name'))->display(function () {
            return $this->template->name ?? '';
        });
        $grid->column('column_name', ll('Column name'));
        $grid->column('column_tag', ll('Column tag'));
        $grid->column('route_name', ll('Route name'));
        $grid->column('route_tag', ll('Path'));
        $grid->column('path', ll('Full path'));
        $grid->column('pages', ll('Module page'))->display(function () {
            $pageArr = $this->pages()->pluck('file_name')->toArray() ?? [];

            return implode(',', $pageArr) ?: '暂无页面';
        })->link(function () {
            return '/admin/template-module-pages?module_id='.$this->id;
        }, '__self');

        $grid->column('aaa', ll('Parent'))->display(function () {
            if (empty($this->parent)) {
                return '顶级';
            }
            if (empty($this->parent->column_name)) {
                return '未知';
            }
            
            return $this->parent->column_name ?? '未知';
        })->link(function () {
            return '/admin/template-modules?template_id='.$this->template_id.'&parent_id='.($this->parent->parent_id ?? 0);
        }, '__self');

        $grid->column('children', ll('Children'))->display(function () {
            if ($this->type == 'list') {
                $children = $this->children()->groupBy('column_name')->pluck('column_name')->toArray();
    
                $str = implode(',', $children) ?: '暂无子类';
                if (mb_strlen($str) > 40) {
                    $str = mb_substr($str, 0, 40) . '...';
                }

                return $str;
            }
        })->link(function () {
            return '/admin/template-modules?template_id='.$this->template_id.'&parent_id='.$this->id;
        }, '__self');
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用创建
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 路由名称
            $filter->like('column_name', ll('Column name'));
            // 路由标识
            $filter->like('column_tag', ll('Column tag'));
            // 路由名称
            $filter->like('route_name', ll('Route name'));
            // 路由标识
            $filter->like('route_tag', ll('Path'));
            // 模块
            // $filter->equal('template_id', ll('Template name'))
            //         ->select(TemplateService::templateOptions());
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            if ($actions->row->route_tag == '/' ||
                // $actions->row->route_tag == '/base/head/' ||
                // $actions->row->route_tag == '/base/foot/'
                $actions->row->route_name == '头部' ||
                $actions->row->route_name == '尾部'
            ) {
                // 如果是首页, 则禁止删除
                $actions->disableDelete();
            }
            // 去掉编辑
            $actions->disableEdit();
            $actions->add(new TemplateModuleFileUpload());
            $actions->add(new TemplateModulePage());
        });

        $grid->tools(function (Grid\Tools $tools) use ($templateId, $parentId) {
            $groupId = Template::find($templateId)->group_id ?? 0;
            $grandParentId = TemplateModule::find($parentId)->parent_id ?? 0;
            $parentUrl = '/admin/template-modules?template_id='.$templateId.'&parent_id='.$grandParentId;
            $tools->append(CommonService::getActionJumpUrl($parentUrl, lp('Parent', 'List')));
            $templateUrl = '/admin/templates?group_id='.$groupId;
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template', 'List')));
            $templateUrl = '/admin/template-materials?template_id='.$templateId;
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Material', 'List')));
            $tools->append(new AddTemplateModule($templateId, $parentId));
        });

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
        $show = new Show(TemplateModule::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('template_id', ll('Template id'));
        $show->field('route_name', ll('Route name'));
        $show->field('route_tag', ll('Path'));
        $show->field('path', ll('Full path'));
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
        $form = new Form(new TemplateModule());

        $form->number('template_id', ll('Template id'));
        $form->text('route_name', ll('Route name'));
        $form->text('route_tag', ll('Path'));
        $form->text('path', ll('Full path'));

        return $form;
    }
}
