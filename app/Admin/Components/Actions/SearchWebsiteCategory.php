<?php

namespace App\Admin\Components\Actions;

use App\Services\CategoryService;
use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchWebsiteCategory extends Action
{
    public $name = '分类筛选';

    protected $selector = '.import-text';

    protected $url;

    protected $type;

    public function __construct($url='', $type='')
    {
        parent::__construct();

        $this->url = $url;
        $this->type = $type;
    }

    public function handle(Request $request)
    {
        $url = $request->input('url');
        $data = $request->only([
            'type',
            'category_id'
        ]);
        $query = http_build_query($data);
        $fullUrl = $url . '?' . $query;

        return $this->response()->redirect($fullUrl);
    }

    public function form()
    {
        $this->hidden('url')->default($this->url);
        $this->hidden('type')->default($this->type);
        $this->select('category_id', ll('Category name'))
            ->options(CategoryService::categoryOptions())
            ->rules('required', [
                'required' => ll('Category cannot be empty')
            ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success import-text"><i class="fa fa-search"></i> 网站分类筛选</a>
HTML;
    }
}
