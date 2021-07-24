<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use App\Constants\AuthCodeConstants;
use Encore\Admin\Admin as EncoreAdmin;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Column;

Form::forget(['map', 'editor']);

// 富文本编辑器
Form::extend("fullEditor", \App\Admin\Components\Fields\FullEditor::class);
Form::extend("textareaHtml", \App\Admin\Components\Fields\TextAreaHtml::class);

// 覆盖视图文件
app('view')->prependNamespace('admin', resource_path('views/admin'));

Column::extend('customModal', \App\Admin\Components\Actions\CustomModal::class);

Grid::init(function (Grid $grid) {
    $grid->model()->orderBy('id', 'desc');
    $grid->actions(function (Grid\Displayers\Actions $actions) {
        $actions->disableView();
    });
    $grid->disableExport();
    $grid->disableColumnSelector();

    $grid->filter(function (Grid\Filter $filter) {
        // $filter->expand();
    });
});
Form::init(function ($footer) {

    // 去掉`查看`checkbox
    $footer->disableViewCheck();

    // 去掉`继续编辑`checkbox
    $footer->disableEditingCheck();

    // 去掉`继续创建`checkbox
    $footer->disableCreatingCheck();

});
// 右侧栏版本内容
Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {
    // 获取授权时间和剩余天数

    // 判断账户类型是一年还是永久
//    $effectiveType = conf('auth.effective_type');
//    if ($effectiveType == AuthCodeConstants::EFFECTIVE_TYPE_ONE_YEAR) {
//        $expiredTime = date('Y-m-d', strtotime("+1years", $beginTime));
//    } else if ($effectiveType == AuthCodeConstants::EFFECTIVE_TYPE_ALL) {
//        $expiredTime = '永久';
//    } else {
//        $expiredTime = '未知';
//    }

    $expiredTime = date('Y-m-d', auth_effective_time());
    $html        = <<<HTML
<span style="color:white; float:left; padding-top: 15px; padding-right: 15px">授权到期时间: {$expiredTime}</span>
HTML;

    $newVersion = get_remote_latest_version();

// 获取当前版本
    $version = conf('update.version');
    if ($newVersion != $version) {
        $html .= <<<HTML
<a style="color:white; float:left; padding-top: 15px" href="/admin/system-update-migration">当前版本: $version</a>
HTML;
    } else {
        $html .= <<<HTML
<span style="color:white; float:left; padding-top: 15px">当前版本: 最新版</span>
HTML;
    }

    $navbar->right($html);
});


// 设置favicon
EncoreAdmin::favicon('/favicon.png');

Admin::js('vendor/laravel-admin-ext/chartjs/Chart.bundle.min.js');


$path = request()->path();

$fileBaseName = str_replace('admin/', '', $path);

$commonScriptPath = '/custom/js/common.js';
if (is_file(public_path() . $commonScriptPath)) { //全局js文件

    $file = file_get_contents(public_path() . $commonScriptPath);
    Admin::script($file);
}


$scriptPath = '/custom/js/' . $fileBaseName . '.js';
if (is_file(public_path() . $scriptPath)) { //当前页面js文件

    $file = file_get_contents(public_path() . $scriptPath);
    Admin::script($file);
}


$cssCommonPath = '/custom/css/common.css';
if (is_file(public_path() . $cssCommonPath)) { //全局css文件

    $file = file_get_contents(public_path() . $cssCommonPath);
    Admin::style($file);
}


$cssPath = '/custom/css/' . $fileBaseName . '.css'; //当前页面css文件
if (is_file(public_path() . $cssPath)) {
    $file = file_get_contents(public_path() . $cssPath);
    Admin::style($file);
}




