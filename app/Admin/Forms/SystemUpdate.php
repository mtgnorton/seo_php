<?php

namespace App\Admin\Forms;

use App\Constants\SpiderConstant;
use App\Models\Gather;
use App\Models\Version;
use App\Models\Config;
use App\Services\Gather\CrawlService;
use App\Services\SystemUpdateService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class SystemUpdate extends Base
{
    public function tabTitle()
    {
        return '系统更新';
    }

    public function handle(Request $request)
    {
        set_time_limit(0);

        try {
            $data = get_remote_all_version_info();
        } catch (\Exception $e) {
            admin_error('系统更新检测失败');
            return back();
        }
        if (!$data) {
            admin_error('当前已是最新版本');
            return back();
        }
        $data                  = array_reverse($data);
        $totalFileAmount       = 0;
        $totalSqlSuccessAmount = 0;
        $totalSqlErrorAmount   = 0;
        $waitClearCacheFiles   = [];

        foreach ($data as $item) {

            if (version_compare(conf('update.version', '1.0.0'), $item['version'], '<')) {


                if (!$packURL = data_get($item, 'package')) {
                    admin_error('更新链接不存在,暂时无法更新');
                    return back();
                }
                $rs = SystemUpdateService::update($packURL);
                if (!$rs['state']) {
                    admin_error($rs['message']);
                    return back();
                }

                system_update_log('系统更新测试 ' . $item['version']);

                conf_insert_or_update('version', $item['version'], 'update');
                conf_insert_or_update('desc', $item['desc'], 'update', true);

                Version::create([
                    'number' => $item['version'],
                    'desc'   => $item['desc']
                ]);
                $totalFileAmount       += $rs['amount'];
                $totalSqlSuccessAmount += $rs['sql_success_amount'];
                $totalSqlErrorAmount   += $rs['sql_error_amount'];
                array_push($waitClearCacheFiles, ...data_get($rs, 'php_files', []));
            }
        }

        foreach ($waitClearCacheFiles as $waitClearCacheFile) { //重启相应文件的opcache缓存

            $isClear = function_exists('opcache_invalidate') && opcache_invalidate($waitClearCacheFile, true);
            system_update_log(sprintf('清空%s文件的opcache缓存,结果为%s', $waitClearCacheFile, $isClear));
        }
        exec('/usr/local/bin/php /data/wwwroot/seo_php/artisan queue:restart', $output); //重启队列

        system_update_log('队列重启日志如下', $output);


        /*生产坏境缓存相关*/
        $rs = exec('sh /data/wwwroot/seo_php/production_clear.sh', $output, $v);

        system_update_log('production_clear日志如下', compact('rs', 'output', 'v'));

        $rs = exec('sh /data/wwwroot/seo_php/production.sh', $output, $v);

        system_update_log('production日志如下', compact('rs', 'output', 'v'));


        admin_success(ll(sprintf('更新成功,共更新%s个文件,sql更新成功的数量为%s,sql更新失败的数量为%s', $totalFileAmount, $totalSqlSuccessAmount, $totalSqlErrorAmount)));
        return back();
    }

    /**
     * Build a form here.
     */
    public function form()
    {

        $version = get_remote_latest_version();

        $html = <<<HTML
var h = `<div class="form-group ">
    <label class="col-sm-2  control-label">最新版本号<\/label>
    <div class="col-sm-8">
        <div class="box box-default no-margin">
            <div class="box-body">
            $version
            <button class="btn btn-warning">立即升级</button>
            <\/div>
        <\/div>
    <\/div>
<\/div>`

$('.fields-group .form-group:nth-child(1)').after(h);
HTML;

        $script = <<<HTML
    $('.fields-group').addClass('system_update_form');
HTML;
        Admin::script($script);

        $nowVersion = conf_without_cache('version', 'update', '1.0.0');

        if (version_compare(conf('update.version', '1.0.0'), $version, '<')) {

            $this->display('version', '当前版本')->with(function ($value) use ($nowVersion) {
                return $nowVersion;
            });
            Admin::script($html);

            $this->textarea('desc', ll('版本描述'))->with(function ($value) {
                return $this->getVersionsDesc();
            })->disable();
        } else {
            $this->display('version', '当前版本')->with(function ($value) use ($nowVersion) {
                return $nowVersion;
            });
            $this->display('t', '最新版本号')->with(function ($value) {
                return '已是最新版本';
            });
            $this->textarea('desc', ll('版本描述'))->with(function ($value) {
                return $this->getVersionsDesc();
            })->disable();

        }
        $this->disableReset();

        $this->disableSubmit();


    }

    private function getVersionsDesc()
    {
        return Version::all()->reverse()->reduce(function ($initial, $item) {
            return $initial . "版本号为:{$item['number']} 更新描述:{$item['desc']}\r\n";
        }, '');
    }


    public function handleGit(Request $request)
    {

        if (is_win()) {
            admin_error(ll('win not support update'));
            return back();

        }

        if (!function_exists('exec')) {
            admin_error(ll('未开启exec函数,无法更新'));
            return back();
        }

        $output = '';
        $status = '';

        $rs = exec('sudo git reset --hard;sudo git pull 2>&1', $output, $status);

        gather_log($rs . $output . $status);

        if (strpos($rs, 'Already up-to-date') !== false) {
            admin_success('已经是最新版本');
            return back();

        }

        //  conf_insert_or_update('version', get_remote_latest_version(), 'update', true);


        admin_success(ll('更新成功') . '更新信息为:' . data_get($output, 0));
        return back();
    }

}
