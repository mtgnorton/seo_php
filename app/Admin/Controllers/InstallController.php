<?php

namespace App\Admin\Controllers;


use App\Admin\Components\Steps\StepAuthorization;

use App\Admin\Components\Steps\Business;
use App\Admin\Components\Steps\Database;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\Gather;
use App\Models\Image;
use App\Services\Gather\CrawlService;
use Encore\Admin\Admin;

use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\MultipleSteps;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Facades\Admin as AdminFacade;

class InstallController extends Controller
{
//    public function index(Content $content)
//    {
//
//        $userModel = config('admin.database.users_model');
//        Auth::setUser(new $userModel());
//
//        $style = <<<EOT
//.main-header{
//   display:none;
//}
//.main-sidebar{
//   display:none;
//}
//.content-wrapper{
//margin:0px;
//}
//.breadcrumb{
//   display:none;
//}
//
//EOT;
//        Admin::style($style);
//
//
//        $steps = [
//            'authorization' => StepAuthorization::class,
////            'database'      => Database::class,
////            'Business'      => Business::class,
//
//        ];
//
//        return $content
//            ->title('授权')
//            ->body(MultipleSteps::make($steps));
//    }


    public function form(Content $content)
    {
        AdminFacade::disablePjax();

        if (Auth::check() && (time() < auth_effective_time())) {

        } else {
            $userModel = config('admin.database.users_model');
            Auth::setUser(new $userModel());

            $style = <<<EOT
.main-header{
   display:none;
}
.main-sidebar{
   display:none;
}
.content-wrapper{
margin:0px;
}
.breadcrumb{
   display:none;
}
.alert{
    margin-bottom: 0;
}
EOT;
            Admin::style($style);
        }


        $form = new  Form(new Gather());
        $form->tools(function (Form\Tools $tools) {

            // 去掉`列表`按钮
            $tools->disableList();

            // 去掉`删除`按钮
            $tools->disableDelete();

            // 去掉`查看`按钮
            $tools->disableView();

        });


        $form->footer(function ($footer) {

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();

        });

        $form->setAction('/admin/install-save');
        $form->text('code', '授权码')->required()->value(conf('auth.code'));
        $form->text('domain', '授权域名')
            ->required()
            ->help('如www.baidu.com,30天只能绑定一次')
            ->value(conf('auth.domain'));

        $form->ignore(['code', 'domain']);


        return $content
            ->title('授权')
            ->description('请填写授权信息')
            ->body($form);

    }


    public function formSave()
    {
        $request = request();


        if ($request->pjax()) {
            Cache::put('code', $request->code);
            Cache::put('domain', $request->domain);
            return;
        }


        $authCode   = Cache::get('code');
        $authDomain = Cache::get('domain');


        if (stripos($authDomain, 'http') !== false) {
            admin_error('域名不能包含http');
            return redirect()->to('/admin/install');
        }

        $domain = trim(config('seo.auth_domain'), '/');
        $url    = $domain . '/api/v1/auth_save_domain';

        $res = CrawlService::post($url, [
            'code'   => $authCode,
            'domain' => $authDomain
        ]);


        if (!$res) {
            admin_error('请求授权失败');
            return redirect()->to('/admin/install');
        }

        if ($res['code'] != 200) {
            admin_error($res['message']);
            return redirect()->to('/admin/install');

        }

        if (conf('auth.code') != $authCode) { //如果两次授权码不同,重新保存授权开始时间和有效期

            Config::updateOrInsert(
                [
                    'module' => 'auth',
                    'key'    => 'use_begin_time',
                ],
                [
                    'value' => data_get($res, 'data.use_begin_time')
                ]);
            Config::updateOrInsert(
                [
                    'module' => 'auth',
                    'key'    => 'effective_days',
                ],
                [
                    'value' => data_get($res, 'data.days')
                ]);
        }

        Config::updateOrInsert(
            [
                'module' => 'auth',
                'key'    => 'code',
            ],
            [
                'value' => $authCode
            ]);
        Config::updateOrInsert(
            [
                'module' => 'auth',
                'key'    => 'domain',
            ],
            [
                'value' => $authDomain
            ]);


        admin_success($res['message']);

        Cache::put('pass_auth', 1, 5);

        \Encore\Admin\Facades\Admin::guard()->loginUsingId(1);

        return redirect()->to('/admin/auth/setting');

    }


}
