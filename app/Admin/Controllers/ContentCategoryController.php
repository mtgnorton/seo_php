<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\SearchContentType;
use App\Admin\Components\Actions\SearchWebsiteCategory;
use App\Models\ContentCategory;
use App\Models\TemplateGroup;
use App\Services\CategoryService;
use App\Services\ContentService;
use App\Services\TemplateService;
use Encore\Admin\Controllers\AdminController;

use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content as EncoreContent;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Encore\Admin\Tree;
use Encore\Admin\Tree\Tools;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Form as EncoreForm;

class ContentCategoryController extends AdminController
{
    use HasResourceActions;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'ContentCategory';

    public function __construct()
    {
        $this->title = ll('Content category');
    }

    /**
     * Index
     *
     * @param EncoreContent $content
     * @return void
     */
    public function index(EncoreContent $content)
    {
        $type = request()->input('type');

        $content->title(lp(ucfirst($type), "Category"))
        ->description(lp(ucfirst($type), 'Category list'))
        ->row(function (Row $row) {
            $row->column(12, function (Column $column) {
                $type = request()->input('type', 0);
                $groupId = request()->input('group_id', 0);
                $group = TemplateGroup::find($groupId);
                $form = new EncoreForm();
                $form->action(admin_url('content-categories'));
                $form->select('parent_id', ll('Parent category'))
                    ->options(ContentCategory::selectOptions(function ($query) use ($type, $groupId) {
                        return $query->where([
                            'type' => $type,
                            'group_id' => $groupId
                        ]);
                    }));
                // $form->select('group_id', ll('Template group'))
                //         ->options(TemplateService::groupOptions())
                //         ->default($groupId)
                //         ->required();
                $form->hidden('group_id')->default($groupId);
                $form->hidden('category_id')->default($group->category_id);
                $form->hidden('type')->default($type);
                $form->text('name', ll('Category name'))->required();
                $form->number('sort', ll('Sort'))->default(0)
                        ->help(ll('Asc sort'));
                $column->append((new Box(ll(''), $form))->style('success'));
            });
        })
        ->row(function (Row $row) {
            $row->column(12, $this->treeView()->render());
        });
        $content = $content->render();
        $content = str_replace('<section class="content">','<section class="content content_categories_default">', $content);
        return $content;
        // return $content
    }

    /**
     * Tree view
     *
     * @return void
     */
    protected function treeView()
    {
        return ContentCategory::tree(function (Tree $tree) {
            $tree->disableCreate();
            $tree->disableSave();
            $tree->query(function ($tree) {
                $type = request()->input('type');
                $groupId = request()->input('group_id');

                // if ($type == 'diy') {
                //     $type = '';
                // }
                $queryData['type'] = $type;
                if (!empty($groupId)) {
                    $queryData['group_id'] = $groupId;
                }

                return $tree->where($queryData)->with('group');
            });
            $tree->branch(function ($branch) {
                $groupName = $branch['group']['name'] ?? '';
                if ($branch['parent_id'] == 0) {
                    return "<strong style='color:#0549f5;'>{$branch['name']} ----  {$groupName}</strong>";
                } else {
                    $url = "/admin/files?category_id={$branch['id']}&type={$branch['type']}";
    
                    return "<a href='{$url}' class=\"dd-nodrag\" style='color:black'><strong>{$branch['name']} ----  {$groupName}</strong></a>";
                }
            });
            $tree->tools(function (Tools $tools) {
                // $url = request()->url();
                // $type = request()->input('type');
                // $categoryId = request()->input('category_id', 0);

                // $tools->add(new SearchWebsiteCategory($url, $type));
                // $tools->add(new SearchContentType($url, $categoryId, $type));
            });
        });
    }

    /**
     * Edit Category
     *
     * @param int $id
     * @param EncoreContent $content
     * @return void
     */
    public function edit($id, EncoreContent $content)
    {
        return $content->title('编辑分类')
                ->description(ll('Add category'))
                ->row($this->form()->edit($id));
    }

    /**
     * form
     *
     * @return Form
     */
    public function form()
    {
        $form = new Form(new ContentCategory());

        $form->select('parent_id', ll('Parent category'))
                ->options(ContentCategory::selectOptions());
        $form->text('name', ll('Category name'))->required();
        $form->select('group_id', ll('Template group'))
                ->options(TemplateService::groupOptions())
                ->required();
        $form->hidden('category_id');
        $form->hidden('type');
        $form->number('sort', ll('Sort'))->required();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->footer(function ($footer) {
            $footer->disableReset();
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->saving(function (Form $form) {
            // 判断当前分类的父级是否是root或一级分类
            $parentId = $form->parent_id;
            $parent = ContentCategory::find($parentId);

            if (!empty($parent) && $parent->parent_id != 0) {
                return back_error('最多添加二级分类');
            }
        });

        $form->saved(function (Form $form) {
            return redirect('/admin/content-categories?type='.$form->type.'&group_id='.$form->group_id);
        });

        return $form;
    }
}
