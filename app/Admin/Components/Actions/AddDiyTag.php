<?php

namespace App\Admin\Components\Actions;

use App\Services\ContentService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddDiyTag extends RowAction
{
    public $name;

    /**
     * 分类模型对象
     */
    public $category;

    public function __construct()
    {
        $this->name = ll('Add tag');

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        $categoryId = $model->id;
        $tag = $request->input('tag');

        $result = ContentService::addDiyTag($tag, (int)$categoryId);

        if ($result) {
            return $this->response()->success(ll('Update success'))->refresh();
        }
        
        return $this->response()->success(ll('Update fail'))->refresh();
    }

    /**
     * 表单内容
     *
     * @return void
     */
    public function form()
    {
        $this->text('tag', ll('Tag name'))
            ->help(ll('Need not {}'))
            ->rules(["required", "unique:tags"], [
                'unique' => ll('Tag name unique'),
                'required' => lp('Tag name', 'cannot be empty'),
            ]);
    }

}
