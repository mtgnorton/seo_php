<?php

namespace App\Admin\Controllers;

use App\Models\Tag;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TagController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Tag');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Tag());

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Tag name'));
        $grid->column('identify', ll('Tag identify'));
        $grid->column('tag', ll('Tag'))->display(function ($value) {
            return '{'.$value.'}';
        });
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
            // 标题搜索
            $filter->like('name', ll('Tag name'));
        });

        // 方法
        $grid->actions(function ($actions) {
            // 去掉删除
            $actions->disableDelete();
            // 去掉编辑
            $actions->disableEdit();
            // 去掉查看
            $actions->disableView();
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
        $show = new Show(Tag::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Tag name'));
        $show->field('identify', ll('Tag identify'));
        $show->field('tag', ll('Tag'));
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
        $form = new Form(new Tag());

        $form->text('name', ll('Tag name'))
            ->rules('required', [
                'required' => ll('Tag cannot be empty')
            ]);
        $form->text('identify', ll('Tag identify'))
            ->help(ll('English or pinyin'))
            ->rules('required', [
                'required' => ll('Tag identify cannot be empty')
            ]);
        $form->text('tag', ll('Tag'))
            ->help(ll('Need not {}'))
            ->creationRules(["required", "unique:tags"], [
                'unique' => ll('Tag name unique'),
                'required' => ll('Tag name cannot be empty'),
            ])
            ->updateRules(["required", "unique:tags,name,{{id}}"], [
                'unique' => ll('Tag name unique'),
                'required' => ll('Tag name cannot be empty'),
            ]);

        return $form;
    }
}
