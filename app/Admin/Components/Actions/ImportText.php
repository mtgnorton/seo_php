<?php

namespace App\Admin\Components\Actions;

use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class ImportText extends Action
{
    public $name = '导入数据';

    protected $selector = '.import-text';

    protected $type;

    protected $help;

    public function __construct($type='article', $help='')
    {
        parent::__construct();

        $this->type = $type;
        $this->help = $help;
    }

    public function handle(Request $request)
    {
        $file = $request->file('file');
        $categoryId = $request->input('category_id');
        $type = $request->input('type');
        $tagId = $request->input('tag_id', 0);

        $result = ContentService::import($file, $type, $categoryId, $tagId);

        if ($result['code'] != 0) {
            return $this->response()->error($result['message']);
        }

        return $this->response()->success($result['message'])->refresh();
    }

    public function form()
    {
        if ($this->type == 'all') {
            $this->select('type', ll('Content type'))
                ->options(ContentService::CONTENT_TYPE)
                ->rules('required', [
                    'required' => ll('Type cannot be empty')
                ]);
        } else {
            $this->hidden('type')->default($this->type);
        }
        // 如果是自定义, 则加入自定义标签的选择
        if ($this->type == 'diy') {
            $this->select('tag_id', ll('Tag name'))
                ->options(ContentService::tagOptions(['type'=>'diy']))
                ->rules('required', [
                    'required' => ll('Tag cannot be empty')
                ]);
        } else {
            $this->select('category_id', ll('Category name'))
                ->options(ContentService::categoryOptions(['type' => $this->type]))
                ->rules('required', [
                    'required' => ll('Category cannot be empty')
                ]);
        }

        $this->file('file', '请选择文件')->help($this->help)
                ->rules('required', [
                    'required' => ll('File does not exist')
                ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-text"><i class="fa fa-upload"></i> 导入数据</a>
HTML;
    }
}
