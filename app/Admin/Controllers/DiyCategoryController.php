<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddDiyTag;
use App\Models\ContentCategory;
use App\Services\ContentService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;

class DiyCategoryController extends AdminController
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
        $grid = new Grid(new ContentCategory());

        $grid->model()->where('type', 'diy');

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Category name'))->expand(function ($model) {
            $data = $model->tags->map(function ($data) {
                $result = ContentService::getDiyTagOption($data);

                return $result;
            })->toArray();

            return new Table(['名称', '标识', '操作'], $data);
        });
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            $actions->add(new AddDiyTag());
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
        $show = new Show(ContentCategory::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Category name'));
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
        $form = new Form(new ContentCategory());

        $form->hidden('type')->default('diy');
        $form->text('name', ll('Category name'));

        return $form;
    }

    /**
     * 上传文件
     *
     * @param Request $request
     * @return void
     */
    public function uploadFile(Request $request)
    {
        $tagId = $request->input('tag_id');
        $file = $request->file;

        $result = ContentService::import($file, 'diy', 0, $tagId);

        return $result;
    }
}
