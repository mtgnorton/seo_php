<?php

namespace App\Admin\Components\Actions;

use App\Services\TemplateService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddTemplateModuleRow extends RowAction
{
    public $name;

    public $templateId;

    public function __construct($templateId = 0)
    {
        $this->name = lp('Add', 'Module');
        $this->templateId = $templateId;

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        $templateId = $request->input('template_id', 0);
        $columnName = $request->input('column_name', '');
        $columnTag = $request->input('column_tag', '');

        $result = TemplateService::addModule($templateId, $columnName, $columnTag);

        if ($result['code'] != 0) {
            return $this->response()->error($result['message']);
        }

        return $this->response()->success('新增成功')->refresh();
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
                    'required' => lp('Template name','cannot be empty')
                ])->default($this->templateId);
        $this->text('column_name', ll('Column name'))
                ->rules('required', [
                    'required' => lp('Column name',  'cannot be empty')
                ]);
        $this->text('column_tag', ll('Column tag'))
                ->help(lp('Both side need not /', '(', 'Use', 'English or pinyin', ')'))
                ->rules('required', [
                    'required' => lp('Column tag', 'cannot be empty')
                ]);
    }

}
