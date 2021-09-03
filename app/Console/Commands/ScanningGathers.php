<?php

namespace App\Console\Commands;

use App\Constants\GatherConstant;
use App\Jobs\GatherJob;
use App\Models\Gather;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ScanningGathers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scanning:gathers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '扫描需要加入队列的采集规则';

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
        $nowMinuteTime = strtotime(date('Y-m-d H:i'));

        $dayBeginTime = strtotime(date('Y-m-d'));

        gather_crontab_log(sprintf('当前分钟时间字符串为:%s,时间戳为:%s', date('Y-m-d H:i:s', $nowMinuteTime), $nowMinuteTime));

        Gather::all()->map(function ($item) use ($dayBeginTime, $nowMinuteTime) {

            if (empty($item->crontab_type) || $item->crontab_type == GatherConstant::CRONTAB_NO) {
                return;
            }
            $settingTime = $dayBeginTime + $item->crontab_hour * 3600 + $item->crontab_minute * 60;

            $isDispatch = '否';
            if ($settingTime == $nowMinuteTime) {
                GatherJob::dispatch($item);
                $isDispatch = '是';
            }

            gather_crontab_log(sprintf('采集名称为:%s,采集设定小时为:%s,设定分钟为:%s,具体时间字符串为:%s,具体时间戳为:%s,是否投递:%s', $item->name, $item->crontab_hour, $item->crontab_minute, date('Y-m-d H:i:s', $settingTime), $settingTime, $isDispatch));

        });
    }


}
