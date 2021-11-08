<?php

namespace App\Admin\Components\Actions;

use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;



class AddTemplate extends Action
{
    public $name = '新增模板';

    protected $selector = '.add-template';

    protected $categoryId;

    protected $groupId;

    protected $typeId;

    public function __construct($categoryId=0, $groupId=0, $typeId=0)
    {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->groupId = $groupId;
        $this->typeId = $typeId;
    }

    public function handle(Request $request)
    {
        $data = $request->input();

        $result = TemplateService::add($data);

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
        $this->text('tag', ll('Template tag'))
            ->rules('required', [
                'required' => lp('Template tag', 'Cannot be empty')
            ])->help(lp('English or pinyin', ',', 'Template tag help'));
        $this->select('type_id', ll('Template type'))
            ->options(TemplateService::typeOptions())
            ->default($this->typeId)
            ->rules('required', [
                'required' => lp('Template type', 'Cannot be empty')
            ]);
        // $this->select('category_id', ll('Category'))
        //     ->options(CategoryService::categoryOptions())
        //     ->default($this->categoryId)
        //     ->rules('required', [
        //         'required' => lp('Category', 'Cannot be empty')
        //     ]);
        // $this->select('group_id', ll('Template group'))
        //     ->options(TemplateService::groupOptions(['category_id' => $this->categoryId]))
        //     ->default($this->categoryId)
        //     ->rules('required', [
        //         'required' => lp('Category', 'Cannot be empty')
        //     ]);
        $this->hidden('groupId')->default($this->groupId);
        $this->hidden('categoryId')->default($this->categoryId);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-template"><i class="fa fa-plus"></i> 新增</a>
HTML;
    }
}
