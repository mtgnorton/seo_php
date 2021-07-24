<?php

namespace App\Admin\Components\Actions;

use App\Services\TemplateService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TemplateModuleFileUpload extends RowAction
{
    public $name = '上传文件';

    public function handle(Model $model, Request $request)
    {
        $file = $request->file('file');
        $result = TemplateService::uploadModuleFile($model, $file);

        if ($result['code'] === 0) {
            return $this->response()->success($result['message'])->refresh();
        } else {
            return $this->response()->error($result['message'])->refresh();
        }
    }

    public function form()
    {
        $this->multipleFile('file', ll('File'))->options([
            'allowedPreviewTypes'=> ['image', 'text', 'video', 'audio', 'flash', 'object'],
            'showPreview' => false
        ])->rules('required', [
            'required' => lp('File', 'cannot be empty')
        ]);
    }

}
