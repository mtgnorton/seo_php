<?php

namespace App\Admin\Components\Actions;

use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

/**
 * 删除模板
 */
class DeleteCategory extends Action
{
    public $name = '删除分类';

    protected $selector = '.delete-category';

    protected $categoryId;

    protected $typeId;

    public function __construct($categoryId=0)
    {
        parent::__construct();

        $this->categoryId = $categoryId;
    }

    public function handle(Request $request)
    {
        $categoryId = $request->input('categoryId');

        $result = CategoryService::delete($categoryId);

        if ($result['code'] == 0) {
            return $this->response()->success('删除成功')->location('/admin/categories/create');
        }

        return $this->response()->error('删除失败');
    }

    public function form()
    {
        $this->hidden('categoryId')->default($this->categoryId);
        $this->radio('status', lp('Is delete', 'Category', '?'))
                ->options([])
                ->default('on')
                ->help(ll('Delete category help'));
        
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-danger delete-category" style="margin-left: 20px"><i class="fa fa-close"></i> 删除分类</a>
HTML;
    }
    
}
