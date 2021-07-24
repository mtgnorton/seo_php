<?php

namespace App\Console\Commands;

use App\Services\Gather\CrawlService;
use App\Services\ImportAndExportService;
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

//        $this->getVerifyCode();
        $this->getVerifyCode();

    }

    public function fake()
    {
       $str ='<p>调整后的演出时间及地点将另行通知，由此带来的不便和困扰，我们深表歉意。</p><img src="//n.sinaimg.cn/ent/transform/300/w550h550/20210721/eded-d302613677a85ee6de31fb2772eb9a5d.jpg" alt="蔡徐坤2021个人巡演南京站延期" longdesc="http://n.sinaimg.cn/ent/transform/300/w550h550/20210721/eded-d302613677a85ee6de31fb2772eb9a5d.jpg" data-link="http://n.sinaimg.cn/ent/transform/300/w550h550/20210721/eded-d302613677a85ee6de31fb2772eb9a5d.jpg">蔡徐坤2021个人巡演南京站延期<p style="text-align: left;">　　新浪娱乐讯 7月21日，蔡徐坤[微博]工作室发布2021巡回演唱会延期公告。公告显示，由于南京疫情突发，为响应国家卫健委关于疫情管理的要求与号召，避免人员聚集引发交叉感染，保障所有观众的健康与安全，原定于2021年7月31日于南京青奥体育公园体育馆举办的‘蔡徐坤 ‘迷’2021巡回演唱会-南京站”延期。调整后的演出时间及地点将另行通知，由此带来的不便和困扰，我们深表歉意。</p> ◀
<p style="text-align: left;">　　同时，已购买南京站演出门门票的观众，用户订单将统一由官方购票平台办理原路退款（不做保留），退款于7个工作日原路退回支付账户。</p>';

       dump(fake_origin($str));
       exit;


    }


    public function submit()
    {

        $data = [
            'code'      => "8jye",
            'email'     => "851426308@qq.com",
            'reason'    => "",
            'site_type' => 1,
            'sites'     => ["https=>//www.xyyseo.com/index/home/product.html", "https://www.xyyseo.com/index/home/news.html"],
            'urls'      => "https://www.xyyseo.com/index/home/product.html\nhttps://www.xyyseo.com/index/home/news.html",
        ];

        $url = 'https://zhanzhang.sogou.com/api/feedback/addMultiShensu';

        $rs = CrawlService::setOptions('', [
            'CURLOPT_COOKIE' => $this->cookie()
        ])->post($url, $data, true);

        dump($rs);
        exit;
    }

    public function getVerifyCode()
    {


        $rs = CrawlService::setOptions('', [
            'CURLOPT_COOKIE' => $this->cookie()
        ])->get('https://zhanzhang.sogou.com/api/user/generateVerifCode?timer=1626945845906');


        $image = SVG::fromString($rs);

        $rasterImage = $image->toRasterImage(100, 100);
        header('Content-Type: image/png');

        imagepng($rasterImage, './aa.png');

    }

    public function cookie()
    {
        return $cookie = 'IPLOC=CN3713; SUID=218ADA1B1431A40A0000000060CC63DC; SUV=1624007645139936; LCLKINT=4771; LSTMV=293%2C829; ssuid=3193141950; __ZHANZHANG_SID__=HSz9AFgJd7gOWiyYo3FzdOJT9DrzG7bb; __ZHANZHANG_SID__.sig=PlQFfuKqk3_iSP0NwhmeUJ-SuDo; ppinf=5|1626663144|1627872744|dHJ1c3Q6MToxfGNsaWVudGlkOjQ6MTExOXx1bmlxbmFtZTowOnxjcnQ6MTA6MTYyNjY2MzE0NHxyZWZuaWNrOjA6fHVzZXJpZDoxOToxNTcyNjIwNDY2M0AxNjMuY29tfA; pprdig=xARSwhgD_ZXkFu4qCkuFP4VO5tSEYY5IouSfaRHt-gx8-sfdtyA-LQAjsEhSw7SSkviYMS8hV9HISaRF588wVpOnrYRHBEBC5Hilh10fMYUl3AlfZZno7n6A1ZFZqtyRUD1FhiFFAxuu1O_rwREI79COcTvdcWw-nnOVBZN_BJM; ppinfo=0e472a900a; passport=5|1626663144|1627872744|dHJ1c3Q6MToxfGNsaWVudGlkOjQ6MTExOXx1bmlxbmFtZTowOnxjcnQ6MTA6MTYyNjY2MzE0NHxyZWZuaWNrOjA6fHVzZXJpZDoxOToxNTcyNjIwNDY2M0AxNjMuY29tfA|49f6967691|xARSwhgD_ZXkFu4qCkuFP4VO5tSEYY5IouSfaRHt-gx8-sfdtyA-LQAjsEhSw7SSkviYMS8hV9HISaRF588wVpOnrYRHBEBC5Hilh10fMYUl3AlfZZno7n6A1ZFZqtyRUD1FhiFFAxuu1O_rwREI79COcTvdcWw-nnOVBZN_BJM; sgid=27-53008989-AWD06Oia55FiaNnxGRODMWib1Y; fe_uname=15726204663; username.sig=UJeuGQqCalJUqXuaDjsoNP8Sd1Q; user_id=4858215; user_id.sig=eAwkXK7xobOXtcYWEyaTfqaI8p0; SNUID=1C48F3691A1FDEABCFA8F5671A0D2C1E; ppmdig=16269458030000000755717f1d47b23de7cdf4a866f22c7e; sgid=27-53008989-AWD5ORicF166Q3xtMLyzxibB8; sgid.sig=hRE0_cjzJAMmmmkylJu2f8VcLf0; username=15726204663; show_wxapp=0';;
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
