<?php

namespace App\Admin\Components\Actions;

use App\Models\TemplateGroup;
use App\Models\TemplateModule;
use App\Models\Website;
use App\Services\CommonService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class AddTemplateModulePage extends Action
{
    public $name = '上传文件';

    protected $selector = '.add-templated-website';

    protected $moduleId;

    public function __construct($moduleId=0)
    {
        parent::__construct();

        $this->moduleId = $moduleId;
    }

    public function handle(Request $request)
    {
        $file = $request->file('file');
        $moduleId = $request->input('module_id', 0);
        $group = TemplateModule::find($moduleId);

        $result = TemplateService::uploadModuleFile($group, $file);

        if ($result['code'] === 0) {
            return $this->response()->success($result['message'])->refresh();
        } else {
            return $this->response()->error($result['message'])->refresh();
        }
    }

    public function form()
    {
        $this->hidden('module_id')->default($this->moduleId);
        $this->multipleFile('file', ll('File'))->options([
            'allowedPreviewTypes'=> ['image', 'text', 'video', 'audio', 'flash', 'object'],
            'showPreview' => false
        ])->rules('required', [
            'required' => lp('File', 'cannot be empty')
        ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-templated-website"><i class="fa fa-upload"></i> 上传文件</a>
HTML;
    }
}
