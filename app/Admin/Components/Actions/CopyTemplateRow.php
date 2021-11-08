<?php

namespace App\Admin\Components\Actions;

use App\Jobs\ProcessCopyTemplate;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CopyTemplateRow extends RowAction
{
    public $name;

    public $group_id;

    public $category_id;

    public function __construct()
    {
        $this->name = lp('Copy', 'Template');
        $groupId = request()->input('group_id');
        $group = TemplateGroup::find($groupId);

        $this->group_id = $groupId;
        $this->category_id = $group->category_id;

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        
        $templateId = $model->id;
        $params = $request->only([
            'name',
            'tag',
            'type_id',
            'group_id',
        ]);

        ProcessCopyTemplate::dispatch($templateId, $params);

        // if ($result['code'] != 0) {
        //     return $this->response()->error($result['message']);
        // }

        return $this->response()->success('复制成功, 请耐心等待两分钟左右即可导入完毕, 具体导入时间与模板大小有关')->refresh();
    }

    /**
     * 表单内容
     *
     * @return void
     */
    public function form()
    {
        $this->text('name', ll('Template name'))
            ->rules('required', [
                'required' => lp('Template name', 'Cannot be empty')
            ]);
        $this->text('tag', ll('Template tag'))
            ->rules('required', [
                'required' => lp('Template tag', 'Cannot be empty')
            ])->help(ll('English or pinyin'));
        $this->select('type_id', ll('Template type'))
            ->options(TemplateService::typeOptions())
            ->rules('required', [
                'required' => lp('Template type', 'Cannot be empty')
            ]);
        // $this->select('category_id', ll('Category'))
        //     ->options(CategoryService::categoryOptions())
        //     ->rules('required', [
        //         'required' => lp('Category', 'Cannot be empty')
        //     ]);
        $this->select('group_id', ll('Template group'))
            ->options(TemplateService::groupOptions(['category_id' => $this->category_id]))
            ->default($this->group_id)
            ->rules('required', [
                'required' => lp('Template group', 'Cannot be empty')
            ]);
    }

}
