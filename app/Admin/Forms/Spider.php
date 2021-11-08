<?php

namespace App\Admin\Forms;

use App\Constants\SpiderConstant;
use App\Services\CategoryService;
use Encore\Admin\Form as EncoreForm;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class Spider extends Base
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
        $states = [
            'on'  => ['value' => 'on', 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 'off', 'text' => '关闭', 'color' => 'danger'],
        ];
        $this->embeds('spider_strong_attraction', lp('Strong attraction'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->checkbox('type', lp('Spider', 'Type'))
                    ->options(SpiderConstant::allTypeText())
                    ->canCheckAll();
            $form->checkbox('category', lp('Category'))
                    ->options(CategoryService::categoryOptions())
                    ->canCheckAll();
            $form->switch('is_weight', ll('Is weight'))->states($states)->help(ll('Is weight help'));
            $form->textarea('urls', lp('Url'))->help(ll('Spider url help'));
        });
        $this->embeds('no_spider', lp('Spider firwall'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->radio('type', lp('Spider firwall', 'Type'))
                    ->options(SpiderConstant::forbiddenTypeText());
            $form->checkbox('black_list', ll('Black list'))
                    ->options(SpiderConstant::allTypeText())
                    ->canCheckAll();
            $form->checkbox('white_list', ll('White list'))
                    ->options(SpiderConstant::allTypeText())
                    ->canCheckAll();
        });
    }
}
