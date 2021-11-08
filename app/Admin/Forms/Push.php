<?php

namespace App\Admin\Forms;

use App\Constants\RedisCacheKeyConstant;
use App\Constants\SpiderConstant;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Push extends Base
{
    public function tabTitle()
    {
        return lp('Baidu', 'Push', 'Config');
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
        $this->display('sum', ll('Push success count'))->with(function () {
            return Cache::get(RedisCacheKeyConstant::BAIDU_PUSH_AMOUNT, 0);
        });
        $this->embeds('auto_push', lp('Auto push setting'), function ($form) use ($states) {
            $form->switch('is_open', ll('Is open'))->states($states);
            // $form->text('baidu_normal', ll('Baidu normal push count'))->help(ll('Baidu normal help'));
            // $form->text('baidu_quick', ll('Baidu quick push count'))->help(ll('Baidu quick help'));
            $form->text('interval', ll('Push interval'))->help(ll('Push interval help'));
        });
        $this->textarea('baidu_normal',ll('Platform baidu normal setting'))->help(ll('Platform baidu help'));
        $this->textarea('baidu_quick',ll('Platform baidu quick setting'))->help(ll('Platform baidu help'));
        $this->textarea('push_js',ll('Push js code'))->help(ll('Push js code help'));
    }
}
