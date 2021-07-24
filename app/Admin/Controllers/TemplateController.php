<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddCategory;
use App\Admin\Components\Actions\AddTemplate;
use App\Admin\Components\Actions\AddTemplateMaterialRow;
use App\Admin\Components\Actions\AddTemplateModulePage;
use App\Admin\Components\Actions\AddTemplateModuleRow;
use App\Admin\Components\Actions\AddTemplateType;
use App\Admin\Components\Actions\AddTemplateWebsiteRow;
use App\Admin\Components\Actions\CategoryList;
use App\Admin\Components\Actions\CopyTemplateRow;
use App\Admin\Components\Actions\DeleteCategory;
use App\Admin\Components\Actions\TemplateMaterial;
use App\Admin\Components\Actions\TemplateModule;
use App\Admin\Components\Actions\TemplateSiteConfig;
use App\Admin\Components\Actions\TemplateTypeList;
use App\Admin\Components\Renders\WebsiteTemplateRender;
use App\Console\Commands\DeleteCacheFile;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\TemplateType;
use App\Services\CategoryService;
use App\Services\CommonService;
use App\Services\ConfigService;
use App\Services\TemplateService;
use Encore\Admin\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\ContextMenuActions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class TemplateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Template';

    public function __construct()
    {
        $this->title = ll('Edit');
        $this->icon = ('/asset/imgs/default_icon/10.png');
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        $content->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->breadcrumb(
                ['text' => lp('Template', 'List')]
            )->body($this->grid());
        $content = $content->render();
        $content = str_replace('<section class="content">','<section class="content content_template_default">',$content);
        return $content;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Template());

        $groupId = request()->input('group_id', 0);
        $type = request()->input('_selector')['type_id'] ?? 0;

        if (!empty($groupId)) {
            $grid->model()->where('group_id', $groupId);
        }

        // $grid->column('id', ll('Id'));
        $grid->column('name', ll('Template name'));
        $grid->column('tag', ll('Template tag'));
        $grid->column('category_id', ll('Category'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('type_id', ll('Template type'))->display(function () {
            return $this->type->name ?? '';
        });
        $grid->column('module', ll('Template module'))->display(function () {
            $modules = $this->modules()
                        ->pluck('route_name')->toArray() ?? [];

            $str =  implode(',', $modules);
            if (mb_strlen($str) > 40) {
                $str = mb_substr($str, 0, 40) . '...';
            }

            return $str ?? '暂无模块';
        // })->modal(ll('Template module'), function ($model) {
        //     $modules = $model->modules()
        //                 ->select('id', 'column_name', 'column_tag', 'route_name', 'route_tag')
        //                 ->get();

        //     return new Table([
        //         ll('Id'),
        //         ll('Column name'),
        //         ll('Column tag'),
        //         ll('Route name'),
        //         ll('Path'),
        //     ], $modules->toArray());
        // });
        })->link(function () {
            return '/admin/template-modules?template_id='.$this->id;
        }, '__self');

        // $grid->column('websites', ll('Bind host'))->display(function () {
        //     $modules = $this->websites()
        //                 ->pluck('url')->toArray() ?? [];
                        
        //     $str =  implode(',', $modules);
        //     if (mb_strlen($str) > 40) {
        //         $str = mb_substr($str, 0, 40) . '...';
        //     }

        //     return $str;
        // })->link(function () {
        //     return '/admin/website-templates?template_id='.$this->id;
        // });

        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用查询过滤器
        $grid->disableFilter();
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
            // 分类
            $filter->equal('category_id', ll('Category'))
                    ->select(CategoryService::categoryOptions());
            // 模板类型
            $filter->equal('type_id', ll('Template type'))
                    ->select(TemplateService::typeOptions());
        });

        $grid->selector(function (Grid\Tools\Selector $selector) {
            // 类型规格选择器
            $selector->select(
                'type_id',
                ll('Template type'),
                TemplateService::typeOptions()
            );
        });

        $grid->tools(function ($tools) use ($groupId, $type) {
            $categoryId = TemplateGroup::find($groupId)->category_id;
            $tools->append(new AddTemplate($categoryId, $groupId, $type));
            $templateUrl = '/admin/template-groups?category_id='.$categoryId;
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template group')));
        });

        $grid->actions(function ($actions) {
            // 去掉查看
            $actions->disableView();
            $actions->disableEdit();
            $actions->add(new AddTemplateModuleRow($actions->row->id));
            // $actions->add(new TemplateModule);
            $actions->add(new AddTemplateMaterialRow($actions->row->id));
            $actions->add(new TemplateMaterial);
            $actions->add(new CopyTemplateRow());
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
        $show = new Show(Template::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Template name'));
        $show->field('tag', ll('Template tag'));
        $show->field('category_id', ll('Category'))->as(function () {
            return $this->category->name ?? '';
        });
        $show->field('type_id', ll('Template type'))->as(function () {
            return $this->type->name ?? '';
        });
        $show->field('module', ll('Template module'))->json();
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
        $form = new Form(new Template());

        $form->text('name', ll('Template name'))
            ->rules('required', [
                'required' => lp('Template name', 'Cannot be empty')
            ]);
        $form->text('tag', ll('Template tag'))
            ->rules('required', [
                'required' => lp('Template tag', 'Cannot be empty')
            ])->help(ll('English or pinyin'));
        $form->select('type_id', ll('Template type'))
                ->options(TemplateService::typeOptions())
                ->rules('required', [
                    'required' => lp('Template type', 'Cannot be empty')
                ]);

        // if ($form->isCreating()) {
        $form->select('category_id', ll('Category'))
            ->options(CategoryService::categoryOptions())
            ->rules('required', [
                'required' => lp('Category', 'Cannot be empty')
            ]);
        // $states = [
        //     'on' => ['value' => 1, 'text' => '应用', 'color' => 'success'],
        //     'off' => ['value' => 0, 'text' => '不应用', 'color' => 'danger'],
        // ];

        // $form->switch('is_used', ll('Is used'))->states($states);

        if ($form->isCreating()) {
            $form->hidden('module');
        }
        $form->hidden('type_tag');

        //     // 获取所有分类
        //     $categories = Category::all();
        //     foreach ($categories as $category) {
        //         $formSelect->when($category->id, function (Form $form) use ($category) {
        //             $form->text('module_help', ll("Template module help title"))
        //                         ->value(ll('Template module help'))
        //                         ->disable();
        //             $form->keyValue('module', ll('Module'))
        //                 ->value($category->module);
        //         });
        //     }
        // }

        // if ($form->isEditing()) {
        //     $form->select('category_id', ll('Category'))
        //         ->options(Category::categoryOptions())
        //         ->rules('required', [
        //             'required' => lp('Category', 'Cannot be empty')
        //         ])->readonly();

        //     $form->keyValue('module', ll('Module'))->help('111');
        // }

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->module = CategoryService::CATEGORY_BASE;
            }
            $form->type_tag = TemplateType::find($form->type_id)->tag;
        });

        $form->saved(function (Form $form) {
            if ($form->isCreating()) {
                // 创建模板模块表数据
                TemplateService::createIndexModule($form->model());
            }
        });

        $form->tools(function (Form\Tools $tools) {
            // 去掉`删除`按钮
            $tools->disableDelete();

            // 去掉列表
            $tools->disableList();

            // 去掉`查看`按钮
            $tools->disableView();
        });

        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });

        $form->ignore(['module_help']);

        return $form;
    }
}
