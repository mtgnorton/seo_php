<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Config;
use App\Models\SpiderRecord;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\TemplateModule;
use App\Models\TemplateModulePage;
use App\Models\Website;
use Illuminate\Support\Facades\Storage;
use App\Services\TemplateService;
use Exception;
use Illuminate\Support\Facades\Cache;
use sqhlib\Hanzi\HanziConvert;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 前端服务类
 *
 * Class IndexService
 * @package App\Services
 */
class IndexService extends BaseService
{
    const UNEABLED_URL = 'template/404.html';

    const TITLE_COOKIE_KEY = 'back_url_title';

    const CACHE_TIME = 86400;

    /**
     * 首页方法
     *
     * @param string $path  路径
     * @param string $uriType 类型
     * @return void
     */
    public static function index(string $path, $uriType = 'index')
    {
        $content = self::readFile($path);
        $baseUrl = request()->url();

        // 替换头部和尾部标签
        $content = self::headFootReplace($content);

        // 循环标签
        $content = self::repeatTag($content);

        // 插入段落模板干扰
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();
        // 获取站点配置
        $siteConfig = conf('site', ConfigService::SITE_PARAMS, $categoryId, $groupId);

        // 插入段落模板干扰
        $content = self::insertDistrub($content, $siteConfig);

        // 记录标题内容
        $globalData = [];
        
        // 提前获取所需的数据
        self::getBatchRedisData($globalData, $baseUrl);

        // 记录标题拆分内容
        $content = self::titleValue($content, $globalData, $uriType);

        // 记录句子的数量
        if ($uriType == 'list' || $uriType == 'detail') {
            self::getSentenceCount($content, $globalData);
        }

        if ($uriType == 'detail') {
            // 记录关键词内链数量
            self::getKeywordTimes($content, $globalData);
            // 记录标题关联内容数量
            self::getTitleRelevanceTimes($content, $globalData);
        }

        // 记录连接和标题的对应
        $content = self::putUrlTitle($content, $uriType, $globalData);

        // 提前替换描述ascii码, 防止标签内容也被ascii化
        $content = self::asciiDescription($content, $siteConfig);

        // 每一次单独替换
        $content = self::replaceAllTag($content, $uriType, $globalData);

        // 如果存在关联词内链连接, 则将地址写入
        $aTagUrlData = $globalData['aTagUrlData'] ?? [];
        if (!empty($aTagUrlData)) {
            redis_batch_set($aTagUrlData);
        }

        // 将句子文章缓存数据写入
        self::setBatchRedisData($globalData, $baseUrl);
        
        // 替换摘要
        $summary = $globalData['summary_data'] ?? '';
        // 如果开启ascii码description, 则将摘要内容ascii化
        $summary = self::summaryAsciiDescription($summary, $siteConfig);
        $content = str_replace('{摘要}', $summary, $content);

        // 站点配置
        $content = self::siteConfig($content, $uriType, $siteConfig);

        // 记录url和标题
        // self::addUrlTitle($content);

        // 添加广告
        // $content = self::addAd($content, $path);

        // 添加推送js
        $content = self::addPushJs($content);

        // 添加网站互链
        $content = self::reciprocalLink($content, $uriType, $categoryId);

        // 删除页面中不需要存在的缓存值
        self::delPageCache($siteConfig);

        return $content;
    }

    /**
     * 记录标题拆分后的内容
     *
     * @param string $content   内容
     * @param array $globalData 全局变量数组
     * @return string
     */
    public static function titleValue($content, &$globalData, $uriType)
    {
        $content = preg_replace_callback("#<title>(.*?)</title>#", function ($match) use (&$globalData, $uriType) {
            $titleVal = self::replaceAllTag($match[1], $uriType, $globalData, 'title');
            $titleVal = self::replaceAllTag($titleVal, $uriType, $globalData);
            if (!empty($titleVal)) {
                $titleArr = mb_str_split($titleVal, 2);
                if (!empty($titleArr)) {
                    shuffle($titleArr);
                }
                $globalData['title_value'] = $titleArr;
            }

            return "<title>".$titleVal."</title>";
        }, $content);

        return $content;
    }

    /**
     * 插入干扰模板
     *
     * @param string $content
     * @param array $siteConfig
     * @return void
     */
    public static function insertDistrub($content, $siteConfig)
    {
        $disturbConfig = $siteConfig['template_disturb'];
        
        if ($disturbConfig['is_open'] == 'on') {
            // 判断内容调取方式
            // $disturbContent = '';
            // if ($disturbConfig['use_type'] == 'open_system') {
            $disturbContent = ContentService::getTemplateDisturb();
            // } else if ($disturbConfig['use_type'] == 'open_diy') {
            //     $disturbContent = $disturbConfig['content'];
            // }

            // 判断插入位置
            if ($disturbConfig['position_type'] == 'header') {
                if (strpos($content, '<!DOCTYPE html>') !== false) {
                    $content = str_replace_once('<!DOCTYPE html>', '<!DOCTYPE html>'.$disturbContent, $content);
                } else if (strpos($content, '<!doctype html>') !== false) {
                    $content = str_replace_once('<!doctype html>', '<!doctype html>'.$disturbContent, $content);
                } else {
                    $content = $disturbContent . $content;
                }
            } else if ($disturbConfig['position_type'] == 'footer') {
                $content .= $disturbContent;
            }
        }

        return $content;
    }

    /**
     * 替换头部尾部标签
     *
     * @param string $content
     * @return string
     */
    public static function headFootReplace($content)
    {
        $result = preg_replace_callback('/{(头部标签|尾部标签)}/', function ($match) {
            $tag = $match[1] ?? '';

            $html = TemplateService::getBaseHtml($tag);

            return $html;
        }, $content);

        return $result;
    }

    /**
     * 替换所有标签
     *
     * @param [type] $content
     * @return void
     */
    public static function replaceAllTag($content, $uriType, &$globalData=[], $callType='')
    {
        // $globalData = [];
        // $relatedKey = request()->url() . '_related_words';
        $relatedKey = 'related_words';


        $result = preg_replace_callback_array([
            // // 头部尾部系列
            // '/{(头部标签|尾部标签)}/' => function ($match) {
            //     $tag = $match[1] ?? '';

            //     $content = TemplateService::getBaseHtml($tag);

            //     return $content;
            // },
            // 系统标签系列
            '/{(随机数字|随机字母|时间|固定数字|固定字母|当前网址)+\d*}/' => function ($match) use (&$globalData, $relatedKey, $callType) {
                $key = $match[0];
                $type = $match[1] ?? '';

                if (in_array($type, ['固定数字', '固定字母'])) {
                    // 判断数组里是否存在
                    if (!array_key_exists($key, $globalData)) {
                        $globalData[$key] = IndexPregService::randSystemTag($type, $key);
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        $globalData[$relatedKey] = $globalData[$key];
                    }

                    return $globalData[$key];
                }

                $tagResult = IndexPregService::randSystemTag($type, $key);
                // 判断该页面相关词是否已存在
                if ($callType == 'title') {
                    $globalData[$relatedKey] = $tagResult;
                }

                return $tagResult;
            },
            // 内容系列
            '/{[^{]*(文章题目|标题|网站名称|栏目|句子|图片|视频|关键词|文章内容)+\d*}/' => function ($match) use (&$globalData, $uriType, $relatedKey, $callType) {
                $key = $match[0];
                $type = $match[1] ?? '';

                $typeData = IndexPregService::getContentInfo($type, $key);

                if ($typeData['is_number'] != 0) {
                    // 判断数组里是否存在
                    if (!array_key_exists($key, $globalData)) {
                        $globalData[$key] = IndexPregService::randContent($type, $key, $uriType, $globalData);
                    }
                    if (is_array($globalData[$key])) {
                        $value = $globalData[$key]['value'];
                    } else {
                        $value = $globalData[$key];
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        $globalData[$relatedKey] = $value;
                    }

                    return $value;
                } else {
                    $value = IndexPregService::randContent($type, $key, $uriType, $globalData);
                    if (is_array($value)) {
                        $value = $value['value'];
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        $globalData[$relatedKey] = $value;
                    }

                    return $value;
                }
            },
            // 其他自定义标签
            '/{([^{\r\n}]+)\d*}/' => function ($match) use (&$globalData, $callType, $relatedKey) {
                $tag = $match[0];
                $noNumTag = $match[1];
                $noNumTag = preg_replace_callback("/[\D]+(\d+)/", function ($match) {
                    $result = rtrim($match[0], $match[1]);

                    return $result;
                }, $noNumTag);
                if ($noNumTag == '摘要') {
                    return $tag;
                }
                $isNumber = IndexPregService::diyHasNumber($tag);
                if ($noNumTag == '相关词') {
                    if ($callType == 'title') {
                        return $tag;
                    }

                    // 判断缓存中是否已存在该值
                    $keyword = $globalData[$relatedKey] ?? '';
                    if (!empty($keyword)) {
                        if ($isNumber) {
                            // 判断数组里是否存在
                            if (!array_key_exists($tag, $globalData)) {
                                $globalData[$tag] = CommonService::getBaiduDropdownWords($keyword);
                            }

                            return $globalData[$tag];
                        } else {
                            return CommonService::getBaiduDropdownWords($keyword);
                        }
                    } else {
                        return '';
                    }
                }

                if ($isNumber) {
                    // 判断数组里是否存在
                    if (!array_key_exists($tag, $globalData)) {
                        $globalData[$tag] = IndexPregService::randDiyContent($tag);
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        $globalData[$relatedKey] = $globalData[$tag];
                    }

                    return $globalData[$tag];
                } else {
                    $tagResult = IndexPregService::randDiyContent($tag);
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        $globalData[$relatedKey] = $tagResult;
                    }

                    return $tagResult;
                }
            },
        ], $content);

        return $result;
    }

    /**
     * 获取当前使用模板信息
     *
     * @return void
     */
    public static function template()
    {
        // 获取使用中的模板的路径
        $template = Template::with('category')
                            ->first();

        return $template->category->tag . '/' .
                $template->group->tag . '/' .
                $template->tag;
    }

    /**
     * 随机获取不同类型下的文件
     *
     * @param string $type  类型
     * @return void
     */
    public static function randFile(string $type)
    {
        $path = self::template();
        if ($path[mb_strlen($path) - 1] !== '/') {
            $path .= '/';
        }
        $fullPath = 'template/' . $path . $type;

        $files = Storage::disk('public')->files($fullPath);
        $randKey = count($files)-1 >=0 ? count($files) -1 : 0;
        $randIndex = mt_rand(0, $randKey);

        $file = $files[$randIndex] ?? '';

        if (Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->get($file);
        } else {
            return '';
        }
    }

    /**
     * 读取文件内容
     *
     * @param string $path  路径
     * @param string $disk  驱动
     * @return string
     */
    public static function readFile(string $path, string $disk = 'public')
    {
        // $key = $disk . $path;
        // $result = Cache::get($key);
        // if (is_null(Cache::get($key))) {
        if (Storage::disk($disk)->exists($path)) {
            $result = Storage::disk($disk)->get($path);
        } else {
            $result = '';
        }

        //     Cache::put($key, $result, self::CACHE_TIME);
        // }

        return $result;
    }

    /**
     * 判断当前uri类型
     *
     * @return void
     */
    public static function getUriType()
    {
        // // 获取当前域名信息
        // $host = $_SERVER["HTTP_HOST"];
        $uri = $_SERVER["REQUEST_URI"];

        // // 根据host的值判断是否已绑定域名
        // $website = Website::where([
        //     'url' => $host,
        //     'is_enabled' => 1
        // ])->first();
        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($templateId)) {
            return '';
        }

        // 判断是否是网站地图
        $result = preg_match('/^sitemap\d*.xml$/', trim($uri,'/'));
        if ($result) {
            return 'sitemap';
        }

        if ($uri === '/' ||
            $uri === '/index.php' ||
            $uri === '/index.html'
        ) {
            $type = 'index';
        } else {
            // $ext = mb_substr($uri, -5, 5);
            $extArr = explode('.', $uri);
            $ext = array_pop($extArr);
            $materialExts = CommonService::MATERIAL_EXTS;
            // dd($ext);

            if ($ext === 'html') {
                $type = 'detail';
            } else if (in_array($ext, $materialExts)) {
                $type = '';
            } else {
                $type = 'list';
            }
        }

        return $type;
    }

    /**
     * 获取当前路由下绑定的路径
     *
     * @return string
     */
    public static function getPathByUri($type = '')
    {
        // 获取当前域名信息
        $uri = $_SERVER["REQUEST_URI"];

        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($type)) {
            $type = self::getUriType();
        }

        // 判断页面类型
        // 1.首页
        if ($type === 'index') {
            $module = TemplateModule::where([
                'template_id' => $templateId,
                'route_tag' => '/'
            ])->first();

            if (empty($module)) {
                return '';
            }

            $files = Storage::disk('public')->files($module->path);

            $randKey = count($files)-1 >=0 ? count($files) -1 : 0;
            $randIndex = mt_rand(0, $randKey);
            $fullPath = $files[$randIndex] ?? '';

            return $fullPath;
        }

        $uriArr = explode('/', trim($uri, '/'));
        $cpUrlArr = $uriArr;
        // $count = count($uriArr);

        $moduleId = 0;
        $selfModule = 0;

        // 循环查询uri
        // for ($i = 0; $i < $count; $i++) {
        //     $tempUri = self::fillStringByLine(implode('/', $uriArr)) . $type . '/';
        //     // dump($tempUri);

        //     // 判断该uri在模板中是否有对应
        //     $module = TemplateModule::where([
        //         'template_id' => $templateId,
        //         'route_tag' => $tempUri
        //     ])->first();

        //     // dump($module);

        //     if (empty($module)) {
        //         array_pop($uriArr);
        //         continue;
        //     }

        //     $moduleId = $module->id;
        //     break;
        // }
        // dd($moduleId);

        // 查询一级栏目
        // if ($moduleId == 0) {
        foreach ($cpUrlArr as $key => $url) {
            $routeTag = self::fillStringByLine($url);
            $oneModule = TemplateModule::where([
                'template_id' => $templateId,
                'route_tag' => $routeTag . $type . '/',
                'level' => 1
            ])->first();
            unset($uriArr[$key]);

            if (!empty($oneModule)) {
                $moduleId = $selfModule = $oneModule->id ?? 0;
                break;
            } else {
                if ($type == 'detail') {
                    $listModule = TemplateModule::where([
                        'template_id' => $templateId,
                        'route_tag' => $routeTag . 'list/',
                        'level' => 1
                    ])->first();
                    if (!empty($listModule) &&
                        !$listModule->children->isEmpty()
                    ) {
                        $moduleId = $listModule->id ?? 0;
                        break;
                    }
                }
            }
        }
        // }

        // 如果没有匹配的页面就返回默认
        if ($moduleId == 0) {
            $moduleId = TemplateService::getDefaultModule($templateId, $type);
            if (empty($moduleId)) {
                return '';
            }
        } else {
            // 判断url规则是否符合子类
            if ($type == 'detail') {
                array_pop($uriArr);
                $module = TemplateModule::where([
                    'template_id' => $templateId,
                    'route_tag' => $routeTag . 'list/',
                    'level' => 1
                ])->first();
                if (!empty($module) &&
                    !$module->children->isEmpty()
                ) {
                    $moduleId = $module->id ?? 0;
                    
                    $moduleId = self::getTrueModule($moduleId, $uriArr, $type);
                } else {
                    $moduleId = $selfModule;
                }
            } else {
                $moduleId = self::getTrueModule($moduleId, $uriArr, $type);
            }
        }
        // 如果moduleId为0, 则直接将selfModule的值返回
        if (empty($moduleId)) {
            $moduleId = $selfModule;
        }

        // 如果没有匹配的页面就返回默认
        if ($moduleId == 0) {
            $moduleId = TemplateService::getDefaultModule($templateId, $type);
            if (empty($moduleId)) {
                return '';
            }
        }

        $pages = TemplateModulePage::where('module_id', $moduleId)
                            ->get()->toArray();

        if (empty($pages)) {
            return '';
        }

        $pageCount = count($pages) - 1;
        $page = $pages[mt_rand(0, $pageCount)] ?? [];

        return $page['full_path'] ?? '';
    }

    /**
     * 获取真实的模块ID(循环子模块)
     *
     * @param int $moduleId     模块ID
     * @param string $type      类型
     * @param array $uriArr     地址数组
     * @param int $templateId   模板ID
     * @return int
     */
    public static function getTrueModule($moduleId, $uriArr, $type)
    {
        $uriArr = array_filter($uriArr);
        if (empty($uriArr)) {
            if ('detail' == $type) {
                $finalListData = TemplateModule::find($moduleId);
                if (empty($finalListData)) {
                    return $moduleId;
                }
                $finalTypeData = TemplateModule::where([
                    'parent_id' => $finalListData->parent_id,
                    'column_tag' => $finalListData->column_tag,
                    'type' => $type,
                    'template_id' => $finalListData->template_id ?? 0,
                ])->first();
    
                $moduleId = $finalTypeData->id ?? $moduleId;
            }

            return $moduleId;
        }
        $tempType = 'list';
        static $finalId = 0;
        $finalId = $moduleId;
        // dd($moduleId, $uriArr);
        $module = TemplateModule::find($finalId);
        // 获取第一个tag
        $parentTag = $module->column_tag ?? '';
        $parentTemplateId = $module->template_id ?? 0;
        $tempTag = array_shift($uriArr);
        if (!empty($parentTag)) {
            $tag = $parentTag . '/' . $tempTag;
        } else {
            $tag = $tempTag;
        }

        $child = TemplateModule::where([
            'type' => $tempType,
            'column_tag' => $tag,
            'template_id' => $parentTemplateId,
            'parent_id' => $finalId,
        ])->first();

        if (!empty($child)) {
            $finalId = $child->id;

            return self::getTrueModule($finalId, $uriArr, $type);
        }

        if ($tempType != $type) {
            $finalListData = TemplateModule::find($finalId);
            if (empty($finalListData)) {
                return $moduleId;
            }
            $finalTypeData = TemplateModule::where([
                'parent_id' => $finalListData->parent_id,
                'column_tag' => $finalListData->column_tag,
                'type' => $type,
                'template_id' => $finalListData->template_id ?? 0,
            ])->first();

            $finalId = $finalTypeData->id ?? $moduleId;
        }

        return $finalId ?: $moduleId;
    }

    /**
     * 用 / 填充字符串左右两边
     *
     * @param string $str   字符串
     * @return void
     */
    public static function fillStringByLine(string $str)
    {
        if ($str === '') {
            return '';
        }

        if ($str[0] !== '/') {
            $str = '/' . $str;
        }

        if ($str[mb_strlen($str) - 1] !== '/') {
            $str .= '/';
        }

        return $str;
    }

    /**
     * 网站地图
     *
     * @return string
     */
    public static function siteMap()
    {
        // 模板ID
        $templateId = TemplateService::getWebsiteTemplateId();

        $tags = TemplateModule::where('template_id', $templateId)
                            ->where('column_tag', '<>', '')
                            ->whereHas('pages')
                            ->groupBy('column_tag')
                            ->pluck('column_tag')
                            ->toArray();

        $newHost = request()->getSchemeAndHttpHost();
        if ($newHost[mb_strlen($newHost) -1] !== '/') {
            $newHost .= '/';
        }
        header("Content-Type: text/xml");
        $map = "\t<urlset>\r\n";
        $date = date('Y-m-d');
        for ($i=0; $i<1000; $i++) {
            $tmp = self::getRandUrl($newHost, $tags);
            $map .= "\t\t<url>\n";
            $map .= "\t\t\t<loc>{$tmp}</loc>\r\n";
            $map .= "\t\t\t<priority>{$date}</priority>\r\n";
            $map .= "\t\t\t<lastmod>daily</lastmod>\r\n";
            $map .= "\t\t\t<changefreq>0.8</changefreq>\r\n";
            $map .= "\t\t</url>\n";
        }
        $map .= "\t</urlset>";

        return $map;
    }

    /**
     * 获取随机链接
     *
     * @param string $host  域名
     * @param array $tags  可用栏目
     * @return void
     */
    public static function getRandUrl(string $host, array $tags)
    {

        if (empty($tags)) {
            $tag = '';
        } else {
            $randKey = count($tags)-1 >=0 ? count($tags) -1 : 0;
            $tag = $tags[mt_rand(0, $randKey)] ?? '';
        }

        // $tagArr = array_filter(explode('/', $tag));
        // $column = array_shift($tagArr) ?? '';
        // $type = array_shift($tagArr) ?? '';
        $type = mt_rand(1,2);
        $ext = '/';

        if ($type == 1) {
            $ext = '.html';
        }
        
        return $host . $tag . '/' . mt_rand(100000, 999999) . $ext;
    }

    /**
     * 增加cookie中增加标题
     *
     * @param string $content
     * @return void
     */
    public static function addCookieTitle(string $content)
    {
        $title = self::TITLE_COOKIE_KEY;
        $cookieTitle = <<<HTML
    <script>
        // $(function () {
        //     $("a").click(function () {
        //         var title = $(this).html();
        //         var url = $(this).attr('href');
        //         if (url.length > 5) {
        //             var ext = url.substr(url.length-5);
        //             if (ext === '.html') {
        //                 document.cookie = "{$title} = " + title;
        //             }
        //         }
        //     });
        //
        window.onload = function () {
        var aObj = document.querySelector('a');
        if (aObj) {
            aObj.addEventListener('click', function (e) {
                var content = this.innerHTML;
                var rule = /<h9>(.+)<\/h9>/;
                var result = rule.exec(content)
                if (result) {
                    var title = result[1];
                } else {
                    var title = '';
                }
                var url = this.href;
                var key = window.btoa(url + "_{$title}");

                if (url.length > 5) {
                    var ext = url.substr(url.length-5);
                    if (ext === '.html') {
                        document.cookie = key + "=" + title;
                    }
                }
            });
        }
        }
    </script>
HTML;

    return $content . $cookieTitle;
    }

    /**
     * 添加地址标题数据
     *
     * @param string $content
     * @return void
     */
    public static function addUrlTitle(string $content)
    {
        $crawler = new Crawler($content);

        $crawler->filter('a')->each(function (Crawler $node, $i) {
            if (count($node->filter('h9')) > 0) {
                $title = $node->filter('h9')->text();

                $url = url($node->attr('href'));

                if (!empty($url) && !empty($title)) {
                    $key = $url;
                    $title = $title;

                    Cache::put($key, $title, self::CACHE_TIME);
                }
            }
        });
    }

    /**
     * 站点配置
     *
     * @param string $content
     * @return string
     */
    public static function siteConfig(string $content, $type, $siteConfig=[])
    {
        // // 获取当前域名信息
        // $host = request()->getHost();
        // // 根据host的值判断是否已绑定域名
        // $website = Website::where([
        //     'url' => $host,
        //     'is_enabled' => 1
        // ])->first();
        if (empty($siteConfig)) {
            $categoryId = CommonService::getCategoryId();
            $groupId = TemplateService::getGroupId();
    
            $siteConfig = conf('site', ConfigService::SITE_PARAMS, $categoryId, $groupId);
        }

        // 0.1 判断是否同义词转换
        $synonymConfig = $siteConfig['synonym_transform'];
        $insetType = $synonymConfig['insert_type'] ?? 'sentence';
        if ($synonymConfig['is_open'] == 'on' && $insetType == 'site') {
            // static $diyData = null;
            // if (is_null($diyData)) {
            //     $diyData = $synonymConfig['content'];
            //     $diyData = CommonService::storeStringToArray($diyData);
            // }
            $systemData = CommonService::getSynonyms();
            // if ($synonymConfig['type'] == 'system') {
            $storeData = $systemData;
            // } else if ($synonymConfig['type'] == 'diy') {
            //     $storeData = $diyData;
            // } else {
            //     $storeData = array_merge($systemData, $diyData);
            // }

            $content = strtr($content, $storeData);
        }

        // 1. 句子库转换类型是否开启
        $num = 0;
        $tempData = [];
        $sentenceConfig = $siteConfig['sentence_transform'];
        $title = '';
        //
        if ($sentenceConfig['is_open'] == 'on' &&
            $sentenceConfig['transform_type'] == 'site'
        ) {
            // 简体转繁体
            if ($sentenceConfig['transform_way'] == 'simp2trad') {
                // 将html中的链接和图片地址数据暂存下来
                $content = preg_replace_callback_array([
                    '/(href|src)[=\"\'\s]+([^\"\']*)[\"\']?[^>]*>/' => function ($preg) use (&$num, &$tempData) {
                        $key = "{temp_variable".$num."}";
                        $value = $preg[0];
                        $tempData[$key] = $value;
                        $num++;

                        return $key;
                    },
                    '#<title>(.*?)</title>#' => function ($preg) use (&$num, &$tempData, $sentenceConfig, &$title) {
                        $title = $preg[1];
                        if ($sentenceConfig['is_ignore_dtk'] == 'on') {
                            $key = "{temp_variable".$num."}";
                            $value = $preg[0];
                            $tempData[$key] = $value;
                            $num++;

                            return $key;
                        } else {
                            return $preg[0];
                        }
                    },
                    "#<meta *name *= *[\"\'] *keywords *[\"\'] *content *= *[\"\'](.*?)[\"\'] */* *>#i" => function ($preg) use (&$num, &$tempData, $sentenceConfig) {
                        if ($sentenceConfig['is_ignore_dtk'] == 'on') {
                            $key = "{temp_variable".$num."}";
                            $value = $preg[0];
                            $tempData[$key] = $value;
                            $num++;

                            return $key;
                        } else {
                            return $preg[0];
                        }
                    },
                    "#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */* *>#i" => function ($preg) use (&$num, &$tempData, $sentenceConfig) {
                        if ($sentenceConfig['is_ignore_dtk'] == 'on') {
                            $key = "{temp_variable".$num."}";
                            $value = $preg[0];
                            $tempData[$key] = $value;
                            $num++;

                            return $key;
                        } else {
                            return $preg[0];
                        }
                    },
                ], $content);
                // 是否忽略DTK
                $content = HanziConvert::convert($content, true);

                $content = str_replace(array_keys($tempData), array_values($tempData), $content);
            } else if ($sentenceConfig['transform_way'] == 'cn2en') {

            }
        }

        // // 判断是否开启标题关联内容
        // if ($siteConfig['content_relevance'] == 'on' &&
        //     $type == 'detail'
        // ) {
        //     // 获取标题
        //     preg_match("#<title>(.*?)</title>#", $content, $titleMatch);
        //     if (!empty($titleMatch)) {
        //         $title = $titleMatch[1];
        //     }
        //     if (!empty($title)) {
        //         // 获取标题的分词
        //         $titleArr = mb_str_split($title, 2);

        //         // 打乱标题
        //         shuffle($titleArr);

        //         if (empty(!$titleArr)) {
        //             $contentResult = CommonService::getHtmlContent($content);
        //             foreach ($contentResult as $key => $val) {
        //                 $afterContent = '';
        //                 $contentArr = mb_str_split($val);
        //                 if (empty($contentArr)) {
        //                     continue;
        //                 }
        //                 foreach ($contentArr as $value) {
        //                     $afterContent .= $value;
        //                     if (!empty($titleArr)) {
        //                         if (mt_rand(1, 10) <= 2) {
        //                             $titleStr = array_pop($titleArr);
        //                             $afterContent .= $titleStr;
        //                         }
        //                     }
        //                 }

        //                 // 替换
        //                 $content = str_replace($val, $afterContent, $content);
        //             }
        //         }
        //     }
        // }

        // 判断是否开启Unicode编码
        if ($siteConfig['unicode_dtk'] == 'on') {
            // 将html中的链接和图片地址数据暂存下来
            $content = preg_replace_callback_array([
                '#<title>(.*?)</title>#' => function ($preg) {
                    return str_replace($preg[1], CommonService::unicodeEncode($preg[1]), $preg[0]);
                },
                "#<meta *name *= *[\"\'] *keywords *[\"\'] *content *= *[\"\'](.*?)[\"\'] */* *>#i" => function ($preg)  {
                    return str_replace($preg[1], CommonService::unicodeEncode($preg[1]), $preg[0]);
                },
                "#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */* *>#i" => function ($preg)  {
                    return str_replace($preg[1], CommonService::unicodeEncode($preg[1]), $preg[0]);
                },
            ], $content);
        }

        // 判断是否开启描述ascii转换
        // $asciiConfig = $siteConfig['ascii'] ?? [];
        // $asciiDescription = $asciiConfig['ascii_description'] ?? 'off';
        // if ($asciiDescription == 'on') {
        //     $content = preg_replace_callback('#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */* *>#i', function ($match) {
        //         $afterDescription = '';
        //         $beforeDescription = $match[1];
        //         $descriptionArr = mb_str_split($beforeDescription, 2);
        //         foreach ($descriptionArr as $value) {
        //             $afterDescription .= $value;
        //             if (mt_rand(1, 10) <= 2) {
        //                 $ascStr = CommonService::getSpecAscii();
        //                 $afterDescription .= $ascStr;
        //             }
        //         }

        //         return str_replace($beforeDescription, $afterDescription, $match[0]);
        //     }, $content);
        // }

        // 判断是否插入拼音
        $pinyinConfig = $siteConfig['rand_pinyin'] ?? [];
        $pinyinState = $pinyinConfig['is_open'] ?? 'off';
        $pinyinType = $pinyinConfig['type'] ?? '';
        $pinyinRate = $pinyinConfig['pinyin_rate'] ?? 0;
        if (!empty($pinyinConfig)) {
            if ($pinyinState == 'on' && $pinyinType == 'site') {
                $contentResult = CommonService::getHtmlContent($content);
                foreach ($contentResult as $key => $val) {
                    $contentArr = mb_str_split($val);
                    $afterContent = '';
                    foreach ($contentArr as $value) {
                        $afterContent .= $value;
                        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $value)) {
                            $rate = mt_rand(1, 100);
                            if ($rate <= $pinyinRate) {
                                // 获取拼音
                                $tempValue = HanziConvert::convert($value);
                                $pinyin = Pinyin::getAllPy($tempValue)[0] ?? '';
                                if (!empty($pinyin)) {
                                    $afterContent .= '('.$pinyin.')';
                                }
                            }
                        }
                    }

                    $content = str_replace($val, $afterContent, $content);
                }
            }
        }

        // 判断网站是否禁止快照
        if ($siteConfig['forbin_snapshot'] == 'on') {
            $forbinData = ContentService::getForbinSnapshotContent();

            $content = $forbinData . $content;
        }

        // 判断是否开启百变模板
        if ($siteConfig['versatile_template'] ?? 'off' == 'on') {
            $content = preg_replace_callback("#<div.*?class=[\'\"](.*?)[\'\"](((?!id).)*?) *?>#", function ($preg) {
                $whole = $preg[0];
                $class = $preg[1];

                $randNum = mt_rand(1, 10);
                $base = '!#%$_';
                // 一半添加类名, 一半添加id
                if ($randNum <= 5) {
                    $randName = IndexPregService::randCode(mt_rand(3,9), 3);
                    $newClass = $class . ' ' . $base . $randName;

                    return str_replace($class, $newClass, $whole);
                } else {
                    $randId = $base . IndexPregService::randCode(mt_rand(3,9), 3);
                    $id = " id='$randId'";

                    return str_replace('>', $id . '>', $whole);
                }
            }, $content);
        }

        return $content;
    }

    /**
     * 添加广告
     *
     * @param string $content
     * @return void
     */
    public static function addAd(string $content, string $fullPath='')
    {
        // 判断当前用户是蜘蛛还是用户, 如果是用户, 则插入广告, 蜘蛛则不插入
        if (empty(SpiderService::getSpider())) {
            // 获取当前网站分类ID
            $categoryId = CommonService::getCategoryId();
            $groupId = TemplateService::getGroupId();
            $templateId = TemplateService::getWebsiteTemplateId();
            $adConfig = conf('ad', null, $categoryId, $groupId, $templateId);
            if (empty($adConfig)) {
                $adConfig = conf('ad', ConfigService::AD_PARAMS, $categoryId, $groupId);
            }
            if (empty($adConfig) || $adConfig['is_open'] == 'off' || empty($adConfig['is_open'])) {
                return $content;
            }

            $path = '';
            // 判断广告类型
            // 栏目
            if ($adConfig['type'] == 'column') {
                $column = $adConfig['column_name'] ?? [];
                $page = TemplateModulePage::where('full_path', $fullPath)->with('module')->first();

                $nowColumn = $page->module ? $page->module->column_tag : '';

                if (!in_array($nowColumn, $column)) {
                    return $content;
                }

                $path = 'ad/'.$groupId.'/column/column_'.$nowColumn.'.html';
            } else if ($adConfig['type'] == 'keyword') {
                $keyword = $adConfig['keyword'] ?? [];
                if (empty($keyword)) {
                    return $content;
                }
                preg_match('#<title>(.*?)</title>#', $content, $match);

                if (empty($match)) {
                    return $content;
                }
                $title = $match[1] ?? '';

                $nowKeyword = '';
                foreach ($keyword as $val) {
                    if (empty($val)) {
                        continue;
                    }
                    if (strpos($title, $val) !== false) {
                        $nowKeyword = $val;
                        break;
                    }
                }
                if (empty($nowKeyword)) {
                    return $content;
                }

                $path = 'ad/'.$groupId.'/keyword/keyword_'.$nowKeyword.'.html';
            } else if ($adConfig['type'] == 'all') {
                $adType = $adConfig['ad_type'] ?? 'diy_content';
                if ($adType == 'diy_content') {
                    $path = 'ad/'.$groupId . '/all/all.html';
                } else {
                    $code = $adConfig['status_code_type'] ?? 500;
                    abort($code);
                }

            } else {
                return $content;
            }

            if (!empty($path)) {
                $adContent = '';
                if (Storage::disk('public')->exists($path)) {
                $filePath = '/storage/'.$path;
                    $adContent = <<<HTML
<iframe src="{$filePath}" style="width:100%; height:100%;position: fixed;top: 0;left:0;z-index: 99999;"/>;
HTML;
                }
                $content = $adContent . $content;
            }
        }



        return $content;
    }

    /**
     * 添加推送的js代码
     *
     * @param string $content
     * @return void
     */
    public static function addPushJs($content)
    {
        $pushConfig = conf('push');
        if ($pushConfig['auto_push']['is_open'] == 'on') {
            // 获取push_js代码
            $pushJs = '<script>'.conf('push.push_js', '').'</script>';

            return $content . $pushJs;
        } else {
            return $content;
        }
    }

    /**
     * 获取缓存数据
     *
     * @return string
     */
    public static function getCache($type = '')
    {
        // 获取页面类型
        if (empty($type)) {
            $type = self::getUriType();
        }

        // 如果是sitemap, 则不获取缓存
        if ($type == 'sitemap') {
            return '';
        }

        // 判断系统是否开启缓存
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        $cacheStatus = conf('cache.is_open', ConfigService::CACHE_PARAMS['is_open'], $categoryId, $groupId);
        if ($cacheStatus != 'on') {
            return '';
        }

        // 获取模板ID
        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($templateId)) {
            return '';
        }

        // $template = Template::find($templateId);
        $template = CommonService::templates(['id' => $templateId], 1);
        if (empty($template) || empty($template['category_id'] ?? 0)) {
            return '';
        }
        $groupId = $template['group_id'] ?? 0;

        $url = request()->url();
        $file = base64_encode($url) . '.txt';
        $path = 'cache/templates/'.$groupId.'/'.$type.'/'.$file;

        $result = '';
        if (Storage::disk('local')->exists($path)) {
            $result = Storage::disk('local')->get($path);
        }

        return $result ?: '';
    }

    /**
     * 写入缓存
     *
     * @return void
     */
    public static function setCache($content)
    {
        // 获取页面类型
        $type = self::getUriType();

        // 如果是sitemap, 则不缓存
        if ($type == 'sitemap') {
            return '';
        }

        // 获取配置信息
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        $config = conf('cache', ConfigService::CACHE_PARAMS, $categoryId, $groupId);
        if ($config['is_open'] != 'on') {
            return '';
        }

        // 判断是不是仅蜘蛛爬行产生缓存
        if ($config['spider_open'] == 'on') {
            // 判断当前是用户还是蜘蛛
            $spider = SpiderService::getSpider();

            if (empty($spider)) {
                return '';
            }
        }

        // 获取模板ID
        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($templateId)) {
            return '';
        }

        // $template = Template::find($templateId);
        $template = CommonService::templates(['id' => $templateId], 1);
        if (empty($template) || empty($template['category_id'] ?? 0)) {
            return '';
        }
        $groupId = $template['group_id'] ?? 0;

        $url = request()->url();
        $file = base64_encode($url) . '.txt';
        $path = 'cache/templates/'.$groupId.'/'.$type.'/'.$file;

        // 判断是否开启缓存时间
        if ($config['cache_time']['is_open'] == 'on') {
            $cacheTime = $config['cache_time'][$type] ?? 0;
            $cacheTime = (int)$cacheTime;
            if (empty($cacheTime)) {
                return false;
            }
            $time = time() + $cacheTime * 60 * 60;

            $key = 'cacheFileData';

            $cacheData = Cache::store('file')->pull($key);

            if (empty($cacheData)) {
                Cache::store('file')->put($key, [$path => $time]);
            } else {
                $cacheData[$path] = $time;

                Cache::store('file')->put($key, $cacheData);
            }
        } else {
            if ($type == 'index') {
                return '';
            }
        }

        Storage::disk('local')->put($path, $content);
    }

    /**
     * 获取句子数量
     *
     * @return void
     */
    public static function getSentenceCount($content, &$globalData)
    {
        // 获取文章内容的总数
        $sentenceData = [];
        $content = preg_replace_callback('/{[^{]*(句子|文章内容)+\d*}/', function ($match) use (&$sentenceData) {
            if ($match[1] == '句子') {
                $sentenceData[] = 0;
            }

            return $match[0];
        }, $content);
        // 如果句子数据不为空, 则
        if (!empty($sentenceData)) {
            // 将句子总数写入缓存
            $sentenceTotal = count($sentenceData);
            // $sentenceKey = request()->url() . '_sentence_count';
            // Cache::put($sentenceKey, $sentenceTotal, self::CACHE_TIME);
            $globalData['sentence_count'] = $sentenceTotal;
        }
    }

    /**
     * 获取关键词数量
     *
     * @return void
     */
    public static function getKeywordTimes($content, &$globalData)
    {
        // 获取文章内容的总数
        $contentTotal = 0;
        $sentenceData = [];
        $content = preg_replace_callback('/{[^{]*(句子|文章内容)+\d*}/', function ($bfmatch) use (&$contentTotal, &$sentenceData) {
            if ($bfmatch[1] == '句子') {
                $sentenceData[] = 0;
            } else if ($bfmatch[1] == '文章内容') {
                $contentTotal++;
            }

            return $bfmatch[0];
        }, $content);
        $sentenceTotal = count($sentenceData);

        // 将句子次数和文章内容次数写入缓存(关键词内链)
        // $sentenceTimesKey = request()->url() . '_keywords_sentence_times_data';
        // $contentKey = request()->url() . '_keywords_content_times';
        $webCategoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();
        $keywordTimes = conf('site.keyword_chain.times', 1, $webCategoryId, $groupId);
        $contentTimes = 0;
        $sentenceTimes = 0;
        if ($sentenceTotal > 0) {
            if ($contentTotal > 0) {
                $contentTimes = bcdiv($keywordTimes, 2, 0);
                $sentenceTimes = $keywordTimes - $contentTimes;
            } else {
                $sentenceTimes = $keywordTimes;
            }
        } else {
            if ($contentTotal > 0) {
                $contentTimes = $keywordTimes;
            }
        }
        // 分配每个句子需要加入的内链数量
        if ($sentenceTimes > $sentenceTotal) {
            $eachTimes = bcdiv($sentenceTimes, $sentenceTotal, 0);
            $remain = $sentenceTimes - bcmul($eachTimes, $sentenceTotal, 0);

            foreach ($sentenceData as $key => &$value) {
                $value = $value + $eachTimes;
                if ($remain > 0) {
                    $value++;
                    $remain--;
                }
            }
        } else {
            $randKeys = multiple_rand(0, $sentenceTotal-1, $sentenceTimes);
            foreach ($sentenceData as $key => &$value) {
                if (in_array($key, $randKeys)) {
                     $value++;
                }
            }
        }
        // dd($sentenceData);
        // Cache::put($contentKey, $contentTimes, self::CACHE_TIME);
        // Cache::put($sentenceTimesKey, $sentenceData, self::CACHE_TIME);
        $globalData['keywords_content_times'] = $contentTimes;
        $globalData['keywords_sentence_times_data'] = $sentenceData;
        // dump('sentence: '.$sentenceTimes);
        // dump('content: '.$contentTimes);
    }

    /**
     * 获取文章关联内容数量
     *
     * @return void
     */
    public static function getTitleRelevanceTimes($content, &$globalData)
    {
        // 获取文章内容的总数
        $contentTotal = 0;
        $sentenceData = [];
        $content = preg_replace_callback('/{[^{]*(句子|文章内容)+\d*}/', function ($bfmatch) use (&$contentTotal, &$sentenceData) {
            if ($bfmatch[1] == '句子') {
                $sentenceData[] = 0;
            } else if ($bfmatch[1] == '文章内容') {
                $contentTotal++;
            }

            return $bfmatch[0];
        }, $content);
        $sentenceTotal = count($sentenceData);

        // 将句子次数和文章内容次数写入缓存(关键词内链)
        // $sentenceTimesKey = request()->url() . '_title_sentence_times';
        // $contentKey = request()->url() . '_title_content_times';
        // $titleKey = request()->url() . '_title_value';
        // $titleVal = Cache::get($titleKey, []);
        $titleVal = $globalData['title_value'] ?? [];
        $titleTimes = count($titleVal);
        $contentTimes = 0;
        $sentenceTimes = 0;
        if ($sentenceTotal > 0) {
            if ($contentTotal > 0) {
                $contentTimes = bcdiv($titleTimes, 2, 0);
                $sentenceTimes = $titleTimes - $contentTimes;
            } else {
                $sentenceTimes = $titleTimes;
            }
        } else {
            if ($contentTotal > 0) {
                $contentTimes = $titleTimes;
            }
        }

        // 分配每个句子需要加入的内链数量
        if ($sentenceTimes > $sentenceTotal) {
            $eachTimes = bcdiv($sentenceTimes, $sentenceTotal, 0);
            $remain = $sentenceTimes - bcmul($eachTimes, $sentenceTotal, 0);

            foreach ($sentenceData as $key => &$value) {
                $value = $value + $eachTimes;
                if ($remain > 0) {
                    $value++;
                    $remain--;
                }
            }
        } else {
            $randKeys = multiple_rand(0, $sentenceTotal-1, $sentenceTimes);
            foreach ($sentenceData as $key => &$value) {
                if (in_array($key, $randKeys)) {
                     $value++;
                }
            }
        }
        // Cache::put($contentKey, $contentTimes, self::CACHE_TIME);
        // Cache::put($sentenceTimesKey, $sentenceData, self::CACHE_TIME);
        $globalData['title_content_times'] = $contentTimes;
        $globalData['title_sentence_times'] = $sentenceData;
    }

    /**
     * 将地址和标题的对应关系写入缓存
     *
     * @param [type] $content
     * @return void
     */
    public static function putUrlTitle($content, $uriType, &$globalData=[])
    {
        $pushData = [];
        $content = preg_replace_callback("#<a.*?href *= *[\'|\"](.*?)[\'|\"][\s\S]*?>([\s\S]*?)< */a>#i", function ($preg) use ($uriType, &$globalData, &$pushData) {
            $whole = $preg[0];
            $href = $preg[1];
            $aHtml = $preg[2];

            $titleData = [
                'title' => '',
                'article_title' => '',
                'keyword' => '',
            ];

            $href = self::replaceAllTag($href, $uriType, $globalData);

            // 更新连接
            $whole = str_replace($preg[1], $href, $whole);

            $articleId = 0;
            $aHtml = preg_replace_callback_array([
                // 系统标签系列
                '/{(随机数字|随机字母|时间|固定数字|固定字母|当前网址)+\d*}/' => function ($match) use (&$globalData) {
                    $key = $match[0];
                    $type = $match[1] ?? '';

                    if (in_array($type, ['固定数字', '固定字母'])) {
                        // 判断数组里是否存在
                        if (!array_key_exists($key, $globalData)) {
                            $globalData[$key] = IndexPregService::randSystemTag($type, $key);
                        }

                        return $globalData[$key];
                    }

                    return IndexPregService::randSystemTag($type, $key);
                },
                // 内容系列
                '/{[^{]*(文章题目|标题|网站名称|栏目|句子|图片|视频|关键词|文章内容)+\d*}/' => function ($match) use (&$globalData, $uriType, &$articleId, &$titleData) {
                    $key = $match[0];
                    $type = $match[1] ?? '';

                    $typeData = IndexPregService::getContentInfo($type, $key);

                    if ($typeData['is_number'] != 0) {
                        // 判断数组里是否存在
                        if (!array_key_exists($key, $globalData)) {
                            $result = IndexPregService::randContent($type, $key, $uriType, $globalData);
                            $globalData[$key] = $result;
                            if (is_array($result)) {
                                $articleId = $result['id'];
                                $result = $result['value'];
                            }
                        } else {
                            $result = $globalData[$key];
                            if (is_array($result)) {
                                $articleId = $result['id'];
                                $result = $result['value'];
                            }
                        }
                    } else {
                        $result = IndexPregService::randContent($type, $key, $uriType, $globalData);
                        if (is_array($result)) {
                            $articleId = $result['id'];
                            $result = $result['value'];
                        }
                    }
                    if ($type == '标题') {
                        $titleData['title'] = $result;
                    } else if ($type == '文章题目') {
                        $titleData['article_title'] = $result;
                    } else if ($type == '关键词') {
                        $titleData['keyword'] = $result;
                    }

                    return $result;
                },
                // 其他自定义标签
                '/{([^{\r\n}]+)\d*}/' => function ($match) use (&$globalData) {
                    $tag = $match[0];
                    $noNumTag = $match[1];
                    $noNumTag = preg_replace_callback("/[\D]+(\d+)/", function ($match) {
                        $result = rtrim($match[0], $match[1]);

                        return $result;
                    }, $noNumTag);
                    if ($noNumTag == '摘要') {
                        return $tag;
                    }
                    $isNumber = IndexPregService::diyHasNumber($tag, $noNumTag);
                    if ($noNumTag == '相关词') {
                        $keyword = $globalData['related_words'] ?? '';
                        if (!empty($keyword)) {
                            if ($isNumber) {
                                // 判断数组里是否存在
                                if (!array_key_exists($tag, $globalData)) {
                                    $globalData[$tag] = CommonService::getBaiduDropdownWords($keyword);
                                }

                                return $globalData[$tag];
                            } else {
                                return CommonService::getBaiduDropdownWords($keyword);
                            }
                        } else {
                            return '';
                        }
                    }

                    if ($isNumber) {
                        // 判断数组里是否存在
                        if (!array_key_exists($tag, $globalData)) {
                            $globalData[$tag] = IndexPregService::randDiyContent($tag);
                        }

                        return $globalData[$tag];
                    } else {
                        return IndexPregService::randDiyContent($tag);
                    }
                },
            ], $aHtml);

            // 更新标题内容
            $whole = str_replace($preg[2], $aHtml, $whole);

            $tempTitle = strip_tags($aHtml);
            $title = '';
            // 判断保存的title, article_title, keyword是否有值
            if (!empty($titleData['title'])) {
                $title = $titleData['title'];
            } else if (!empty($titleData['article_title'])) {
                // $title = $titleData['article_title'];
                $title = '';
            } else if (!empty($titleData['keyword'])) {
                $title = $titleData['keyword'];
            } else {
                $titleArr = CommonService::linefeedStringToArray($tempTitle);
                if (!empty($titleArr)) {
                    foreach ($titleArr as $titleKey => &$titleVal) {
                        $titleVal = trim($titleVal);
                        if (empty($titleVal)) {
                            unset($titleArr[$titleKey]);
                        }
                    }

                    $title = implode('', $titleArr);
                }
            }

            if (!empty($title) && empty($articleId)) {
                $url = url($href);
                $key = $url;
                $title = $title;

                // Cache::put($key, $title, self::CACHE_TIME);
                $pushData[$key] = $title;
            }
            if (!empty($articleId)) {
                $url = url($href) . '_article_id';
                $articleKey = $url;

                // Cache::put($articleKey, $articleId, self::CACHE_TIME);
                $pushData[$articleKey] = $articleId;
            }

            return $whole;
        }, $content);
        
        // 将地址批量插入redis
        redis_batch_set($pushData);

        return $content;
    }

    /**
     * 循环循环标签中间的内容
     *
     * @param string $content
     * @return string
     */
    public static function repeatTag(string $content)
    {
        // return $content;
        $contentData = [];
        $content = preg_replace_callback('#{ *?循环开始(.*?)}([\s\S]*?){ *?循环结束 *?}#', function($match) use (&$contentData) {
            $repeatContent = $match[2];
            $params = $match[1] ?? '';
            preg_match('# *?times *?= *?[\'\"](.*?)[\'\"] *?#', $params, $timesMatch);
            preg_match('# *?data *?= *?[\'\"](.*?)[\'\"] *?#', $params, $dataMatch);

            $times = trim($timesMatch[1] ?? 1);
            $dataStr = trim($dataMatch[1] ?? '');

            // 获取参数
            $resultData = [];
            // 将字符串中的中文符号, 替换成英文符号
            $dataStr = str_replace(['，','；','：'],[',',';',':'],$dataStr);
            $tempData = explode(';', $dataStr);
            foreach ($tempData as $temp) {
                $childData = explode(':', $temp);
                if (empty($childData)) {
                    continue;
                }

                $childArr = explode(',', $childData[1] ?? '');
                $resultData['{'.trim($childData[0]).'}'] = array_filter($childArr);
            }

            $result = '';
            $addStatus = [];
            for ($i=1; $i <= $times; $i++) {
                $keys = array_keys($resultData);
                $keyStr = implode('|', $keys);
                $nowData = [];
                foreach ($resultData as $key => &$val) {
                    $tempVal = trim(array_shift($val));
                    $nowData[$key] = $tempVal;
                    $val[] = $tempVal;
                }
                // 替换参数
                $pattern = '#'.$keyStr.'#';
                $tempRepeat = preg_replace_callback($pattern, function ($match) use ($i, &$nowData) {
                    $pregKey = $match[0] ?? '';
                    if (empty($pregKey)) {
                        return $pregKey;
                    }
                    if (array_key_exists($pregKey, $nowData)) {
                        return $nowData[$pregKey];
                    }

                    return '';
                }, $repeatContent);

                // 记录物料库+num++, 不同循环中相同分类名num++前面依次加上1,2,3...
                $contentPattern = '#{([^{}]*?)num\+\+(.*?)}#';
                $tempRepeat = preg_replace_callback($contentPattern, function ($match) use(&$contentData, &$addStatus, $times) {
                    $key = $match[1] ?? '';
                    $whole = $match[0] ?? '';
                    if (array_key_exists($key, $contentData)) {
                        if (!array_key_exists($key, $addStatus)) {
                            $contentData[$key] = (int)$contentData[$key] + 1;
                            $addStatus[$key] = true;
                        }
                        $newKey = $key;
                        if (!empty($contentData[$key])) {
                            $newKey = $key . $contentData[$key];
                        }

                        return str_replace($key, $newKey, $whole);
                    }

                    if (!array_key_exists($key, $addStatus)) {
                        $contentData[$key] = 0;
                        $addStatus[$key] = true;
                    }

                    return $whole;
                }, $tempRepeat);

                $pattern = '#(num\+\+|'.$keyStr.')#';
                $tempResult = preg_replace_callback($pattern, function ($match) use ($i, &$nowData) {
                    $pregKey = $match[0] ?? '';
                    if (empty($pregKey)) {
                        return $pregKey;
                    }
                    // 如果是num++, 则替换当前$i
                    if ($pregKey == 'num++') {
                        return $i;
                    }
                    if (array_key_exists($pregKey, $nowData)) {
                        return $nowData[$pregKey];
                    }

                    return '';
                }, $tempRepeat);

                // 每一次循环内的固定字母和固定数字相同, 否则不同
                $repeatPattern = '/{(随机数字|随机字母|固定数字|固定字母)+\d*}/';
                $fixedData = [];
                $tempResult = preg_replace_callback($repeatPattern, function ($match) use (&$fixedData) {
                    $key = $match[0];
                    $type = $match[1] ?? '';

                    if (in_array($type, ['固定数字', '固定字母'])) {
                        // 判断数组里是否存在
                        if (!array_key_exists($key, $fixedData)) {
                            $fixedData[$key] = IndexPregService::randSystemTag($type, $key);
                        }

                        return $fixedData[$key];
                    }

                    return IndexPregService::randSystemTag($type, $key);
                }, $tempResult);

                $result .= $tempResult;
            }

            return $result;
        }, $content);

        return $content;
    }

    /**
     * 删除本页面中需要删除的缓存值
     *
     * @param array $siteConfig
     * @return void
     */
    public static function delPageCache($siteConfig)
    {
        // 判断刷新是否改变
        $isNotChange = CommonService::ifRefreshNotChange($siteConfig, 'config');
        if (!$isNotChange) {
            // 如果刷新改变, 则清空保存的句子数据和文章id数据
            $baseUrl = request()->url();
            $key = $baseUrl . '_sentences_not_change';
            Cache::forget($key);
            $articleKey = $baseUrl . '_article_id';
            Cache::forget($articleKey);
        }
    }

    /**
     * 提前将需要的数据取出
     *
     * @param [type] $globalData
     * @return void
     */
    public static function getBatchRedisData(&$globalData, $baseUrl='')
    {
        if (empty($baseUrl)) {
            $baseUrl = request()->url();
        }
        $key1 = $baseUrl . '_sentences_not_change';
        $key2 = $baseUrl . '_article_id';
        $key3 = $baseUrl;
        $keys = [
            $key1,
            $key2,
            $key3,
        ];

        $result = redis_batch_get($keys, true);

        $globalData[$key1] = $result[$key1] ?? '';
        $globalData[$key2] = $result[$key2] ?? 0;
        $globalData[$key3] = $result[$key3] ?? '';
    }

    /**
     * 批量写入redis数据
     *
     * @param array $globalData
     * @return void
     */
    public static function setBatchRedisData(&$globalData, $baseUrl='')
    {
        if (empty($baseUrl)) {
            $baseUrl = request()->url();
        }
        $result = [];
        if (!empty($globalData['sentence_arr'])) {
            $sentenceKey = $baseUrl . '_sentences_not_change';
            $result[$sentenceKey] = json_encode($globalData['sentence_arr']);
        }
        if (!empty($globalData['key_article_id'])) {
            $articleKey = $baseUrl . '_article_id';
            $result[$articleKey] = $globalData['key_article_id'];
        }

        redis_batch_set($result, true);
    }

    /**
     * 根据条件判断是否需要重定向到首页
     *
     * @return void
     */
    public static function redirectTo()
    {
        try {
            $ip = CommonService::getUserIpAddr();
            $url = request()->path();
    
            $ipArr = explode('.', $ip);
            array_pop($ipArr);
            $shortIp = implode('.', $ipArr);
    
            if ($shortIp == '116.179.37' && $url != '/') {
                return redirect('/');
            }
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * 网站互链
     *
     * @param string $content
     * @return string
     */
    public static function reciprocalLink($content, $type, $categoryId)
    {
        // 获取网站互链配置
        $config = conf('reciprocallink', [], 0, 0);
        if (empty($config)) {
            return $content;
        }
        $isOpen = $config['is_open'] ?? 'off';
        $categoryIds = $config['category'] ?? [];
        $num = $config['num'] ?? 10;
        $location = $config['location'] ?? 'index';
        $linkText = $config['link_text'] ?? 'group';

        // 判断是否开启
        if ($isOpen != 'on') {
            return $content;
        }
        // 判断当前类型是否符合
        if ($type != $location) {
            return $content;
        }
        // 判断分类ID是否在其中
        if (!in_array($categoryId, $categoryIds)) {
            return $content;
        }

        $websites = Website::whereIn('category_id', $categoryIds)
                    ->pluck('group_id', 'url')
                    ->toArray();
                    
        // 从网址中取出num个网址, 可重复
        $urls = [];
        for ($i=0; $i<$num; $i++) {
           $baseUrl = array_rand($websites);
           $urls[] = str_replace('*.', '', $baseUrl);
        }

        $aTags = "<div style='display:none;'>";

        foreach ($urls as $key => $url) {
            // 判断文本类型
            $groupId = $websites[$url] ?? 0;
            $text = '';
            if ($linkText == 'group') {
                static $groups = [];
                $groups = TemplateService::groupOptions()->toArray();

                $text = $groups[$groupId] ?? '';
            } else if ($linkText == 'keywords') {
                $categoryIds = CommonService::contentCategories([
                    'type'     => 'keyword',
                    'group_id' => $groupId,
                ], 0, 'id');
                $keywords = CommonService::contents($type, '关键词', 'App\Models\Keyword', $groupId, 'content', ['category_id' => $categoryIds], [], 0, ['content', 'id']);

                if (!empty($keywords)) {
                    $text = $keywords[array_rand($keywords)];
                }
            } else if ($linkText == 'title') {
                $categoryIds = CommonService::contentCategories([
                    'type'     => 'title',
                    'group_id' => $groupId,
                ], 0, 'id');
                $titles = CommonService::contents($type, '标题', 'App\Models\Title', $groupId, 'content', ['category_id' => $categoryIds], [], 0, ['content', 'id']);

                if (!empty($titles)) {
                    $text = $titles[array_rand($titles)];
                }
            } else {
                return $content;
            }

            $aTags .= "<a href='http://{$url}'>{$text}</a>";
        }
        $aTags .= "</div>";

        return $content . $aTags;
    }

    /**
     * ascii特殊吗混淆
     *
     * @param string $content
     * @param array $siteConfig
     * @return string
     */
    public static function asciiDescription($content, $siteConfig)
    {
        // 判断是否开启描述ascii转换
        $asciiConfig = $siteConfig['ascii'] ?? [];
        $asciiDescription = $asciiConfig['ascii_description'] ?? 'off';
        $rate = $asciiConfig['ascii_description_rate'] ?? 0;
        if ($asciiDescription == 'on') {
            $content = preg_replace_callback('#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */* *>#i', function ($match) use ($rate) {
                $description = $match[1] ?? '';
                if (empty($description)) {
                    return $match[0] ?? '';
                }

                // 判断描述内容中是否有标签
                if (strpos($description, '{') === false ||
                    strpos($description, '}') === false
                ) {
                    $afterDescription = '';
                    $descriptionArr = mb_str_split($description, 1);
                    foreach ($descriptionArr as $value) {
                        $afterDescription .= $value;
                        if (mt_rand(1, 100) <= $rate) {
                            $ascStr = CommonService::getSpecAscii();
                            $afterDescription .= $ascStr;
                        }
                    }
    
                    return str_replace($description, $afterDescription, $match[0]);
                } else {
                    // 增加标识符
                    $addDescription = '@'.$description.'@';
                    $afterDescription = preg_replace_callback_array([
                        '#}(.*?){#' => function ($matchC) use ($rate) {
                            $afterDescription = '';
                            $beforeDescription = $matchC[1];
                            $descriptionArr = mb_str_split($beforeDescription, 1);
                            foreach ($descriptionArr as $value) {
                                $afterDescription .= $value;
                                if (mt_rand(1, 100) <= $rate) {
                                    $ascStr = CommonService::getSpecAscii();
                                    $afterDescription .= $ascStr;
                                }
                            }
            
                            return str_replace($beforeDescription, $afterDescription, $matchC[0]);
                        },
                        '#\@(.*?){#' => function ($matchC) use ($rate) {
                            $afterDescription = '';
                            $beforeDescription = $matchC[1];
                            $descriptionArr = mb_str_split($beforeDescription, 1);
                            foreach ($descriptionArr as $value) {
                                $afterDescription .= $value;
                                if (mt_rand(1, 100) <= $rate) {
                                    $ascStr = CommonService::getSpecAscii();
                                    $afterDescription .= $ascStr;
                                }
                            }
            
                            return str_replace($beforeDescription, $afterDescription, $matchC[0]);
                        },
                        '#}([^}]*?)\@#' => function ($matchC) use ($rate) {
                            $afterDescription = '';
                            $beforeDescription = $matchC[1];
                            $descriptionArr = mb_str_split($beforeDescription, 1);
                            foreach ($descriptionArr as $value) {
                                $afterDescription .= $value;
                                if (mt_rand(1, 100) <= $rate) {
                                    $ascStr = CommonService::getSpecAscii();
                                    $afterDescription .= $ascStr;
                                }
                            }
            
                            return str_replace($beforeDescription, $afterDescription, $matchC[0]);
                        },
                    ], $addDescription);
                    // 去除标识符
                    $afterDescription = trim($afterDescription, '@');

                    return str_replace($description, $afterDescription, $match[0]);
                }

            }, $content);
        }

        return $content;
    }

    /**
     * 将内容ascii化
     *
     * @param string $summary
     * @param array $siteConfig
     * @return string
     */
    public static function summaryAsciiDescription($summary, $siteConfig)
    {
        // 判断是否开启描述ascii转换
        $asciiConfig = $siteConfig['ascii'] ?? [];
        $asciiDescription = $asciiConfig['ascii_description'] ?? 'off';
        $rate = $asciiConfig['ascii_description_rate'] ?? 0;
        if ($asciiDescription == 'on') {
            $dataArr = mb_str_split($summary, 1);
            $afterSummary = '';
            foreach ($dataArr as $value) {
                $afterSummary .= $value;
                if (mt_rand(1, 100) <= $rate) {
                    $ascStr = CommonService::getSpecAscii();
                    $afterSummary .= $ascStr;
                }
            }
        } else {
            $afterSummary = $summary;
        }

        return $afterSummary;
    }
}
