<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\ImportText;
use App\Models\Article;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use App\Services\ContentService;

class ArticleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Article');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Article());

        $grid->column('id', ll('Id'))->sortable();
        $grid->column('title', ll('Title'));
        $grid->column('image', ll('Image'))->gallery(['width' => 100, 'height' => 100]);
        $grid->column('content', ll('Content'))->display(function($content) {
            return htmlspecialchars(Str::limit($content, 30));
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

            // 标题搜索
            $filter->like('title', ll('Title'));
            // 是否是采集
            $filter->equal('is_collected', ll('Is collected'))->select([
                '0' => '不是',
                '1' => '是'
            ]);
            // 分类
            $filter->equal('category_id', ll('Category id'))
                    ->select(ContentService::categoryOptions(['type' => 'article']));
        });

        // 添加导入
        $grid->tools(function ($tools) {
            $tools->append(new ImportText(
                'article',
                '文本格式: title******image******content, 一行一条'
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
        $show = new Show(Article::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('title', ll('Title'));
        $show->field('image', ll('Image'))->image('', 1000, 1000);
        $show->field('content', ll('Content'));
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
        $form = new Form(new Article());

        $form->text('title', ll('Title'))
            ->rules('required', [
                'required' => ll('Title cannot be empty')
            ]);

        $imageType = ContentService::IMAGE_TYPE;
        $form->radioButton('imageType', ll('Image'))
            ->options($imageType)
            ->when('local', function (Form $form) {
                $form->image('image', ll('Image'))
                    ->uniqueName()
                    ->move('images/'.date('Y/m/d'));
            })->when('link', function (Form $form) {
                $form->url('imageUrl', ll('Image'))
                    ->help(ll('Url need http or https'));
            })->default('local');

        $form->fullEditor('content', ll('Content'))
            ->rules('required', [
                'required' => ll('Content cannot be empty')
            ]);
        $form->select('category_id', ll('Category name'))
                ->options(ContentService::categoryOptions(['type' => 'article']))
                ->rules('required', [
                    'required' => ll('Category cannot be empty')
                ]);
        $form->hidden('tag');

        $form->saving(function (Form $form) {
            // 将标签写入
            $tag = ContentService::contentTag($form->category_id, 'article');
            $form->tag = $tag;

            $type = request('imageType');
            $imageUrl = request('imageUrl');
            // 判断图片是否为空
            if ($type == 'link' && !empty($imageUrl)) {
                $image = get_image_file_by_url($imageUrl);
                if (empty($image)) {
                    $form->ignore(['image']);
                } else {
                    $form->image = $image;
                }
            }
        });
        $form->ignore(['imageType', 'imageUrl']);

        return $form;
    }
}
