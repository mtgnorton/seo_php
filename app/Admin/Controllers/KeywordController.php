<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\ImportText;
use App\Models\Keyword;
use App\Services\ContentService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class KeywordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Keyword';

    public function __construct()
    {
        $this->title = ll('Keyword');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Keyword());

        $grid->column('id', ll('Id'));
        $grid->column('content', ll('Keyword'));
        $grid->column('tag', ll('Tag name'));
        $grid->column('category_id', ll('Category name'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('file_id', ll('File id'))->display(function () {
            return $this->file->path ?? '';
        })->downloadable();
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        // 禁用导出
        $grid->disableExport();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 标题搜索
            $filter->like('content', ll('Keyword'));
            // 分类
            $filter->equal('category_id', ll('Category name'))
                    ->select(ContentService::categoryOptions(['type' => 'keyword']));
        });

        // 添加导入
        $grid->tools(function ($tools) {
            $tools->append(new ImportText(
                'keyword',
                '文本格式: 一个关键词一行'
            ));
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
        $show = new Show(Keyword::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('content', ll('Keyword'));
        $show->field('tag', ll('Tag name'));
        $show->field('category_id', ll('Category name'))->as(function () {
            return $this->category->name ?? '';
        });
        $show->field('file_id', ll('File id'))->as(function () {
            return $this->file->name ?? '';
        });
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
        $form = new Form(new Keyword());

        $form->text('content', ll('Keyword'))
            ->rules('required', [
                'required' => ll('Title cannot be empty')
            ]);
        $form->select('category_id', ll('Category name'))
            ->options(ContentService::categoryOptions(['type' => 'keyword']))
            ->rules('required', [
                'required' => ll('Category cannot be empty')
            ]);
        $form->hidden('tag');

        $form->saving(function (Form $form) {
            // 将标签写入
            $tag = ContentService::contentTag($form->category_id, 'keyword');
            $form->tag = $tag;
        });

        return $form;
    }
}
