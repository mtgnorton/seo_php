<?php

namespace App\Admin\Controllers;

use App\Constants\SpiderConstant;
use App\Models\SpiderAttractionRecord;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SpiderAttractionRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SpiderAttractionRecord';

    public function __construct()
    {
        $this->title = ll('Spider attraction record');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SpiderAttractionRecord());

        $grid->column('id', ll('Id'));
        $grid->column('type', ll('Type'))->display(function ($type) {
            $typeData = SpiderConstant::typeText();

            return $typeData[$type] ?? '';
        });
        $grid->column('ip', ll('Ip'));
        $grid->column('from_host', ll('From host'));
        $grid->column('from_url', ll('From url'))->link();
        $grid->column('to_url', ll('To url'));
        $grid->column('url_type', lp('Url', 'Type'));
        $grid->column('category_id', ll('Category name'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('group_id', lp('Template group name'))->display(function () {
            return $this->group->name ?? '';
        });
        $grid->column('template_id', ll('Template name'))->display(function () {
            return $this->template->name ?? '';
        });
        $grid->column('status_code', ll('Status code'));
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
            // 标题搜索
            // $filter->date('created_at', ll('Created at'));
            $filter->between('created_at', ll('Created at'))->datetime();
            $filter->equal('type', ll('Type'))->radio(SpiderConstant::typeText())
                    ->default('');
            $filter->like('from_host', ll('From host'));
            $filter->like('to_host', ll('To url'));
        });

        $grid->selector(function (Grid\Tools\Selector $selector) {
            // 类型规格选择器
            $selector->select(
                'type',
                ll('Template type'),
                SpiderConstant::typeText()
            );
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
        $show = new Show(SpiderAttractionRecord::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('type', ll('Type'));
        $show->field('user_agent_id', ll('User agent id'));
        $show->field('ip', ll('Ip'));
        $show->field('from_host', ll('From host'));
        $show->field('from_url', ll('From url'));
        $show->field('to_url', ll('To url'));
        $show->field('url_type', ll('Url type'));
        $show->field('category_id', ll('Category id'));
        $show->field('group_id', ll('Group id'));
        $show->field('template_id', ll('Template id'));
        $show->field('status_code', ll('Status code'));
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
        $form = new Form(new SpiderAttractionRecord());

        $form->text('type', ll('Type'))->default('other');
        $form->number('user_agent_id', ll('User agent id'));
        $form->ip('ip', ll('Ip'));
        $form->text('from_host', ll('From host'));
        $form->text('from_url', ll('From url'));
        $form->text('to_url', ll('To url'));
        $form->text('url_type', ll('Url type'));
        $form->number('category_id', ll('Category id'));
        $form->number('group_id', ll('Group id'));
        $form->number('template_id', ll('Template id'));
        $form->number('status_code', ll('Status code'));

        return $form;
    }
}
