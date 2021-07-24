<?php

namespace App\Admin\Forms;

use App\Constants\SpiderConstant;
use App\Models\Config;
use App\Models\TemplateGroup;
use Encore\Admin\Admin;
use Encore\Admin\Form\Row;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Encore\Admin\Layout\Column;

class Cache extends Base
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
        $this->switch('is_open', ll('Is open'))->states($states);
        $this->switch('spider_open', ll('Spider open'))->states($states);
        $this->embeds('cache_time', lp('Cache time'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->text('index', ll('Index cache time'))->help(ll('Cache time help'));
            $form->text('list', ll('List cache time'))->help(ll('Cache time help'));
            $form->text('detail', ll('Detail cache time'))->help(ll('Cache time help'));
        });
        $jsCode = <<<JS

$(function () {
    // 在三个input框后面加入删除button
    $('#index').css({width:'40%',height:'50px'});
    $('#list').css({width:'40%',height:'50px'});
    $('#detail').css({width:'40%',height:'50px'});
    $('#index').after("<input style='margin:5px 30px;' type='button' class='btn btn-danger' id='deleteIndex' value='立即清除'>");
    $('#list').after("<input style='margin:5px 30px;' type='button' class='btn btn-danger' id='deleteList' value='立即清除'>");
    $('#detail').after("<input style='margin:5px 30px;' type='button' class='btn btn-danger' id='deleteDetail' value='立即清除'>");

    $('#deleteIndex').click(function (item) {
        var groupId = getQueryString('group_id');
        $.ajax({
            url: '/admin/cache/clear?type=index&group_id='+groupId,
            method: 'get',
            dataType: 'json'
        }).done(function (data) {
            console.log(data);
            if (data.code == 0) {
                swal('清空缓存成功', '', 'success');
            } else {
                swal('清空缓存失败', '', 'error');
            }
        }).fail(function (xhr) {
            swal('清空缓存失败', '', 'error');
        });
    });
    $('#deleteList').click(function (item) {
        var groupId = getQueryString('group_id');
        $.ajax({
            url: '/admin/cache/clear?type=list&group_id='+groupId,
            method: 'get',
            dataType: 'json'
        }).done(function (data) {
            console.log(data);
            if (data.code == 0) {
                swal('清空缓存成功', '', 'success');
            } else {
                swal('清空缓存失败', '', 'error');
            }
        }).fail(function (xhr) {
            swal('清空缓存失败', '', 'error');
        });
    });
    $('#deleteDetail').click(function (item) {
        var groupId = getQueryString('group_id');
        $.ajax({
            url: '/admin/cache/clear?type=detail&group_id='+groupId,
            method: 'get',
            dataType: 'json'
        }).done(function (data) {
            console.log(data);
            if (data.code == 0) {
                swal('清空缓存成功', '', 'success');
            } else {
                swal('清空缓存失败', '', 'error');
            }
        }).fail(function (xhr) {
            swal('清空缓存失败', '', 'error');
        });
    });
    function getQueryString(name) {
        var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
        var r = window.location.search.substr(1).match(reg);
        if (r != null) {
            return unescape(r[2]);
        }
        return null;
    }
})
JS;
        Admin::script($jsCode);
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
        $group = TemplateGroup::find($groupId);

        // 获取之前的数据信息
        $condition = [
            'module' => $this->getModule(),
            'group_id' => $groupId,
            'category_id' => $group->category_id,
            'key' => 'is_open',
        ];
        $oldGroup = Config::where($condition)->first();

        if (!empty($oldGroup)) {
            // 如果之前开启缓存现在关闭缓存, 则删除之前的所有缓存
            if ($oldGroup->value == 'on' && $data['is_open'] == 'off') {
                // 判断当前分类下是否有文件
                $path = 'cache/templates/'.$groupId;
                if (Storage::disk('local')->directories($path)) {
                    Storage::disk('local')->deleteDirectory($path);
                }
            }
        }

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
        $categoryId = TemplateGroup::find($groupId)->category_id;

        return Config::where([
            'module' => $this->getModule(),
            'group_id' => $groupId,
            'category_id' => $categoryId
        ])->pluck('value', 'key');
    }
}
