<?php

namespace App\Admin\Controllers;

use App\Models\TemplateExport;
use App\Services\CommonService;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class TemplateExportController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "模板导出";

    public function llconstruct()
    {
        $this->title = ll('Template export');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TemplateExport());

        $grid->column('id', ll('Id'));
        $grid->column('template_id', ll('Template id'));
        $grid->column('name', ll('Name'));
        $grid->column('tag', ll('Tag'));
        $grid->column('path', ll('Path'))->display(function ($path) {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            } else {
                return '';
            }
        })->downloadable();
        $grid->column('message', ll('Message'));
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            // 去掉编辑
            $actions->disableEdit();
            // 去掉查看
            $actions->disableView();
        });
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 名称
            $filter->like('name', ll('Template name'));
            // 标签
            $filter->like('tag', ll('Template tag'));
            // 分类
            $filter->like('template_id', ll('Template id'));
        });

        $grid->tools(function ($tools) {
            $groupId = request()->input('group_id', 0);
            if (!empty($groupId)) {
                $templateUrl = '/admin/templates?group_id='.$groupId;
                $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template', 'List')));
            }
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
        $show = new Show(TemplateExport::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('template_id', ll('Template id'));
        $show->field('name', ll('Name'));
        $show->field('tag', ll('Tag'));
        $show->field('path', ll('Path'));
        $show->field('message', ll('Message'));
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
        $form = new Form(new TemplateExport());

        $form->number('template_id', ll('Template id'));
        $form->text('name', ll('Name'));
        $form->text('tag', ll('Tag'));
        $form->text('path', ll('Path'));
        $form->text('message', ll('Message'));

        return $form;
    }
}
