<?php

namespace App\Admin\Forms;

use App\Constants\RedisCacheKeyConstant;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SouGouPush extends Base
{
    public function tabTitle()
    {
        return ll('搜狗推送配置');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $promptContent = Cache::get(RedisCacheKeyConstant::SOUGOU_PUSH_ERROR);
        $prompt        = <<<EOT
prompt = `
<div class="alert alert-danger alert-dismissible" style="height: 85px">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×<\/button>
                <h4><i class="icon fa fa-ban"><\/i> 上次运行时错误提示!,请根据提示进行修改相关参数,如果不进行修改,推送将不会执行,重新提交参数后,将会重新执行<\/h4>

            {$promptContent}
              <\/div>
`

$('.box-body').eq(0).prepend(prompt)
EOT;

        if ($promptContent) {
            \Admin::script($prompt);
        }

        $this->display('amount', '成功推送数量')->with(function () {
            return Config::where('key', 'push_amount')->value('value') ?? 0;
        });
        $states = [
            'on'  => ['value' => 'on', 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 'off', 'text' => '关闭', 'color' => 'danger'],
        ];


        $this->switch('is_open', ll('Is open'))->states($states);


        $this->number('interval', ll('Push interval'))->help('单位: 分钟，每次推送的间隔时间, 10分钟以上')->rules('numeric|min:10');

        $this->textarea('push_args', ll('搜狗·收录参数'))->help('每行一个,格式：site----url生成规则,由于搜狗的限制,每行规则每次推送数量为20个');
        //  $this->textarea('has_add_domains', ll('已经添加的站点'))->help('完成验证的域名(不需要加http),如果需要无限制的推送链接,需要先到搜狗站长平台进行网站验证后完成网站添加,如果不验证,则每次只能推送一个,一天只能推送200个');
        $this->textarea('cookies', ll('搜狗站长平台cookie'))->rows(30)->help('每行一个');

        $this->divider('打码平台');
        $this->divider('打码平台使用的是斐斐打码平台,平台地址为:http://www.fateadm.com/,如需使用搜狗推送,需要先进行注册,充值然后填入对应的相关参数');

        $this->text('app_id', 'App ID');
        $this->text('app_key', 'App Key');

        $this->text('pd_id', 'Pd ID');
        $this->text('pd_key', 'Pd Key');

    }

    public function handle(Request $request)
    {
//        if (count(explode(PHP_EOL, $request->push_args)) > 10) {
//            admin_error('收录参数不能超过10行');
//            return back();
//        }
        $data = $request->all();

        collect($data)->map(function ($v, $k) {
            Config::updateOrCreate([
                'module' => $this->getModule(),
                'key'    => $k
            ], [
                'value' => $v
            ]);
        });

        \Illuminate\Support\Facades\Cache::delete(RedisCacheKeyConstant::CACHE_CONFIGS_KEY . '_' . 'sougoupush' . '_0_0_0');
        admin_success(ll('Update success'));

        Cache::delete(RedisCacheKeyConstant::SOUGOU_PUSH_ERROR);
        return back();
    }

}
