<?php

namespace App\Console\Commands;

use App\Services\CommonService;
use App\Services\ServerMonitorService;
use Illuminate\Console\Command;

class CheckDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:disk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查服务器硬盘是否已满, 如果已满, 则清理';

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
        common_log('开始检查服务器是否已满');
        $clearPercent = conf('all_cache.clear_percent', '95');

        $obj = new ServerMonitorService();
        $result = $obj->getExecDisk();

        $percent = $result['percent'] ?? 0;
        $percentData = str_replace('%', '', $percent);

        if ($percentData > $clearPercent) {
            common_log('服务器已满, 使用比率为: '.$percentData.', 开始进行缓存的清除');
            CommonService::clearCacheAll([2]);
            common_log('缓存清除完毕');
        } else {
            common_log('服务器使用比率为: '.$percentData.', 正常使用中');
        }
    }
}
