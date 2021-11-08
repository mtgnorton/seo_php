<?php

namespace App\Admin\Forms;

use App\Constants\RedisCacheKeyConstant;
use App\Models\group;
use App\Models\Config;
use App\Models\TemplateGroup;
use App\Services\TemplateService;
use Encore\Admin\Admin;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $templateId = request()->input('template_id', 0);
        $this->hidden('group_id')->default($groupId);
        $this->hidden('template_id')->default($templateId);
        $states = [
            'on'  => ['value' => 'on', 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 'off', 'text' => '关闭', 'color' => 'danger'],
        ];
        $this->switch('is_open', lp('Is open', 'Ad'))->states($states);
        // $this->textarea('urls', ll('Url'))->help(ll('Spider url help'));
        // $this->radio('type', lp('Ad', 'Type'))->options([
        //     'system' => ll('System'),
        //     'diy' => ll('Diy')
        // ])->default('system');
        // $this->radio('position', ll('Position'))->options([
        //     'header' => ll('Header'),
        //     'footer' => ll('Footer')
        // ])->default('footer');
        $this->radio('type', ll('Type'))->options([
            'all' => ll('All page'),
            'column' => ll('Ad column'),
            'keyword' => ll('Ad keyword'),
        ])->value('all')->when('all', function (Form $form) {
            $this->radio('ad_type', ll('Ad type'))->options([
                'diy_content' => ll('Diy content'),
                'status_code' => ll('Status code'),
            ])->default('status_code');
            $this->textarea('all_content', lp('Ad content'))
                ->help(ll('Ad diy content help'));
            $this->checkbox('screen_terminal', ll('Screen terminal'))->options([
                'android' => ll('Android'),
                'pc' => ll('Pc'),
                'ios' => ll('Ios')
            ]);
            $this->radio('status_code_type', ll('Status code type'))->options([
                200 => 200,
                403 => 403,
                404 => 404,
                500 => 500,
                502 => 502,
                503 => 503,
                504 => 504,
            ])->default('status_code');
        })->when('column', function (Form $form) {
            $data = $this->data();
            if (!empty($data['column_name'])) {
                foreach ($data['column_name'] as $key => $val) {
                    $this->text('column_name[]', ll('Column tag'))
                        ->default($data['column_name'][$key])
                        ->help(ll('Ad column tag help'));
                    $this->textarea('column_content[]', lp('Ad content'))
                        ->default($data['column_content'][$key])
                        ->help(ll('Ad diy content help'));
                }
            } else {
                $this->text('column_name[]', ll('Column tag'))
                    ->help(ll('Ad column tag help'));
                $this->textarea('column_content[]', lp('Ad content'))
                    ->help(ll('Ad diy content help'));
            }
        })->when('keyword', function (Form $form) {
            $data = $this->data();
            if (!empty($data['keyword'])) {
                foreach ($data['keyword'] as $key => $val) {
                    $this->text('keyword[]', ll('Keyword'))
                        ->default($data['keyword'][$key])
                        ->help(ll('Ad keyword tag help'));
                    $this->textarea('keyword_content[]', lp('Ad content'))
                        ->default($data['keyword_content'][$key])
                        ->help(ll('Ad diy content help'));
                }
            } else {
                $this->text('keyword[]', ll('Keyword'))
                    ->help(ll('Ad keyword tag help'));
                $this->textarea('keyword_content[]', lp('Ad content'))
                    ->help(ll('Ad diy content help'));
            }
        });
        $htmlContent = <<<HTML
<label for="column_name[]" class="col-sm-2  control-label">栏目标识</label>
<div class="col-sm-8">
    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-pencil fa-fw"></i></span>
        <input type="text" id="column_name[]" name="column_name[]" value="aaa" class="form-control column_name__" placeholder="输入 栏目标识" style="width: 40%;">
    </div>
    <span class="help-block">
        <i class="fa fa-info-circle"></i>&nbsp;指定一栏目标识, 如news, 只有访问该栏目才会跳转广告
    </span>
</div>
HTML;

        $jsCode = <<<JS
$(function () {
    $("textarea[name='all_content']").css({width:'40%'});
    $("input[name='column_name[]']").css({width:'40%'});
    $("textarea[name='column_content[]']").css({width:'40%'});
    $("input[name='keyword[]']").css({width:'40%'});
    $("textarea[name='keyword_content[]']").css({width:'40%'});
    $('.cascade-type-column').append("<input style='margin:5px 30px;' type='button' class='btn btn-success' id='addColumn' value='增加栏目'>");
    $('.cascade-type-column').append("<input style='margin:5px 30px;' type='button' class='btn btn-danger delete-category' id='deleteColumn' value='删除栏目'>");
    if ($('#addColumn')) {
        $('#addColumn').click(function () {
            var htmlContent = `<label for="column_name[]" class="col-sm-2  control-label">栏目标识<\/label>
<div class="col-sm-8">
    <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-pencil fa-fw"><\/i><\/span>
        <input type="text" id="column_name[]" name="column_name[]" value="" class="form-control column_name__" placeholder="输入 栏目标识" style="width: 40%;">
    <\/div>
    <span class="help-block">
<i class="fa fa-info-circle"><\/i>&nbsp;指定一栏目标识, 如news, 只有访问该栏目才会跳转广告
<\/span>
<\/div>`;
            var htmlContent1 = `<label for="column_content[]" class="col-sm-2  control-label">广告JS<\/label>
<div class="col-sm-8">
    <textarea name="column_content[]" class="form-control column_content__" rows="5" placeholder="输入 广告JS" style="width: 40%;"><\/textarea>
    <span class="help-block">
        <i class="fa fa-info-circle"><\/i>&nbsp;用户访问跳转, 蜘蛛访问不跳转.
    <\/span>
<\/div>`;
            $('#addColumn').before('<div class="form-group">' + htmlContent + '<\/div>');
            $('#addColumn').before('<div class="form-group">' + htmlContent1 + '<\/div>');
        });
    }
        $('#deleteColumn').click(function () {
            if($('.cascade-type-column').children().length > 4){
                $('.cascade-type-column .form-group').last().remove()
                $('.cascade-type-column .form-group').last().remove()
            }
        });
    
        $('.cascade-type-keyword').append("<input style='margin:5px 30px;' type='button' class='btn btn-success' id='addKeyColumn' value='增加栏目'>");
        $('.cascade-type-keyword').append("<input style='margin:5px 30px;' type='button' class='btn btn-danger delete-category' id='deleteKeyColumn' value='删除栏目'>");
        if ($('#addKeyColumn')) {
        $('#addKeyColumn').click(function () {
            var htmlContent = `<label for="keyword[]" class="col-sm-2  control-label">关键词<\/label>
<div class="col-sm-8">
    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-pencil fa-fw"><\/i><\/span>
        <input type="text" id="keyword[]" name="keyword[]" value="" class="form-control keyword__" placeholder="输入 关键词" style="width: 40%;">
    <\/div>
    <span class="help-block">
        <i class="fa fa-info-circle"><\/i>&nbsp;指定关键词, 标题中含有该关键词时会跳转广告, 
    <\/span>
<\/div>`;
            var htmlContent1 = `<label for="keyword_content[]" class="col-sm-2  control-label">广告JS<\/label>
<div class="col-sm-8">
    <textarea name="keyword_content[]" class="form-control keyword_content__" rows="5" placeholder="输入 广告JS" style="width: 40%;"><\/textarea>
    <span class="help-block">
        <i class="fa fa-info-circle"><\/i>&nbsp;用户访问跳转, 蜘蛛访问不跳转.
    <\/span>
<\/div>`;
            $('#addKeyColumn').before('<div class="form-group">' + htmlContent + '<\/div>');
            $('#addKeyColumn').before('<div class="form-group">' + htmlContent1 + '<\/div>');
        });
    }
    $('#deleteKeyColumn').click(function () {
        if($('.cascade-type-keyword').children().length > 4){
            $('.cascade-type-keyword .form-group').last().remove()
            $('.cascade-type-keyword .form-group').last().remove()
        }
    });
});
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
        $data = $request->except(['group_id', 'template_id']);
        $groupId = $request->input('group_id');
        $templateId = $request->input('template_id', 0);
        // dd($data);
        
        // 将广告内容写入文件
        $group = TemplateGroup::find($groupId);
        $categoryId = $group->category_id;
        // $path = 'ad/'.$groupId . '.html';
        $fileData = [];
        $basePath = 'ad/'.$groupId.'/';

        $content = '';
        if ($data['type'] == 'all') {
            $content = $data['all_content'] ?? '';
            $path = $basePath.'all/all.html';
            $fileData[$path] = $content;
        } else if ($data['type'] == 'column') {
            // $content = $data['column_content'] ?? '';
            $keys = [];
            foreach ($data['column_name'] as $val) {
                $keys[] = $basePath . 'column/column_'.$val.'.html';
            }
            $fileData = array_combine($keys, $data['column_content']);
        } else if ($data['type'] == 'keyword') {
            // $content = $data['keyword_content'] ?? '';
            $keys = [];
            foreach ($data['keyword'] as $val) {
                $keys[] = $basePath . 'keyword/keyword_'.$val.'.html';
            }
            $fileData = array_combine($keys, $data['keyword_content']);
        }

        foreach ($fileData as $fileKey => $fileVal) {
            Storage::disk('public')->put($fileKey, $fileVal);
        }

        collect($data)->map(function ($v, $k) use ($group, $templateId) {
            Config::updateOrCreate([
                'module' => $this->getModule(),
                'group_id' => $group->id,
                'category_id' => $group->category_id,
                'template_id' => $templateId,
                'key' => $k
            ], [
                'value' => $v
            ]);
        });
        $cacheKey = RedisCacheKeyConstant::CACHE_CONFIGS_KEY 
                        . '_' . $this->getModule()
                        . '_' . $categoryId
                        . '_' . $groupId
                        . '_' . $templateId;
        Cache::forget($cacheKey);

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
        $templateId = request()->input('template_id', 0);
        $group = TemplateGroup::find($groupId);

        $result = Config::where([
            'module' => $this->getModule(),
            'group_id' => $groupId,
            'category_id' => $group->category_id,
            'template_id' => $templateId,
        ])->pluck('value', 'key')
        ->toArray();

        foreach ($result as $key => &$val) {
            if (in_array($key, [
                'column_content',
                'column_name',
                'keyword',
                'keyword_content',
                'screen_terminal'
            ])) {
                $val = json_decode($val, true);
            }

            if (in_array($key, [
                'screen_terminal',
            ])) {
                $val = implode(',', $val);
            }
        };

        return $result;
    }
}
