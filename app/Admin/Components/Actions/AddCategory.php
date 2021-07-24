<?php

namespace App\Admin\Components\Actions;

use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class AddCategory extends Action
{
    public $name = '新增分类';

    protected $selector = '.add-category';

    protected $templateId;

    protected $type;

    public function __construct($templateId=0, $type='other')
    {
        parent::__construct();

        $this->templateId = $templateId;
        $this->type = $type;
    }

    public function handle(Request $request)
    {
        $name = $request->input('name');
        $tag = $request->input('tag');

        $result = CategoryService::add(compact('name', 'tag'));

        if ($result) {
            return $this->response()->success('新增成功')->refresh();
        }

        return $this->response()->error('新增失败');
    }

    public function form()
    {
        $this->text('name', ll('Category name'))
            ->rules('required', [
                'required' => lp('Category name', 'Cannot be empty')
            ]);
        $this->text('tag', ll('Tag'))
            ->help(ll('English or pinyin'))
            ->rules('required', [
                'required' => ll('File does not exist')
            ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-category"><i class="fa fa-upload"></i> 新增分类</a>
HTML;
    }
}
