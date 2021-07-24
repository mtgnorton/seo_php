<?php

namespace App\Admin\Components\Actions;

use App\Services\TemplateService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddTemplateMaterialRow extends RowAction
{
    public $name;

    public $templateId;

    public function __construct($templateId = 0)
    {
        $this->name = lp('Add', 'Material');
        $this->templateId = $templateId;

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
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

    /**
     * 表单内容
     *
     * @return void
     */
    public function form()
    {
        $this->select('template_id', ll('Template name'))
                ->options(TemplateService::templateOptions())
                ->rules('required', [
                    'required' => lp('Template name', 'cannot be empty')
                ])->default($this->templateId)
                ->readonly();

        $this->select('module_id', ll('Module'))
                ->options(TemplateService::moduleOptions($this->templateId))
                ->rules('required', [
                    'required' => lp('Template name', 'cannot be empty')
                ]);

        $this->select('type', ll('Template material type'))
                ->options(TemplateService::MATERIAL_TYPE)
                ->rules('required', [
                    'required' => ll('Type cannot be empty')
                ])->default('other');

        $this->multipleFile('file', ll('File'))
                ->options([
                    'showPreview' => false,
                    'maxFileCount' => 0
                ])->rules('required', [
                    'required' => ll('File does not exist')
                ]);
    }

}
