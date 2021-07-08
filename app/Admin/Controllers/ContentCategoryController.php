<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\SearchContentType;
use App\Admin\Components\Actions\SearchWebsiteCategory;
use App\Models\ContentCategory;
use App\Services\CategoryService;
use App\Services\ContentService;
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

    // /**
    //  * Make a grid builder.
    //  *
    //  * @return Grid
    //  */
    // protected function grid()
    // {
    //     $grid = new Grid(new ContentCategory());

    //     $grid->column('id', ll('Id'));
    //     $grid->column('name', ll('Category name'));
    //     $grid->column('tag', ll('Category tag'));
    //     $grid->column('type', ll('Type'))->display(function ($type) {
    //         $contentType = ContentService::CONTENT_TYPE;

    //         return $contentType[$type] ?? '未知';
    //     });
    //     $grid->column('created_at', ll('Created at'));
    //     $grid->column('updated_at', ll('Updated at'));

    //     return $grid;
    // }

    // /**
    //  * Make a show builder.
    //  *
    //  * @param mixed $id
    //  * @return Show
    //  */
    // protected function detail($id)
    // {
    //     $show = new Show(ContentCategory::findOrFail($id));

    //     $show->field('id', ll('Id'));
    //     $show->field('name', ll('Category name'));
    //     $show->field('tag', ll('Category tag'));
    //     $show->field('type', ll('Type'));
    //     $show->field('created_at', ll('Created at'));
    //     $show->field('updated_at', ll('Updated at'));

    //     return $show;
    // }

    // /**
    //  * Make a form builder.
    //  *
    //  * @return Form
    //  */
    // protected function form()
    // {
    //     $form = new Form(new ContentCategory());

    //     $form->text('name', ll('Category name'));
    //     $form->text('tag', ll('Category tag'))->help(ll('English or pinyin'));
    //     $form->select('type', ll('Type'))->options(ContentService::CONTENT_TYPE);

    //     return $form;
    // }

    /**
     * Index
     *
     * @param EncoreContent $content
     * @return void
     */
    public function index(EncoreContent $content)
    {
        $type = request()->input('type');

        return $content->title(lp(ucfirst($type), "Category"))
                    ->description(lp(ucfirst($type), 'Category list'))
                    ->row(function (Row $row) {
                        $row->column(12, function (Column $column) {
                            $type = request()->input('type', 0);
                            $categoryId = request()->input('category_id', 0);
                            $form = new EncoreForm();
                            $form->action(admin_url('content-categories'));
                            $form->select('parent_id', ll('Parent category'))
                                ->options(ContentCategory::selectOptions(function ($query) use ($type, $categoryId) {
                                    return $query->where([
                                        'type' => $type,
                                        'category_id' => $categoryId
                                    ]);
                                }));
                            $form->select('category_id', ll('Website category'))
                                    ->options(CategoryService::categoryOptions())
                                    ->default($categoryId)
                                    ->required();
                            $form->hidden('type')->default($type);
                            $form->text('name', ll('Category name'))->required();
                            $form->number('sort', ll('Sort'))->default(0)
                                    ->help(ll('Asc sort'));
                            $column->append((new Box(ll('Add category'), $form))->style('success'));
                        });
                    })
                    ->row(function (Row $row) {
                        $row->column(12, $this->treeView()->render());
                    });
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
            $tree->query(function ($tree) {
                $type = request()->input('type');
                $categoryId = request()->input('category_id');

                // if ($type == 'diy') {
                //     $type = '';
                // }
                $queryData['type'] = $type;
                if (!empty($categoryId)) {
                    $queryData['category_id'] = $categoryId;
                }

                return $tree->where($queryData)->with('category');
            });
            $tree->branch(function ($branch) {
                $categoryName = $branch['category']['name'] ?? '';
                if ($branch['parent_id'] == 0) {
                    return "<strong style='color:#0549f5;'>{$branch['name']} ----  {$categoryName}</strong>";
                } else {
                    $url = "/admin/files?category_id={$branch['id']}&type={$branch['type']}";
    
                    return "<a href='{$url}' class=\"dd-nodrag\" style='color:black'><strong>{$branch['name']} ----  {$categoryName}</strong></a>";
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
        return $content->title(ll('Category'))
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
        $form->select('category_id', ll('Website category'))
                ->options(CategoryService::categoryOptions())
                ->required();
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

        $form->saved(function (Form $form) {
            return redirect('/admin/content-categories?type='.$form->type.'&category_id='.$form->category_id);
        });

        return $form;
    }
}
