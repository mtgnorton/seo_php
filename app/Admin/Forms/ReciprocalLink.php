<?php

namespace App\Admin\Forms;

use App\Constants\RedisCacheKeyConstant;
use App\Constants\SpiderConstant;
use App\Models\Config;
use App\Services\CategoryService;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReciprocalLink extends Base
{
    public function tabTitle()
    {
        return lp();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $states = [
            'on'  => ['value' => 'on', 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 'off', 'text' => '关闭', 'color' => 'danger'],
        ];
        $this->switch('is_open', ll('Is open'))->states($states);
        $this->checkbox('category', lp('Category'))
                ->options(CategoryService::categoryOptions())
                ->canCheckAll();
        $this->number('num', ll('Num'))->default(10);
        $this->select('location', ll('Url location'))->options([
            'index' => '首页',
            'detail' => '内页'
        ])->default('index');
        $this->select('link_text', ll('Reciprocal link text'))->options([
            'keywords' => '关键词库',
            'title' => '标题库',
            'group' => '模板分组名',
        ])->default('group');
    }

    /**
     * The data of the form.
     *
     * @return array $data
     */
    public function data()
    {
        $result = Config::where([
            'module' => $this->getModule(),
        ])->pluck('value', 'key')
        ->toArray();

        foreach ($result as $key => &$val) {
            if (in_array($key, [
                'category',
            ])) {
                $val = implode(',', json_decode($val, true));
            }
        };

        return $result;
    }
}
