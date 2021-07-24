<?php

namespace App\Admin\Controllers;

use App\Models\TemplateType;
use App\Services\CommonService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TemplateTypeControlelr extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Template type');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TemplateType());

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Template type name'));
        $grid->column('tag', ll('Template type tag'));
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用创建
        $grid->disableCreateButton();
        // 禁用行操作列
        $grid->disableActions();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
        });

        $grid->tools(function ($tools){
            $templateUrl = '/admin/templates';
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template', 'List')));
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
        $show = new Show(TemplateType::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Template type name'));
        $show->field('tag', ll('Template type tag'));
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
        $form = new Form(new TemplateType());

        $form->text('name', ll('Template type name'))->required();
        $form->text('tag', ll('Template type tag'))->required();

        return $form;
    }
}
