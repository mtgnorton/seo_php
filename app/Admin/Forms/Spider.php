<?php

namespace App\Admin\Forms;

use App\Constants\SpiderConstant;
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
            $form->textarea('urls', lp('Url'))->help(ll('Spider url help'));
        });
        $this->embeds('no_spider', lp('Spider firwall'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            $form->checkbox('type', lp('Spider', 'Type'))
                    ->options(SpiderConstant::allTypeText())
                    ->canCheckAll();
        });
    }
}
