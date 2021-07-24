<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use App\Services\CommonService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Admin;

class CategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Category');
        $this->icon = ('/asset/imgs/default_icon/2.png');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category());

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Category name'));
        $grid->column('tag', ll('Tag'));
        // $grid->column('module', ll('Category module'));
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        // 禁用导出
        $grid->disableExport();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 标题搜索
            $filter->like('name', ll('Category name'));
            // 其他模板搜索
            $filter->like('module', ll('Category module'));
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            // $actions->add(new CategoryFixedRule($actions->row));
            // $actions->add(new CategoryRandomRule($actions->row));
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
        $show = new Show(Category::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Name'));
        $show->field('tag', ll('Tag'));
        // $show->field('module', ll('Category module'));
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
        // $systemData = CategoryService::CATEGORY_BASE;

        $form = new Form(new Category());

        $form->text('name', ll('Category name'))->required();
        if ($form->isCreating()) {
            $form->text('tag', ll('Tag'))
                ->help(ll('English or pinyin'))
                ->required();
        }
        // $form->keyValue('module', ll('Category module'))->value($systemData);

        // $form->saved(function (Form $form) {
        //     if ($form->isCreating()) {
        //         CategoryService::createCategroyRules($form->model());
        //     }

        //     if ($form->isEditing()) {
        //         CategoryService::updateCategoryRules($form->model());
        //     }
        // });
        $css = <<<CSS

CSS;
        $js = <<<JS
JS;
        Admin::style($css);
        Admin::script($js);
        $form->saved(function (Form $form) {
            $categoryId = $form->model()->id;
            $url = CategoryService::addMenu($categoryId);
            echo '<script>url="'.'/admin/'.$url.'";window.location.href=url;</script>';
            exit;
//            Header("Location: ".'/admin/'.$url);
            // return redirect('/admin/'.$url);
        });

        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });

        return $form;
    }
}
