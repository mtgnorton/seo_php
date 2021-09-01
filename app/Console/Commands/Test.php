<?php

namespace App\Console\Commands;

use App\Jobs\GatherJob;
use App\Models\Config;
use App\Models\Gather;
use App\Models\GatherCrontabLog;
use App\Services\DatabaseService;
use App\Services\FakerOriginService;
use App\Services\FileService;
use App\Services\Gather\CrawlService;
use App\Services\IdentifyService;
use App\Services\ImportAndExportService;
use App\Services\NginxRequestLimitService;
use App\Services\SouGouService;
use App\Services\SystemUpdateService;
use App\Services\ZipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use \Goose\Client as GooseClient;

use Tim168\SearchEngineRank\SearchEngineRank;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test';

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

        GatherJob::dispatch(Gather::find(72));
        GatherJob::dispatch(Gather::find(72));
        GatherJob::dispatch(Gather::find(72));
        GatherJob::dispatch(Gather::find(72));
//        GatherJob::dispatchNow(Gather::find(72));
//        GatherJob::dispatchNow(Gather::find(72));
//        GatherJob::dispatchNow(Gather::find(72));
    }


    public function baiduRank()
    {
        //关键字
        $keyword = '头疗养发';

//查询的页码
        $page = 1;

//查询的网址
        $url = 'https://touliaojun.com';

//代理ip（若不设置，默认用本地ip）
        // $proxy = "112.245.21.58:548";

//超时时间
        $timeout = 5;

        $rank = SearchEngineRank::getRank(\Tim168\SearchEngineRank\Enum\SearchEngineEnum::PC_BAI_DU, $keyword, $page, '', $url, $timeout);

    }
}
