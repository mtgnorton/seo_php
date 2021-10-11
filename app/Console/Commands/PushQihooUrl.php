<?php

namespace App\Console\Commands;

use App\Constants\RedisCacheKeyConstant;
use App\Models\Config;
use App\Services\CommonService;
use App\Services\IndexPregService;
use App\Services\QihooSerivice;
use App\Services\SouGouService;
use App\Services\SpiderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PushQihooUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qihooUrl:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将网址推送到360平台';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $configs = conf('qihoopush');


        if ($configs['is_open'] == 'off') {
            return false;
        }

        /**
         * 上次的推送错误没有解决,不会再次推送
         */
        if (Cache::get(RedisCacheKeyConstant::QIHOO_PUSH_ERROR)) {
            return false;
        }


        if (!$this->checkConfigs($configs)) {
            return false;
        }

        $time         = time();
        $lastPushTime = Cache::get(RedisCacheKeyConstant::QIHOO_PUSH_TIME_KEY);

        // 判断是否到下次推送时间
        if (($time - (int)$lastPushTime) < (60 * (int)$configs['interval'])) {
            return false;
        }


        $args = collect(explode(PHP_EOL, $configs['push_args']))->map('trim')->filter();

        if ($args->isEmpty()) {
            Cache::set(RedisCacheKeyConstant::QIHOO_PUSH_ERROR, '收录参数错误');
            return false;
        }

        $args->map(function ($arg) {

            $urls = [];

            gather_log(sprintf('开始%s规则的推送', $arg));

            list($host, $rule) = explode('----', $arg);

            $baseUrl = rtrim($host, '/') . '/' . ltrim($rule, '/');

            for ($i = 0; $i < 5; $i++) { //每次推送5个
                $urls[] = preg_replace_callback('/{(随机数字|随机字母)+\d*}/', function ($match) {
                    $key  = $match[0];
                    $type = $match[1] ?? '';

                    return IndexPregService::randSystemTag($type, $key);
                }, $baseUrl);
            }


            $rs   = QihooSerivice::flow($urls);


            if (!data_get($rs, 'state')) {

                if (mb_strpos(data_get($rs, 'msg'), '验证码') === false) { //非验证码错误进行记录

                    Cache::set(RedisCacheKeyConstant::QIHOO_PUSH_ERROR, $rs['msg']);
                } else {
                    gather_log('360 自动推送验证码失败');
                }
                return;
            } else {

                $amount = Config::where('key', 'qihoo_push_amount')->value('value') ?? 0;
                $amount += 5;
                conf_insert_or_update('qihoo_push_amount', $amount, 'qihoopush');
            }
            gather_log('360 自动推送验证码开始休眠');

            sleep(20); //限频问题

        });

        Cache::put(RedisCacheKeyConstant::QIHOO_PUSH_TIME_KEY, $time);

    }


    public function checkConfigs($configs)
    {
        if (!data_get($configs, 'app_id') || !data_get($configs, 'app_key') || !data_get($configs, 'pd_id') || !data_get($configs, 'pd_key') || !data_get($configs, 'cookies')) {
            Cache::set(RedisCacheKeyConstant::QIHOO_PUSH_ERROR, '推送配置错误,请检查');
            return false;
        }
        return true;
    }
}
