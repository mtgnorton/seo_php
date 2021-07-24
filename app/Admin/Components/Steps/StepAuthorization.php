<?php

namespace App\Admin\Components\Steps;


use App\Models\Config;
use App\Services\Gather\CrawlService;
use Encore\Admin\Widgets\StepForm;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

class Authorization extends StepForm
{
    /**
     * The form title.
     *
     * @var  string
     */
    public $title = '请填写授权信息';

    /**
     * Handle the form request.
     *
     * @param Request $request
     *
     * @return  \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {

        $domain = trim(config('seo.auth_domain'), '/');
        $url    = $domain . '/api/v1/auth_save_domain';

        $res = CrawlService::post($url, [
            'code'   => $request->code,
            'domain' => $request->domain
        ]);

        if ($res['code'] != 200) {
            admin_error($res['message']);
            return $this->prev();
        }

        Config::updateOrInsert(
            [
                'module' => 'auth',
                'key'    => 'code',
            ],
            [
                'value' => $request->code
            ]);
        Config::updateOrInsert(
            [
                'module' => 'auth',
                'key'    => 'domain',
            ],
            [
                'value' => $request->domain
            ]);

        admin_success($res['message']);

        return redirect()->to('admin/auth/login');

    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('code', '授权码')->required()->value(conf('auth.code'));
        $this->text('domain', '授权域名')
            ->required()
            ->help('如www.baidu.com,30天只能绑定一次')
            ->value(conf('auth.domain'));
    }
}
