<?php

namespace App\Admin\Components\Actions;

use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

/**
 * 上传本地图片
 */
class ImportLocalImage extends Action
{
    public $name;

    protected $selector = '.import-local-image';

    protected $categoryId;

    protected $help;

    public function __construct($categoryId=0, $help='')
    {
        parent::__construct();

        $this->name = ll('Upload local image');

        $this->categoryId = $categoryId;
        $this->help = $help;
    }

    public function handle(Request $request)
    {
        $files = $request->file('files');
        $categoryId = $request->input('category_id');

        $result = ContentService::importLocalImage($files, $categoryId);

        if ($result['code'] != 0) {
            return $this->response()->error($result['message'])->refresh();
        }

        return $this->response()->success($result['message'])->refresh();
    }

    public function form()
    {
        $this->hidden('category_id')->default($this->categoryId);
        $this->multipleImage('files', ll('File'))
                ->options([
                    'showPreview' => false,
                    'maxFileCount' => 0
                ])->rules('required', [
                    'required' => ll('File does not exist')
                ])->help($this->help);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-local-image"><i class="fa fa-upload"></i>{$this->name}</a>
HTML;
    }
}
