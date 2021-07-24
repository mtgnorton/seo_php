<?php

namespace App\Admin\Controllers;

use App\Models\Config;
use App\Services\CommonService;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends BaseAuthController
{

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {

        $class = config('admin.database.users_model');

        $form = new Form(new $class());

        $form->display('username', trans('admin.username'));
        // $form->text('name', trans('admin.name'))->rules('required');
//         $form->image('avatar', trans('admin.avatar'));
        $form->password('pass', trans('admin.password'))->rules('confirmed|required');
        $form->password('pass_confirmation', trans('admin.password_confirmation'))->rules('required');

        $form->hidden('password', 'password');
        $form->setAction(admin_url('auth/setting'));

        $form->ignore(['pass', 'pass_confirmation']);

        $form->saving(function (Form $form) {

            if (request()->pass != "") {
                $form->password = Hash::make(request()->pass);
            }
        });

        $form->saved(function () {
            admin_toastr(trans('admin.update_succeeded'));

            return redirect(admin_url('/'));
        });

        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });

        return $form;
    }

    /**
     * Handle a login request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $this->loginValidator($request->all())->validate();

        $credentials = $request->only([$this->username(), 'password']);
        $remember = $request->get('remember', false);

        if ($this->guard()->attempt($credentials, $remember)) {
            // 记录最后一次登录时间和IP
            Config::updateOrCreate([
                'module' => 'user',
                'key' => 'last_logined_at'
            ], [
                'value' => Carbon::now()->toDateTimeString()
            ]);
            // 记录最后一次登录时间和IP
            Config::updateOrCreate([
                'module' => 'user',
                'key' => 'last_logined_ip'
            ], [
                'value' => CommonService::getUserIpAddr()
            ]);

            return $this->sendLoginResponse($request);
        }

        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    /**
     * 忘记密码表单
     *
     * @param Request $request
     * @return void
     */
    public function forgetForm(Request $request)
    {
        return view('admin.forget');
    }

    /**
     * 忘记密码提交
     *
     * @param Request $request
     * @return void
     */
    public function forget(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
            'code' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (conf('auth.code') != $value) {
                        $fail('授权码错误!');
                    }
                }
            ]
        ]);

        $password = Hash::make($request->input('password'));

        $user = Administrator::where('id', 1)->first();
        $user->password = $password;
        $user->save();

        return redirect('admin/auth/login');
    }
}
