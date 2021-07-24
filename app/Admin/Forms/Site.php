<?php

namespace App\Admin\Forms;

use App\Constants\ConfigConstant;
use App\Models\Config;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class Site extends Base
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
        $categoryId = request()->input('category_id');
        $groupId = request()->input('group_id');
        $this->hidden('group_id')->default($groupId);
        $this->hidden('category_id')->default($categoryId);
        $states = [
            'on'  => ['value' => 'on', 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 'off', 'text' => '关闭', 'color' => 'danger'],
        ];
        $this->embeds('sentence_transform', lp('sentence', 'Transform'), function ($form) use ($states) {
            $form->select('transform_way', lp('Transform', 'Way'))
                    ->options(ConfigConstant::transWayText());
            $form->select('transform_type', lp('Transform', 'Type'))
                    ->options(ConfigConstant::transTypeText());
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->switch('is_ignore_dtk', ll('Ignore dtk'))->states($states);
        });
        $this->switch('content_relevance', ll('Content relevance title'))->states($states);
        $this->switch('unicode_dtk', ll('Unicode dtk'))->states($states);
        $this->switch('ascii_article', ll('Ascii article confound'))->states($states);
        $this->switch('ascii_description', ll('Ascii description confound'))->states($states);

        $this->switch('add_bracket', ll('Add bracket'))->states($states);
        $this->embeds('keyword_chain', lp('Keyword chain'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->text('times', ll('Times'));
        });
        $this->switch('forbin_snapshot', ll('Forbin snapshot'))->states($states);

        $this->embeds('synonym_transform', ll('Synonym transform'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->radio('type', lp('Synonym store', 'Type'))
                    ->options(ConfigConstant::synonymTransTypeText())
                    ->default(ConfigConstant::SYNONYM_TRANSFORM_TYPE_SYSTEM);
            $form->textarea('content', lp('Diy', 'Content'))
                        ->help(ll('Synonym transform rule'));
        });
        $this->embeds('rand_pinyin', lp('Rand pinyin'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->select('type', lp('Insert','Type'))
                    ->options(ConfigConstant::pinyinTypeText());
        });

        $this->embeds('template_disturb', lp('Template disturb'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->select('use_type', lp('Use', 'Type'))
                    ->options(ConfigConstant::transDistrubOpenTypeText());
            $form->select('position_type', lp('Position', 'Type'))
                    ->options(ConfigConstant::transDistrubPositionTypeText());
            $form->textarea('content', lp('Diy', 'Content'));
        });
        $this->switch('is_refresh_change', lp('Is refresh change'))->states($states);
        $this->switch('is_category', lp('Is category syn'))->states($states);
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
        $data = $request->except([
            'category_id',
            'group_id'
        ]);
        $categoryId = $request->input('category_id');
        $groupId = $request->input('group_id');

        collect($data)->map(function ($v, $k) use ($data, $categoryId, $groupId) {
            $condition = [
                'module' => $this->getModule(),
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'key' => $k
            ];
            $updateData = [
                'value' => $v
            ];
            if ($data['is_category'] == 'on') {
                unset($condition['group_id']);

                Config::where($condition)->update($updateData);
            } else {
                Config::updateOrCreate($condition, $updateData);
            }
        });

        admin_success(ll('Update success'));

        $url = '/admin/template-groups?category_id='.$categoryId;

        return redirect($url);
    }

    /**
     * The data of the form.
     *
     * @return array $data
     */
    public function data()
    {
        $categoryId = request()->input('category_id');
        $groupId = request()->input('group_id');

        return Config::where([
            'module' => $this->getModule(),
            'category_id' => $categoryId,
            'group_id' => $groupId,
        ])->pluck('value', 'key');
    }
}
