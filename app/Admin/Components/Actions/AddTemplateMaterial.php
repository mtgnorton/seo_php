<?php

namespace App\Admin\Components\Actions;

use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class AddTemplateMaterial extends Action
{
    public $name = '新增模板素材';

    protected $selector = '.add-templated-material';

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
        $file = $request->file('file');
        $templateId = $request->input('template_id');
        $moduleId = $request->input('module_id');
        $type = $request->input('type');

        $result = TemplateService::addMaterial($file, $type, $templateId, $moduleId);

        if ($result['code'] != 0) {
            return $this->response()->error($result['message']);
        }

        return $this->response()->success($result['message'])->refresh();
    }

    public function form()
    {
        $this->select('template_id', ll('Template name'))
                ->options(TemplateService::templateOptions())
                ->rules('required', [
                    'required' => ll('Type cannot be empty')
                ])->default($this->templateId)
                ->readonly();

        $this->select('module_id', ll('Module'))
                // ->options(TemplateService::moduleOptions($this->templateId))
                ->options(TemplateService::treeModulesTitle($this->templateId))
                ->rules('required', [
                    'required' => lp('Template name', 'Cannot be empty')
                ]);

        $this->select('type', ll('Template material type'))
                ->options(TemplateService::MATERIAL_TYPE)
                ->rules('required', [
                    'required' => ll('Type cannot be empty')
                ])->default($this->type);

        $this->multipleFile('file', ll('File'))
                ->options([
                    'showPreview' => false,
                    'maxFileCount' => 0
                ])->rules('required', [
                    'required' => ll('File does not exist')
                ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-templated-material"><i class="fa fa-upload"></i> 新增素材</a>
HTML;
    }
}
