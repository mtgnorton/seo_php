<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddTemplateMaterial;
use App\Models\Template;
use App\Models\TemplateMaterial;
use App\Services\CommonService;
use App\Services\TemplateService;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class TemplateMaterialController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'TemplateMaterial';

    public function __construct()
    {
        $this->title = ll('');
        // $this->icon = ('/asset/imgs/default_icon/10.png');
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
            <label class="title-word">查看素材<\/label>
        <\/div>`
$('.content_template_modules_default .box-header:nth-child(1)').append(h);
HTML;
        Admin::script($titleHeader);
        $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->breadcrumb(
                ['text' => lp('Template', 'List'), 'url' => '/templates'],
                ['text' => lp('Material', 'List')]
            )->body($this->grid());
        $content = $content->render();
        $content = str_replace('<section class="content">','<section class="content content_template_modules_default">',$content);
        return $content;
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        $page = TemplateMaterial::find($id);
        $templateId = $page->template_id;

        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->breadcrumb(
                ['text' => lp('Template', 'List'), 'url' => '/templates'],
                ['text' => lp('Material', 'List'), 'url' => '/template-materials?template_id'.$templateId],
                ['text' => lp('Material', 'Edit')]
            )->body($this->form()->edit($id));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TemplateMaterial());

        $templateId = request()->input('template_id', 0);
        if ($templateId) {
            $grid->model()->where('template_id', $templateId);
        }

        $grid->column('id', ll('Id'));
        $grid->column('template_id', ll('Template name'))->display(function () {
            return $this->template->name;
        });
        $grid->column('type', ll('Template material type'));
        $grid->column('file_name', ll('File name'));
        $grid->column('full_path', ll('File'))->display(function ($fullPath) {
            if (Storage::disk('public')->exists($fullPath)) {
                return Storage::disk('public')->url($fullPath);
            } else {
                return '';
            }
        })->downloadable();
        $grid->column('path', ll('Full path'))->display(function ($fullPath) {
            return '/storage/'.$this->full_path;
        });
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用新建
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function($filter) use ($templateId) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 分类
            $filter->equal('module_id', ll('Template module'))
                    ->select(TemplateService::moduleOptions($templateId));
            // 模板类型
            $filter->equal('type', ll('Template material type'))
                    ->select(TemplateService::MATERIAL_TYPE);
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
        });

        $grid->tools(function ($tools){
            $templateId = request()->input('template_id', 0);
            $groupId = Template::find($templateId)->group_id ?? 0;
            $templateUrl = '/admin/templates?group_id='.$groupId;
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template', 'List')));
            $moduleUrl = '/admin/template-modules?template_id='.$templateId;
            $tools->append(CommonService::getActionJumpUrl($moduleUrl, lp('Module', 'List')));
            $type = request()->input('type', 'other');
            $tools->append(new AddTemplateMaterial($templateId, $type));
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
        $show = new Show(TemplateMaterial::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('template_id', ll('Template name'));
        $show->field('type', ll('Material type'));
        $show->field('path', ll('Path'));
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
        $form = new Form(new TemplateMaterial());

        $params = request()->route()->parameters();
        $id = $params['template_material'] ?? 0;

        if ($form->isEditing()) {
            $content = TemplateService::getMaterialContent($id);

            $form->textarea('file_content', ll('File Content'))
                        ->default($content)
                        ->rows(45);
        }

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

            TemplateService::editMaterialFileContent($id, $content);
        });

        $form->saved(function (Form $form) use ($id) {
            // 配置定义跳转连接
            $material = TemplateService::materialInfo($id);

            $query = http_build_query([
                'type' => $material->type,
                'template_id' => $material->template_id
            ]);

            $url = '/admin/template-materials?' . $query;

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
            $page = TemplateMaterial::find($id);
            $url = "/admin/template-materials?template_id={$page->template_id}";

            // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
            $tools->add('<a href="'.$url.'" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>');
        });

        $form->ignore(['file_content']);

        return $form;
    }
}
