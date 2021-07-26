<?php

namespace App\Console\Commands;

use App\Services\CommonService;
use App\Services\IndexPregService;
use App\Services\SpiderService;
use Carbon\Carbon;
use Exception;
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
    protected $description = '将网址推送到百度等平台中';

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
        // 获取配置数据
        $config = conf('qihoopush');

        // 判断是否开启推送
        if ($config['is_open'] != 'on') {
            return false;
        }

        // 获取百度普通收录参数
        $qihooPushData = CommonService::linefeedStringToArray($config['url_format']);
        $sum = count($qihooPushData);
        $successCount = 0;
        $failCount = 0;

        foreach ($qihooPushData as $push) {
            if (empty($push)) {
                $failCount++;

                continue;
            }

            $pushData = explode('----', $push);
            $host = $pushData[0] ?? '';
            $rule = $pushData[1] ?? '';

            // 判断域名是否为空, 且是否存在http://或https://
            if (empty($host)) {
                $failCount++;
                
                continue;
            }
            if (strpos($host, 'http://') !== 0 &&
                strpos($host, 'https://') !== 0
            ) {
                $failCount++;

                continue;
            }

            // 去除域名右边的/
            $host = rtrim($host, '/');
            $url = $host;

            if (!empty($rule)) {
                // 去除规则两边的/
                $rule = trim($rule, '/');
    
                $rule = preg_replace_callback('/{(随机数字|随机字母)+\d*}/', function ($match) {
                    $key = $match[0];
                    $type = $match[1] ?? '';
    
                    return IndexPregService::randSystemTag($type, $key);
                }, $rule);

                $url .= '/'.$rule;
            }

            try {
                $result = CommonService::requestGet($url);

                $successCount++;
            } catch (Exception $e) {
                $failCount++;

                common_log('域名访问失败, 域名为: '.$url, $e);
            }
        }

        common_log('360推送地址总数为:'.$sum.', 推送成功数量为: '.$successCount.', 推送失败数量为: '.$failCount);
    }
}
