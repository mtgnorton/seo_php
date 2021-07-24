<?php

namespace App\Admin\Forms;

use App\Models\group;
use App\Models\Config;
use App\Models\TemplateGroup;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Ad extends Base
{
    public function tabTitle()
    {
        return lp('', '');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $groupId = request()->input('group_id');
        $this->hidden('group_id')->default($groupId);
        $states = [
            'on'  => ['value' => 'on', 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 'off', 'text' => '关闭', 'color' => 'danger'],
        ];
        $this->switch('is_open', lp('Is open', 'Ad'))->states($states);
        $this->textarea('urls', ll('Url'))->help(ll('Spider url help'));
        $this->radio('type', lp('Ad', 'Type'))->options([
            'system' => ll('System'),
            'diy' => ll('Diy')
        ])->default('system');
        // $this->radio('position', ll('Position'))->options([
        //     'header' => ll('Header'),
        //     'footer' => ll('Footer')
        // ])->default('footer');
        $this->textarea('diy_content', lp('Diy', 'Content'))
            ->help(ll('Ad diy content help'));
    }

    /**
     * Handle the form request.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {
        $data = $request->except('group_id');
        $groupId = $request->input('group_id');

        // 将广告内容写入文件
        $group = TemplateGroup::find($groupId);
        $path = 'ad/'.$groupId . '.html';
        Storage::disk('public')->put($path, $data['diy_content']);

        collect($data)->map(function ($v, $k) use ($group) {
            Config::updateOrCreate([
                'module' => $this->getModule(),
                'group_id' => $group->id,
                'category_id' => $group->category_id,
                'key' => $k
            ], [
                'value' => $v
            ]);
        });

        admin_success(ll('Update success'));

        return back();
    }

    /**
     * The data of the form.
     *
     * @return array $data
     */
    public function data()
    {
        $groupId = request()->input('group_id');
        $group = TemplateGroup::find($groupId);

        return Config::where([
            'module' => $this->getModule(),
            'group_id' => $groupId,
            'category_id' => $group->category_id,
        ])->pluck('value', 'key');
    }
}
