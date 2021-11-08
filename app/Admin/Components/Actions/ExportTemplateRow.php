<?php

namespace App\Admin\Components\Actions;

use App\Jobs\ProcessExportTemplate;
use App\Models\Template;
use App\Models\TemplateExport;
use App\Models\TemplateGroup;
use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExportTemplateRow extends RowAction
{
    public $name;
    
    public $templateId;

    public function __construct()
    {
        $this->name = lp('Export', 'Template');

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        // set_time_limit(0);
        // ini_set('max_execution_time', '0');
        // ini_set('max_input_time', '0');
        // ini_set('memory_limit', '-1');
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // 写入导出模板表
        $templateId = $model->id;
        $export = TemplateExport::create([
            'template_id' => $templateId,
            'name' => $model->name,
            'tag' => $model->tag,
            'message' => '开始导出模板中, 请耐心等待...'
        ]);

        ProcessExportTemplate::dispatch($templateId, $export);
        $url = '/admin/template-exports?group_id='.$model->group_id;

        return $this->response()->success('导出成功')->redirect($url);
        // return $this->response()->success('导出成功')->download($result['data']);
    }

    /**
     * 表单内容
     *
     * @return void
     */
    public function form()
    {
        $this->hidden('templateId')->default($this->templateId);
        $this->radio('status', lp('Is Export', '?'))
                ->options([])
                ->default('on')
                ->help(ll('Export template help'));
    }

}
