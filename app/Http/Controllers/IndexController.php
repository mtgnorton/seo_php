<?php

namespace App\Http\Controllers;

use App\Models\Mirror;
use App\Services\CommonService;
use App\Services\IndexService;
use App\Services\SpiderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IndexController extends Controller
{
    /**
     * 首页方法
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        // 判断uri类型
        $type = IndexService::getUriType();

        // 记录蜘蛛
        SpiderService::spiderRecord($type);

        // 判断是否屏蔽或强引蜘蛛
        $spiderResult = SpiderService::spiderOption();

        if ($spiderResult instanceof RedirectResponse) {
            return $spiderResult;
        }

        $this->mirror();

        // 判断是否存在缓存数据
        $cacheData = IndexService::getCache();

        if (!empty($cacheData)) {
            echo $cacheData;
            die;
        }

        if ($type == 'sitemap') {
            $result = IndexService::siteMap();
        } else if ($type === '') {
            $result = '页面不存在';
        } else {
            // 获取uri对应的模板路径
            $path = IndexService::getPathByUri();
            if ($path !== '') {
                $result = IndexService::index($path, $type);
            } else {
                $result = '页面不存在';
            }
        }

        // 进行缓存
        IndexService::setCache($result);

        echo $result;
    }


    public function mirror()
    {


        $components = parse_url(url()->full());


        $domain = data_get($components, 'host');

        $domainComponents = explode('.', $domain);


        if (strpos($domain, 'www') !== false) {
            return;
        }
        if (count($domainComponents) === 2) {
            return;
        }

        $categoryID = CommonService::getCategoryId($domain);

        if (!$categoryID) {
            return;
        }
        $mirror = Mirror::where('category_id', $categoryID)->first();

        if (!$mirror) {
            return;
        }
        if ($mirror->is_disabled) {
            return;
        }

        $files = Storage::disk('mirror')->files($mirror->id);

        $content = Storage::disk('mirror')->get(collect($files)->random());


        /*字符编码 将gbk 转为*/
        if (strpos($content, 'charset=gbk') !== false) {
            header("content-type:text/html;charset=GBK");
        }
        if (strpos($content, 'charset=gb2312') !== false) {
            header("content-type:text/html;charset=gb2312");
        }


        $scheme     = $components['scheme'];
        $mainDomain = data_get($domainComponents, 1) . '.' . data_get($domainComponents, 2);


        $content = preg_replace_callback('#<a(.*?)href\="[^"]*?"#i', function ($matches) use ($mainDomain, $scheme) {

            $prefix = $scheme . '://' . Str::random(3);
            return '<a' . $matches[1] . 'href="' . $prefix . '.' . $mainDomain . '"';

        }, $content);
        echo $content;
        exit;

    }
}
