<?php

namespace App\Console\Commands;

use App\Services\FakerOriginService;
use App\Services\Gather\CrawlService;
use App\Services\IdentifyService;
use App\Services\ImportAndExportService;
use App\Services\SouGouService;
use App\Services\SystemUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use \Goose\Client as GooseClient;
use SVG\Nodes\Shapes\SVGCircle;
use SVG\SVG;
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
        $data = '[北京航空客运业务7月26日全面转场大兴机场 ]
<p class="otitle">（原文章标题：北京航空货物运输业务流程7月26日全方位转换场地北京大兴机场）</p><p>7月26日早上，由北京航空执飞的CA8651飞机航班顺利从北京大兴机场起降，意味着北京航空宣布打开在北京大兴机场的运作，执飞北京大兴来回合肥市、揭阳市广东潮汕、绵阳市、沈阳市、重庆市、江阴6条航道12个飞机航班。</p><p>据统计，做为我国国内航空股权有限责任公司的子公司，北京航空借助中国国航安全性体系管理和資源，在进行公务机有关业务流程的与此同时，积极主动拓展航空服务专业范畴，于2018年根据了我国民用航空局CCAR-121部运作达标核准，并于同一年完成了公共性航空货运的首航。</p><p>北京航空以北京市为产业基地，执飞机场型为波音777-800，转换场地至北京大兴机场后将进一步数据加密北京大兴机场航道互联网。现阶段，除国家公务航空公司业务流程仍在首都国际机场运作外，北京航空货物运输业务流程已所有转至北京大兴机场。</p><p>编写/彭小菲</p><p><img src="https://static.ws.126.net/163/f2e/product/post_nodejs/static/logo.png"><img src="https://static.ws.126.net/cnews/css13/img/end_news.png" alt="netease" width="13" height="12" class="icon">文中来源于：北青报责编：姚文体局_NN1682';


        dump(FakerOriginService::toLength5118($data));
        exit;
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
