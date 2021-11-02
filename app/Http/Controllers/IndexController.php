<?php

namespace App\Http\Controllers;

use App\Models\Mirror;
use App\Services\CommonService;
use App\Services\ConfigService;
use App\Services\IndexService;
use App\Services\MirrorService;
use App\Services\SpiderService;
use App\Services\TemplateService;
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
        // 判断是否屏蔽或强引蜘蛛
        // $spiderResult = SpiderService::spiderOption();

        // if ($spiderResult instanceof RedirectResponse) {
        //     return $spiderResult;
        // }

        // 判断uri类型
        $type = IndexService::getUriType();
        // 获取uri对应的模板路径
        $path = IndexService::getPathByUri($type);

        // 记录蜘蛛
        // SpiderService::spiderRecord($type);

        $this->mirror();
        // 判断是否存在缓存数据
        $result = IndexService::getCache($type);

        if (empty($result)) {
            if ($type == 'sitemap') {
                $result = IndexService::siteMap();
                if (!empty($result)) {
                    echo $result;
                    die;
                }
            } else if ($type === '') {
                abort(404);
            } else {
                if ($path !== '') {
                    $result = IndexService::index($path, $type);
                } else {
                    abort(404);
                }
            }

            // 进行缓存
            IndexService::setCache($result);
        }

        // 添加广告
        $result = IndexService::addAd($result, $path);

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

        if (empty($files)) { //如果没有镜像文件直接返回
            return;
        }
        $content = Storage::disk('mirror')->get(collect($files)->random());


        MirrorService::headerEncoding($content);

        list($scheme, $mainDomain) = MirrorService::parseDomain(url()->full());


        $content = MirrorService::replaceLink($scheme, $mainDomain, $content);
        $content = MirrorService::dtk($mirror, $content);

        echo $content;
        exit;

    }


    /**
     * 获取robots内容
     *
     * @param Request $request
     * @return void
     */
    public function robots(Request $request)
    {

        // 插入段落模板干扰
        $categoryId = CommonService::getCategoryId();
        $groupId    = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();
        // 获取站点配置
        $siteConfig = conf('site', ConfigService::SITE_PARAMS, $categoryId, $groupId);

        $robot = $siteConfig['robots'] ?? '';
        $robot = str_replace(["\r\n", "\r", "\n"], '<br>', $robot);

        echo '<pre>'.$robot.'</pre>';
    }
}
