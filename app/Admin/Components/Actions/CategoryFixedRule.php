<?php

namespace App\Admin\Components\Actions;

use App\Models\Category;
use App\Models\CategoryRule;
use Encore\Admin\Actions\RowAction;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryFixedRule extends RowAction
{
    public $name;

    /**
     * 分类模型对象
     *
     * @var App\Models\Category
     */
    public $category;

    public function __construct(Category $category)
    {
        $this->name = ll('Category fixed rule');
        $this->category = $category;

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        $categoryId = $request->input('category_id');
        $data = $request->input('data');

        DB::beginTransaction();

        try {
            foreach ($data as $key => $val) {
                $condition = [
                    'category_id' => $categoryId,
                    'type' => 'fixed',
                    'route_tag' => $key,
                ];
    
                $rule = CategoryRule::firstOrCreate($condition);
                $rule->rule = $val;
                $rule->save();
            }

            DB::commit();
    
            return $this->response()->success(ll('Category rule update success'))->refresh();
        } catch (Exception $e) {
            DB::rollBack();
            common_log('随机分类规则修改失败', $e);

            return $this->response()->success(ll('Category rule update fail'))->refresh();
        }
    }

    /**
     * 表单内容
     *
     * @return void
     */
    public function form()
    {
        $rules = CategoryRule::where([
            'category_id' => $this->category->id,
            'type' => 'fixed'
        ])->get();
        $this->hidden('category_id')->default($this->category->id);

        foreach ($rules as $rule) {
            // 文本域输入框
            $this->textarea("data[{$rule->route_tag}]", $rule->route_tag)->value($rule->rule);
        }
    }

}
