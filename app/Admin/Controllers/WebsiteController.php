<?php

namespace App\Admin\Controllers;

use App\Models\Website;
use App\Services\CategoryService;
use App\Services\TemplateService;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class WebsiteController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Website';

    public function __construct()
    {
        $this->title = ll('Website');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Website());

        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Website name'));
        $grid->column('url', ll('Website url'));
        $grid->column('category_id', ll('Category'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('template_id', ll('Template'))->display(function () {
            return $this->template->name ?? '';
        });
        $states = [
            'on' => ['value' => 1, 'text' => '应用', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '不应用', 'color' => 'danger'],
        ];
        $grid->column('is_enabled', ll('Is enabled'))->switch($states);
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 名称
            $filter->like('name', ll('Website name'));
            // 标签
            $filter->like('url', ll('Website url'));
            // 分类
            $filter->equal('category_id', ll('Category id'))
                    ->select(CategoryService::categoryOptions());
            // 模板类型
            $filter->equal('template_id', ll('Template name'))
                    ->select(TemplateService::templateOptions());
        });

        $grid->actions(function ($actions) {
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
        $show = new Show(Website::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Website name'));
        $show->field('url', ll('Website url'));
        $show->field('category_id', ll('Category name'));
        $show->field('template_id', ll('Template name'));
        $show->field('is_enabled', ll('Is enabled'));
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
        $form = new Form(new Website());

        $form->text('name', ll('Website name'))
                ->rules('required', [
                    'required' => lp('Website name', 'Cannot be empty')
                ]);
        $form->text('url', ll('Website url'))
                ->help(ll('need not http or https'))
                ->rules('required', [
                    'required' => lp('Website url', 'Cannot be empty')
                ]);
        if ($form->isCreating()) {
            $form->select('category_id', ll('Category'))
                ->options(CategoryService::categoryOptions())
                ->rules('required', [
                    'required' => lp('Category', 'Cannot be empty')
                ])->load('templates', '/admin/websites/types');
            // $form->select('template_id', ll('Template'))
            //     ->rules('required', [
            //         'required' => lp('Template', 'Cannot be empty')
            //     ]);

            $form->multipleSelect('templates', ll('Template'));
                            // ->options(TemplateService::templates());
        }

        if ($form->isEditing()) {
            $form->select('category_id', ll('Category'))
                ->options(CategoryService::categoryOptions())
                ->readonly()
                ->rules('required', [
                    'required' => lp('Category', 'Cannot be empty')
                ]);
            // $form->select('template_id', ll('Template'))
            //     ->rules('required', [
            //         'required' => lp('Template', 'Cannot be empty')
            //     ]);

            $params = request()->route()->parameters();
            $websiteId = $params['website'] ?? 0;
            $website = Website::find($websiteId);

            $form->multipleSelect('templates', ll('Template'))
                            ->options(TemplateService::templates(['category_id' => $website->category_id]));
        }

        $states = [
            'on' => ['value' => 1, 'text' => '应用', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '不应用', 'color' => 'danger'],
        ];

        $form->switch('is_enabled', ll('Is enabled'))
                ->states($states)
                ->default(1);

        $form->saving(function (Form $form) {
            // 去除链接中是否包含http或者https
            $form->url = str_replace("https://", "", $form->url);
            $form->url = str_replace("http://", "", $form->url);
            $form->url = trim($form->url, '/');
        });

        return $form;
    }

    /**
     * 获取模板类型数据
     *
     * @return void
     */
    public function types(Request $request)
    {
        $categoryId = $request->input('q');

        return TemplateService::templateSelect(['category_id' => $categoryId]);
    }
}
