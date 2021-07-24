<?php

namespace App\Admin\Components\Actions;

use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class AddTemplateType extends Action
{
    public $name = '新增模板类型';

    protected $selector = '.add-templated-type';

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

        $result = TemplateService::addType(compact('name', 'tag'));

        if ($result) {
            return $this->response()->success('新增成功')->refresh();
        }

        return $this->response()->error('新增失败');
    }

    public function form()
    {
        $this->text('name', ll('Template type name'))
            ->rules('required', [
                'required' => lp('Template type name', 'Cannot be empty')
            ]);
        $this->text('tag', ll('Template type tag'))
            ->rules('required', [
                'required' => lp('Template type tag', 'Cannot be empty')
            ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-templated-type"><i class="fa fa-upload"></i> 新增模板类型</a>
HTML;
    }
}
