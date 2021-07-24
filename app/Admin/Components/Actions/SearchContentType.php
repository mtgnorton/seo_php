<?php

namespace App\Admin\Components\Actions;

use App\Constants\ContentConstant;
use App\Services\CategoryService;
use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchContentType extends Action
{
    public $name = '物料库类型';

    protected $selector = '.import-text';

    protected $url;

    protected $categoryId;

    public function __construct($url='', $categoryId=0, $type = 0)
    {
        parent::__construct();

        $this->url = $url;
        $this->categoryId = $categoryId;
        $this->type = $type;
    }

    public function handle(Request $request)
    {
        $url = $request->input('url');
        $data = $request->only([
            'type',
        ]);
        $data['category_id'] = $request->input('categoryId');
        $query = http_build_query($data);
        $fullUrl = $url . '?' . $query;

        return $this->response()->redirect($fullUrl);
    }

    public function form()
    {
        $this->hidden('url')->default($this->url);
        $this->hidden('categoryId')->default($this->categoryId);
        $this->select('type', ll('Content type'))
            ->options(ContentConstant::tagText())
            ->rules('required', [
                'required' => lp('Content type', 'Cannot be empty')
            ])->default($this->type);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-text"><i class="fa fa-search"></i> 物料库类型筛选</a>
HTML;
    }
}
