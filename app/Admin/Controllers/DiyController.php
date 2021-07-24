<?php

namespace App\Admin\Controllers;

use App\Models\Diy;
use App\Services\ContentService;

use Encore\Admin\Form;
use Encore\Admin\Grid;

class DiyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Diy');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Diy());

        $grid->column('id', ll('Id'));
        $grid->column('content', ll('Content'));
        $grid->column('tag_id', ll('Tag name'))->display(function () {
            return $this->tag->name ?? '';
        });
        // $grid->column('tag_content', ll('Tag'))->display(function () {
        //     return $this->tag->tag ?  '{'.$this->tag->tag.'}' : '';
        // });
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
        // 禁用查询
        $grid->disableFilter();
        // 查询
        // $grid->filter(function($filter){
        //     // 去掉默认的id过滤器
        //     $filter->disableIdFilter();
        //     // 标题搜索
        //     $filter->like('content', ll('Content'));
        //     // 标签
        //     $filter->equal('tag_id', ll('Tag name'))
        //             ->select(ContentService::tagOptions());
        // });

        // 添加导入
        // $grid->tools(function ($tools) {
        //     $tools->append(new ImportText(
        //         'diy',
        //         '文本格式: 一行一个'
        //     ));
        // });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Diy());

        $form->text('content', ll('Content'))
            ->rules('required', [
                'required' => ll('Content cannot be empty')
            ]);
        $form->select('tag_id', ll('Tag name'))
            ->options(ContentService::tagOptions())
            ->rules('required', [
                'required' => ll('Tag cannot be empty')
            ]);

        return $form;
    }
}
