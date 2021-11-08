<?php

namespace App\Admin\Controllers;


use App\Admin\Components\Renders\DynamicOutput;
use App\Admin\Forms\Concurrent;
use App\Models\Test;
use App\Services\NginxRequestLimitService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Tab;

class ConcurrentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = '并发设置';
    }

    public function index(Content $content)
    {
        $form = [
            'concurrent' => Concurrent::class,
        ];

        $html = $content
            ->title(lp("", ''))
            ->body(Tab::forms($form));

        $html = $html->render();
        $html = str_replace('<section class="content">', '<section class="content content_nav_default">', $html);
        return $html;
    }


    public function benchmark()
    {

        if (request()->isMethod('get')) {
            $form = new Form(new Test());

            $form->display('t1', '提示')->with(function () {
                return '1. 系统会根据服务器性能测试的结果,自动推荐一个最优的每秒并发数量';
            });
            $form->display('t2', '  ')->with(function () {
                return '2. 服务器性能测试时间大约为10秒';
            });
            $form->display('t3', '注意事项')->with(function () {
                return '1. 频繁进行性能测试会影响服务器性能,仅建议在服务器配置发生变动时进行';
            });
            $form->display('t4', ' ')->with(function () {
                return '2. 为保证服务器性能测试数据准确,建议打开蜘蛛防火墙,屏蔽所有蜘蛛';
            });
            $form->setAction('/admin/cpu-benchmark');

            return DynamicOutput::get($form);

        }

        DynamicOutput::post();
        force_notify('服务器性能测试中,请稍候');

        $score = NginxRequestLimitService::getCpuPerformance();


        $number = intval($score / 67);
        force_notify("当前服务器性能测试分值为{$score}分,推荐并发数量为{$number}个/S");


    }
}
