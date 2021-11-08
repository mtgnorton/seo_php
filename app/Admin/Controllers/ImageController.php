<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\ImportText;
use App\Models\Image;
use App\Services\ContentService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Image');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Image());

        $grid->column('id', ll('Id'));
        $grid->column('url', ll('Image'))->gallery(['width' => 100, 'height' => 100]);
        $grid->column('category_id', ll('Category name'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('tag', ll('Tag name'));
        $grid->column('file_id', ll('File name'))->display(function () {
            return $this->file->path ?? '';
        })->downloadable();
        $grid->column('is_collected', ll('Is collected'))->display(function ($isCollected) {
            return $isCollected == 1 ? '是' : '不是';
        });;
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
                    ->select(ContentService::categoryOptions(['type' => 'image']));
        });

        // 添加导入
        $grid->tools(function ($tools) {
            $tools->append(new ImportText(
                'image',
                '文本格式: 一条图片链接一行 注: 请确保链接的有效性, 以http://或https://开头'
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
        $show = new Show(Image::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('url', ll('Image'))->image('', 1000, 1000);
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
        $form = new Form(new Image());

        $form->select('category_id', ll('Category name'))
                ->options(ContentService::categoryOptions(['type' => 'image']))
                ->rules('required', [
                    'required' => ll('Category cannot be empty')
                ]);
        $imageType = ContentService::IMAGE_TYPE;
        $form->radioButton('imageType', ll('Image'))
            ->options($imageType)
            ->when('local', function (Form $form) {
                $form->image('url', ll('Image'))
                    ->uniqueName()
                    ->move('images/'.date('Y/m/d'));
            })->when('link', function (Form $form) {
                $form->url('imageUrl', ll('Image'))
                    ->help(ll('Url need http or https'));
            })->default('local');
        $form->hidden('tag');

        $form->saving(function (Form $form) {
            // 将标签写入
            $tag = ContentService::contentTag($form->category_id, 'image');
            $form->tag = $tag;

            $type = request('imageType');
            $imageUrl = request('imageUrl');
            // 判断图片是否为空
            if ($type == 'link') {
                if (empty($imageUrl)) {
                    throw new Exception(ll('File uploaded failed'));
                }
                $image = get_image_file_by_url($imageUrl);
                if (empty($image)) {
                    $form->ignore(['url']);
                } else {
                    $form->url = $image;
                }
            } else {
                if (empty($form->url)) {
                    throw new Exception(ll('File uploaded failed'));
                }
            }
        });
        $form->ignore(['imageType', 'imageUrl']);

        return $form;
    }

    /**
     * 上传图片
     *
     * @return void
     */
    public function upload(Request $request)
    {
        $urls = [];

        foreach ($request->file() as $file) {
            $urls[] = Storage::url(Storage::put('images', $file));
        }

        return [
            "errno" => 0,
            "data"  => $urls,
        ];
    }
}
