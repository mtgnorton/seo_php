<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\ImportText;
use App\Models\Video;
use App\Services\ContentService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;

class VideoController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Video');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Video());

        $grid->column('id', ll('Id'))->sortable();
        $grid->column('url', ll('Url'))->display(function($url) {
            return htmlspecialchars(Str::limit($url, 30));
        });
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

            // 是否是采集
            $filter->equal('is_collected', ll('Is collected'))->select([
                '0' => '不是',
                '1' => '是'
            ]);
            // 分类
            $filter->equal('category_id', ll('Category name'))
                    ->select(ContentService::categoryOptions(['type' => 'video']));
        });

        // 添加导入
        $grid->tools(function ($tools) {
            $tools->append(new ImportText(
                'video',
                '文本格式: 一个视频链接一行 注: 请确保链接的有效性, 以http://或https://开头'
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
        $show = new Show(Video::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('url', ll('Url'));
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
        $form = new Form(new Video());

        $form->url('url', ll('Url'))
            ->rules('required', [
                'required' => ll('Url cannot be empty')
            ]);
        $form->select('category_id', ll('Category name'))
                ->options(ContentService::categoryOptions(['type' => 'video']))
                ->rules('required', [
                    'required' => ll('Category cannot be empty')
                ]);

        $form->hidden('tag');

        $form->saving(function (Form $form) {
            // 将标签写入
            $tag = ContentService::contentTag($form->category_id, 'video');
            $form->tag = $tag;
        });

        return $form;
    }
}
