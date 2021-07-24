<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\ImportText;
use App\Models\Title;
use App\Services\ContentService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TitleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Title');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Title());

        $grid->column('id', ll('Id'))->sortable();
        $grid->column('content', ll('Title'));
        $grid->column('category_id', ll('Category name'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('tag', ll('Tag name'));
        $grid->column('file_id', ll('File name'))->display(function () {
            return $this->file->path ?? '';
        })->downloadable();
        $grid->column('is_collected', ll('Is collected'))->display(function ($isCollected) {
            return $isCollected == 1 ? '是' : '不是';
        });
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        // 禁用导出
        $grid->disableExport();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 标题搜索
            $filter->like('content', ll('Title'));
            // 是否是采集
            $filter->equal('is_collected', ll('Is collected'))->select([
                '0' => '不是',
                '1' => '是'
            ]);
            // 分类
            $filter->equal('category_id', ll('Category name'))
                    ->select(ContentService::categoryOptions(['type' => 'title']));
        });

        // 添加导入
        $grid->tools(function ($tools) {
            $tools->append(new ImportText(
                'title',
                '文本格式: 一个标题一行'
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
        $show = new Show(Title::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('content', ll('Title'));
        $show->field('category_id', ll('Category name'))->as(function () {
            return $this->category->name ?? '';
        });
        $show->field('file_id', ll('File name'))->as(function () {
            return $this->file->name ?? '';
        });
        $show->field('is_collected', ll('Is collected'))->as(function ($isCollected) {
            return $isCollected == 1 ? '是' : '不是';
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
        $form = new Form(new Title());

        $form->text('content', ll('Title'))
            ->rules('required', [
                'required' => ll('Title cannot be empty')
            ]);
        $form->select('category_id', ll('Category name'))
                ->options(ContentService::categoryOptions(['type' => 'title']))
                ->rules('required', [
                    'required' => ll('Category cannot be empty')
                ]);
        $form->hidden('tag');

        $form->saving(function (Form $form) {
            // 将标签写入
            $tag = ContentService::contentTag($form->category_id, 'title');
            $form->tag = $tag;
        });

        return $form;
    }
}
