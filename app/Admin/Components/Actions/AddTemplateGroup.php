<?php

namespace App\Admin\Components\Actions;

use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;



class AddTemplateGroup extends Action
{
    public $name = '新增模板分组';

    protected $selector = '.add-category';

    protected $categoryId;

    public function __construct($categoryId=0)
    {
        parent::__construct();

        $this->categoryId = $categoryId;
    }

    public function handle(Request $request)
    {
        $data = $request->input();

        $result = TemplateService::addGroup($data);

        if ($result['code'] == 0) {
            return $this->response()->success('新增成功')->refresh();
        }

        return $this->response()->error('新增失败');
    }

    public function form()
    {
        $this->text('name', ll('Template name'))
            ->rules('required', [
                'required' => lp('Template name', 'Cannot be empty')
            ]);
        $this->text('tag', ll('Tag'))
            ->rules('required', [
                'required' => lp('Tag', 'Cannot be empty')
            ])->help(lp('English or pinyin', ',', 'Template tag help'));
        $this->select('category_id', ll('Category'))
            ->options(CategoryService::categoryOptions())
            ->default($this->categoryId)
            ->rules('required', [
                'required' => lp('Category', 'Cannot be empty')
            ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-category"><i class="fa fa-plus"></i> 新增</a>
HTML;
    }
}
