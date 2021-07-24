<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddTemplateGroup;
use App\Admin\Components\Actions\AddTemplateWebsiteRow;
use App\Admin\Components\Actions\DeleteCategory;
use App\Admin\Components\Actions\Template;
use App\Admin\Components\Actions\TemplateSiteConfig;
use App\Models\TemplateGroup;
use App\Services\CategoryService;
use App\Services\ConfigService;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TempalteGroupController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Template group');
        $this->icon = ('');
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $grid = new Grid(new TemplateGroup());
        $categoryId = request()->input('category_id', 0);

        if (!empty($categoryId)) {
            $grid->model()->where('category_id', $categoryId);
        }

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Tempate group name'));
        $grid->column('tag', ll('Tag'));
        $grid->column('category_id', ll('Category'))->display(function () {
            return $this->category->name;
        });
        $grid->column('tempaltes', ll('Template'))->display(function () {
            $templates = $this->templates()
                        ->pluck('name')->toArray() ?? [];
                        
            $str =  implode(',', $templates);
            if (mb_strlen($str) > 40) {
                $str = mb_substr($str, 0, 40) . '...';
            }

            return $str ?: '暂无模板';
        })->link(function () {
            return '/admin/templates?group_id='.$this->id;
        }, '__self');

        $grid->column('websites', ll('Bind host'))->display(function () {
            $modules = $this->websites()
                        ->pluck('url')->toArray() ?? [];
                        
            $str =  implode(',', $modules);
            if (mb_strlen($str) > 40) {
                $str = mb_substr($str, 0, 40) . '...';
            }

            return $str ?: '暂无域名';
        })->link(function () {
            return '/admin/website-templates?group_id='.$this->id;
        }, '__self');
        
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        // 禁用导出
        $grid->disableExport();
        // 禁用新增
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 名称
            $filter->like('name', ll('Template name'));
            // 标签
            $filter->like('tag', ll('Template tag'));
        });

        $grid->tools(function ($tools) use ($categoryId) {
            $tools->append(new AddTemplateGroup($categoryId));
            $tools->append(new DeleteCategory($categoryId));
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            // 去掉编辑
            $actions->disableEdit();
            $actions->add(new Template($actions->row->id));
            $actions->add(new AddTemplateWebsiteRow());
            $actions->add(new TemplateSiteConfig());
        });
        $grid = $grid->render();
        $grid = str_replace('<section class="content">','<section class="content content_template_modules_default">',$grid);
        
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
        $show = new Show(TemplateGroup::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Tempate group name'));
        $show->field('tag', ll('Tag'));
        $show->field('category_id', ll('Category'));
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
        $form = new Form(new TemplateGroup());

        $form->text('name', ll('Name'));
        $form->text('tag', ll('Tag'));
        $form->select('category_id', ll('Category'))
            ->options(CategoryService::categoryOptions())
            ->rules('required', [
                'required' => lp('Category', 'Cannot be empty')
            ]);

        $form->saved(function (Form $form) {
            if ($form->isCreating()) {
                // // 添加默认配置
                $group = $form->model();
                ConfigService::addDefaultAd($group);
                ConfigService::addDefaultCache($group);
                ConfigService::addDefaultSite($group);
            }
        });

        return $form;
    }
}
